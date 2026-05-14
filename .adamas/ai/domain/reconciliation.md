# 🔄 Reconciliation Strategy — GP-Valise

> Document de design Phase 7+. À lire avant toute implémentation du moteur de réconciliation.

---

## 🎯 Objectif

Vérifier que l'état financier interne (Transactions + Ledger) est cohérent avec l'état réel chez les PSP.

```
État interne (DB)  ↔  État externe (PSP)
Transactions       ↔  KkiapayAdminClient::verify()
LedgerEntries      ↔  Stripe dashboard / Kkiapay reports
```

**La réconciliation ne modifie jamais le domaine directement.** Elle produit des rapports et déclenche des investigations.

---

## 🧠 Principe fondamental

```
Webhook = signal financier primaire (temps réel)
Réconciliation = vérification secondaire (batch)
```

La réconciliation ne remplace pas les webhooks. Elle détecte les cas où :

- Un webhook n'a pas été reçu
- Un webhook a été traité incorrectement
- Un PSP rapporte un état différent de notre DB

---

## 🔍 Sources de vérité

| Source                         | Type             | Usage                        |
| ------------------------------ | ---------------- | ---------------------------- |
| `transactions` (DB)            | Source interne   | Référence principale         |
| `ledger_entries` (DB)          | Source comptable | Vérification comptable       |
| `webhook_logs` (DB)            | Historique async | Traçabilité events           |
| `KkiapayAdminClient::verify()` | API PSP          | Vérification externe Kkiapay |
| Stripe API                     | API PSP          | Vérification externe Stripe  |

---

## 🧩 Types de réconciliation

### 1. Réconciliation ledger interne

**Objectif :** Vérifier `SUM(debits) = SUM(credits)` pour toutes les transactions.

**Fréquence :** Quotidien (batch nuit).

```sql
SELECT
  transaction_id,
  SUM(CASE WHEN direction = 'DEBIT'  THEN amount ELSE 0 END) as total_debit,
  SUM(CASE WHEN direction = 'CREDIT' THEN amount ELSE 0 END) as total_credit,
  ABS(SUM(CASE WHEN direction = 'DEBIT' THEN amount ELSE -amount END)) as imbalance
FROM ledger_entries
GROUP BY transaction_id
HAVING imbalance != 0;
```

**Résultat :** Liste des transactions avec déséquilibre → investigation manuelle.

---

### 2. Réconciliation Transaction ↔ PSP

**Objectif :** Vérifier que les transactions PENDING en DB ont un état PSP connu.

**Fréquence :** Horaire pour les PENDING > 30 min, quotidien pour l'historique.

**Flow :**

```
Transactions PENDING depuis > 30min
  → KkiapayAdminClient::verify(provider_transaction_id)
  → Comparer providerStatus avec transaction.status
  → Si divergence → rapport ReconciliationAnomaly
```

**Cas possibles :**

| DB status | PSP status  | Action                                   |
| --------- | ----------- | ---------------------------------------- |
| PENDING   | success     | Webhook manqué → déclencher manuellement |
| PENDING   | failed      | Webhook manqué → marquer FAILED          |
| COMPLETED | introuvable | Anomalie → investigation                 |
| PENDING   | pending     | Normal — attendre                        |

---

### 3. Réconciliation escrow

**Objectif :** Détecter les bookings LIVREE dont l'escrow aurait dû être libéré.

**Fréquence :** Horaire (aligné avec `ReleaseEscrowBatch`).

```sql
SELECT id, escrow_releasable_at, disputed_at
FROM bookings
WHERE status = 'LIVREE'
AND escrow_releasable_at <= NOW()
AND disputed_at IS NULL
AND id NOT IN (
  SELECT booking_id FROM transactions WHERE type = 'PAYOUT'
);
```

**Action :** Ces bookings auraient dû être traités par `ReleaseEscrowBatch`. Investiguer pourquoi ils ne l'ont pas été.

---

### 4. Réconciliation audit chain

**Objectif :** Vérifier l'intégrité de la chaîne de hash.

**Fréquence :** Quotidien.

```php
$isValid = app(AuditLogIntegrityService::class)->verifyChainFrom(0);
```

**Si invalide :** Incident de sécurité potentiel — escalader immédiatement.

---

## 🏗️ Architecture cible

### Composants

```
ReconciliationRunner         → orchestrateur (artisan command / scheduler)
LedgerReconciliationService  → vérification SUM debits = SUM credits
TransactionReconciliationService → DB ↔ PSP comparison
EscrowReconciliationService  → escrow release manqué
ReconciliationReport         → modèle des anomalies détectées
ReconciliationAnomaly        → event dispatché si anomalie
```

### Tables

```sql
reconciliation_runs
  id
  type          -- ledger | transaction | escrow | audit
  started_at
  completed_at
  status        -- running | completed | failed
  anomalies_count
  report        -- JSON

reconciliation_anomalies
  id
  run_id        FK → reconciliation_runs
  type          -- imbalance | psp_divergence | escrow_missed | chain_broken
  entity_type   -- Transaction | Booking | AuditLog
  entity_id
  expected      -- JSON (état attendu)
  actual        -- JSON (état constaté)
  resolved_at
  resolved_by
  created_at
```

---

## 🔒 Règles critiques

```
✅ La réconciliation est read-only par défaut
✅ Toute correction passe par une Action dédiée avec audit
✅ Anomalies persistées dans reconciliation_anomalies
✅ Alerting Slack si anomalies > seuil
❌ Jamais modifier Transaction / LedgerEntry directement
❌ Jamais auto-corriger sans validation humaine
❌ La réconciliation ne remplace pas les webhooks
```

---

## 🔌 KkiapayAdminClient — usage réconciliation

`verify()` est actuellement implémenté mais pas dans le flow primaire.

**Usage prévu en réconciliation :**

```php
// Vérification d'une transaction ambiguë
$result = $kkiapayClient->verify($providerTransactionId);

// result n'est PAS une source de vérité finale
// c'est un signal de réconciliation
if ($result->status !== $transaction->providerStatus) {
    ReconciliationAnomaly::create([...]);
    ReconciliationAnomalyDetected::dispatch($anomaly);
}
```

**Important :** Un succès SDK `verify()` ne déclenche pas de mise à jour automatique. Il produit un rapport.

---

## 🧪 Tests obligatoires

| Scénario                                             | Type    |
| ---------------------------------------------------- | ------- |
| Ledger équilibré → aucune anomalie                   | Feature |
| Ledger déséquilibré → anomalie créée                 | Feature |
| Transaction PENDING PSP success → rapport divergence | Feature |
| Escrow non libéré → détecté                          | Feature |
| Réconciliation idempotente                           | Feature |
| Alerting déclenché si anomalies > seuil              | Feature |

---

## 📈 Roadmap

```
Phase 7a — Réconciliation ledger
  LedgerReconciliationService
  reconciliation_runs + reconciliation_anomalies
  Artisan command + scheduler nuit

Phase 7b — Réconciliation Transaction ↔ PSP
  TransactionReconciliationService
  Intégration KkiapayAdminClient::verify()
  Alerting Slack sur divergences

Phase 7c — Dashboard réconciliation (Filament)
  Vue anomalies
  Workflow résolution manuelle
  Export rapport CSV
```

---

## 🧠 Résumé

```
Réconciliation = filet de sécurité
Webhooks = source primaire

Objectif :
→ détecter ce que les webhooks ont manqué
→ jamais corriger aveuglément
→ toujours passer par des Actions auditées
```
