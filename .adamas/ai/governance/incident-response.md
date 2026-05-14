# 🚨 Incident Response — GP-Valise

> Procédure d'investigation et de résolution des incidents en production.

---

## 🧠 Principe fondamental

```
correlation_id + logs + webhook_logs + transactions + audit_logs
= capacité à reconstruire n'importe quel incident
```

Chaque incident doit être :

- **détecté** via monitoring / alerting
- **tracé** via correlation_id
- **diagnostiqué** via les sources de traçabilité
- **résolu** via les actions correctives autorisées
- **documenté** dans `decision-log.md` si impactant

---

## 📊 Niveaux d'incident

| Niveau      | Exemples                                                                                | Délai réponse |
| ----------- | --------------------------------------------------------------------------------------- | ------------- |
| 🔴 Critique | Incohérence financière, double payout, escrow non libéré, webhook définitivement failed | Immédiat      |
| 🟠 Majeur   | Retry storm, backlog élevé, dispute non assignée > SLA                                  | < 1h          |
| 🟡 Mineur   | Job lent, log d'erreur isolé, rate limit PSP                                            | < 24h         |

---

## 🔍 Sources de traçabilité

| Source                     | Contient                                       | Usage                   |
| -------------------------- | ---------------------------------------------- | ----------------------- |
| `logs Laravel`             | correlation_id, context structuré              | Première investigation  |
| `webhook_logs`             | event_id, status, payload brut, correlation_id | Incidents webhook       |
| `transactions`             | type, status, amount, correlation_id           | Vérification financière |
| `ledger_entries`           | debits/credits par transaction                 | Vérification comptable  |
| `audit_logs`               | actions admin, hash chain                      | Preuves admin           |
| `booking_status_histories` | transitions, acteur, raison                    | Timeline booking        |
| `dispute_status_histories` | transitions dispute                            | Timeline dispute        |
| Horizon UI                 | jobs failed, retry, queue states               | Monitoring queues       |

---

## 🔁 Procédure générale

```
1. Identifier le correlation_id (header HTTP ou booking_id/transaction_id)
2. Croiser : logs → webhook_logs → transactions → audit_logs
3. Identifier la source de l'anomalie
4. Évaluer l'impact financier (transaction + ledger)
5. Appliquer l'action corrective autorisée
6. Documenter dans decision-log.md si nécessaire
```

---

## 🔴 Scénarios critiques

### Webhook `refund.completed` définitivement failed

**Symptôme :** Alerte Slack "job échoué définitivement". Provider a remboursé. Transaction REFUND COMPLETED. Booking reste CONFIRMEE ou EN_LITIGE.

**Diagnostic :**

```sql
-- Trouver le webhook
SELECT * FROM webhook_logs WHERE event_id = 'evt_xxx';

-- Vérifier la transaction
SELECT * FROM transactions WHERE provider_transaction_id = 'txn_xxx';

-- Vérifier le booking
SELECT * FROM bookings WHERE id = ?;
```

**Actions correctives autorisées :**

1. Vérifier que le bug C3 n'est pas en cause (CONFIRMEE → REMBOURSEE manquant dans allowedTransitions)
2. Si Transaction REFUND COMPLETED mais Booking non mis à jour → transition manuelle via Action dédiée (jamais directement en DB)
3. Créer un AuditLog de l'intervention avec raison

---

### Double payout détecté

**Symptôme :** Alerte ou rapport de deux PAYOUT COMPLETED sur même booking.

**Diagnostic :**

```sql
SELECT type, status, amount, created_at
FROM transactions
WHERE booking_id = ? AND type = 'PAYOUT'
ORDER BY created_at;
```

**Actions :**

1. Identifier lequel est légitime (timestamp + correlation_id + webhook source)
2. Si PSP a débité deux fois → contacter PSP pour remboursement du doublon
3. Marquer la transaction erronée dans les métadonnées (jamais modifier — créer une correction)
4. Audit log obligatoire + decision-log

---

### Escrow non libéré après délai

**Symptôme :** Booking LIVREE avec `escrow_releasable_at` dépassé, aucun PAYOUT créé.

**Diagnostic :**

```sql
SELECT id, escrow_releasable_at, disputed_at, status
FROM bookings
WHERE status = 'LIVREE'
AND escrow_releasable_at <= NOW()
AND disputed_at IS NULL;

-- Vérifier les jobs
SELECT * FROM failed_jobs WHERE payload LIKE '%ReleaseEscrowPayoutJob%';
```

**Actions :**

1. Si job failed → identifier l'erreur, corriger si besoin, rejouer le job
2. Si `disputed_at IS NOT NULL` → escrow bloqué légitimement, voir Dispute
3. Si aucun job dispatché → vérifier le scheduler (`php artisan schedule:list`)
4. Rejouer manuellement : `php artisan escrow:release-payouts` (idempotent)

---

### Incohérence ledger (SUM debits ≠ SUM credits)

**Symptôme :** Alerte monitoring ou détection manuelle.

**Diagnostic :**

```sql
SELECT
  transaction_id,
  SUM(CASE WHEN direction = 'DEBIT' THEN amount ELSE 0 END) as total_debit,
  SUM(CASE WHEN direction = 'CREDIT' THEN amount ELSE 0 END) as total_credit,
  SUM(CASE WHEN direction = 'DEBIT' THEN amount ELSE -amount END) as balance
FROM ledger_entries
GROUP BY transaction_id
HAVING balance != 0;
```

**Actions :**

1. Identifier la transaction concernée
2. Vérifier le flow ledger attendu (voir `ledger-strategy.md`)
3. **Ne jamais modifier une LedgerEntry**
4. Créer des entrées de correction via une Action dédiée avec audit obligatoire
5. Documenter dans decision-log.md

---

### Dispute non résolue bloquant l'escrow

**Symptôme :** Booking avec `disputed_at IS NOT NULL` depuis longtemps, aucune résolution.

**Diagnostic :**

```sql
SELECT d.id, d.status, d.opened_by, d.assigned_to, d.created_at,
       b.id as booking_id, b.escrow_releasable_at
FROM disputes d
JOIN bookings b ON b.id = d.booking_id
WHERE d.status != 'RESOLVED'
AND d.created_at < NOW() - INTERVAL '48 hours';
```

**Actions :**

1. Si non assignée → assigner via Filament Admin
2. Si assignée mais bloquée → escalader (`UpdateDisputeStatus` → ESCALATED)
3. Résolution via `ResolveDispute` — décision obligatoire (refund ou payout)

---

### Retry storm sur queue

**Symptôme :** Alerte "retry storm détecté". Même job type retenté en boucle.

**Diagnostic :**

```bash
php artisan monitor:queue-health
# Vérifier Horizon UI → failed jobs
```

**Actions :**

1. Identifier le job en cause et son erreur
2. Si erreur infrastructure (DB down, Redis down) → attendre résolution infra
3. Si erreur applicative → corriger le code, vider la queue du job concerné
4. Rejouer après correction (jobs idempotents = safe)
5. Ne jamais vider toute la queue sans analyse

---

### Audit chain corrompue

**Symptôme :** `verifyChainFrom()` retourne false.

**Diagnostic :**

```php
app(AuditLogIntegrityService::class)->verifyChainFrom(0);
// Identifier le premier log dont verifyLog() = false
```

**Actions :**

1. Identifier le log corrompu (modification directe en DB ?)
2. Ne jamais modifier le log
3. Documenter l'incident — c'est une preuve potentielle de manipulation
4. Si manipulation confirmée → escalader (incident de sécurité)

---

## 🔒 Actions correctives autorisées vs interdites

| Action                               | Autorisée | Via                               |
| ------------------------------------ | --------- | --------------------------------- |
| Rejouer un job idempotent            | ✅        | Horizon ou artisan                |
| Forcer une transition booking        | ✅        | Action dédiée avec audit          |
| Refund admin override                | ✅        | `AdminRefundTransaction` + reason |
| Résoudre une dispute                 | ✅        | `ResolveDispute` admin            |
| Modifier directement une Transaction | ❌        | Jamais                            |
| Modifier directement un LedgerEntry  | ❌        | Jamais                            |
| Modifier directement un AuditLog     | ❌        | Jamais                            |
| UPDATE en DB sans Action dédiée      | ❌        | Jamais                            |

---

## 📝 Documentation post-incident

Tout incident 🔴 ou 🟠 doit produire une entrée dans `decision-log.md` :

```
## [DATE] — Incident : [description courte]

### Contexte
Ce qui s'est passé.

### Diagnostic
Comment identifié, correlation_id utilisé.

### Action corrective
Ce qui a été fait, par qui, pourquoi.

### Prévention
Monitoring ajouté / test ajouté / règle .adamas mise à jour.

### Statut
✅ résolu
```

---

## 🧠 Résumé

```
Détecter  → monitoring / alerting Slack
Tracer    → correlation_id
Analyser  → logs + webhook_logs + transactions + ledger + audit
Corriger  → Actions dédiées uniquement (jamais en DB direct)
Documenter → decision-log.md
```
