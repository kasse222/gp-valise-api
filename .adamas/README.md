# 🧠 Architecture Rules — GP-Valise

> Définit les responsabilités de chaque couche du système pour garantir cohérence, testabilité et évolutivité fintech.

---

## 🔁 Vue d'ensemble — Flux global

```
HTTP Request
  └─► Controller ──► FormRequest (validation)
          │
          ▼
       Policy (autorisation)
          │
          ▼
       Action (use case métier)
       ├─► Model / Enum
       ├─► Service (calcul, audit, PSP)
       ├─► DB::transaction() + lockForUpdate()
       ├─► LedgerWriter (double-entry)
       └─► Event / Job

Webhook HTTP
  └─► WebhookProcessor
       ├─► verifyWebhook() → 403 si invalide
       ├─► normalizeWebhook() → PaymentEventData
       └─► Job async
              └─► Action
                    └─► Transaction + Booking + LedgerEntry
```

---

## 🧱 Responsabilités des couches

### Controller

| ✅ Autorisé               | ❌ Interdit                 |
| ------------------------- | --------------------------- |
| Appel d'une seule Action  | Logique métier              |
| Application de Policy     | Manipulation de statuts     |
| Retour de Resource        | Conditions métier complexes |
| Conversion erreurs → HTTP | Accès Model pour décision   |

---

### FormRequest

| ✅ Autorisé                  | ❌ Interdit                          |
| ---------------------------- | ------------------------------------ |
| required / nullable / format | Validation métier (capacité, statut) |
| Types simples (email, uuid)  | Accès DB complexe                    |

> Toute validation dépendant du domaine métier → **Action**.

---

### Policy

| ✅ Autorisé               | ❌ Interdit             |
| ------------------------- | ----------------------- |
| Vérifier rôle             | Logique métier          |
| Vérifier ownership simple | Modification de données |
|                           | Transitions complexes   |

```
Policy = QUI peut accéder
Action = CE QUI est autorisé métier
```

---

### Action ⭐ Cœur du système

| ✅ Autorisé                    | ❌ Interdit                    |
| ------------------------------ | ------------------------------ |
| Orchestration métier complète  | `request()` / `Auth` directs   |
| DB::transaction()              | Calcul financier inline        |
| lockForUpdate() si concurrence | Logique dans plusieurs couches |
| Appel Service transverse       | Duplication use case           |
| Dispatch Event / Job           |                                |
| Levée exception métier         |                                |

**Règles critiques :**

- Une Action = **un seul use case** métier
- Idempotence obligatoire si risque de double exécution
- `lockForUpdate()` obligatoire si concurrence possible

---

### Enum

| ✅ Autorisé                       | ❌ Interdit           |
| --------------------------------- | --------------------- |
| `canTransitionTo()`               | Accès DB              |
| `isFinal()`, `label()`, `color()` | Dépendances externes  |
| Règles basées sur la valeur       | Logique multi-modèles |

---

### Model

| ✅ Autorisé                            | ❌ Interdit                 |
| -------------------------------------- | --------------------------- |
| Getters métier simples                 | Orchestration multi-entités |
| Scopes, relations                      | Calcul financier            |
| Helpers locaux sur ses propres données | Appel Action / Service      |
|                                        | Effets de bord              |

---

### Service

| ✅ Autorisé                        | ❌ Interdit                  |
| ---------------------------------- | ---------------------------- |
| Logique transverse réutilisable    | Remplacer une Action         |
| Intégration externe (PSP adapters) | Contenir un use case complet |
| Calcul financier centralisé        | Appeler une Action           |
| Observabilité, audit integrity     | Devenir un fourre-tout       |

**Exemples :**
`TransactionAmountCalculator` · `TransactionEligibilityService` · `AuditLogIntegrityService` · `LedgerWriter` · `LedgerReader` · `QueueHealthService` · `SlackNotifier`

---

### WebhookProcessor _(Phase 2+)_

| ✅ Autorisé                               | ❌ Interdit                 |
| ----------------------------------------- | --------------------------- |
| Construire `WebhookVerificationData`      | Logique métier              |
| `verifyWebhook()` → reject si invalide    | Modification domaine        |
| `normalizeWebhook()` → `PaymentEventData` | Connaissance statut Booking |
| Isoler Controller des payloads PSP        |                             |

```
Webhook brut
  → verifyWebhook()   → 403 si signature invalide
  → normalizeWebhook()
  → PaymentEventData (canonique)
  → Job async
```

---

### PaymentProviderResolver _(Phase 2+)_

| ✅ Autorisé                         | ❌ Interdit                    |
| ----------------------------------- | ------------------------------ |
| Sélectionner le provider au runtime | Hardcoding dans Actions/Models |
| Routing par pays / devise / méthode | Logique métier                 |
| Résolution via container Laravel    | Couplage domaine ↔ PSP         |

```
SN + MOBILE_MONEY  →  KkiapayProvider
FR + CARD          →  StripeProvider
fallback            →  FakePaymentProvider (dev/test uniquement)
```

---

### PaymentEventData _(DTO canonique — Phase 2+)_

Objet unifié produit après normalisation. Tous les PSP produisent le même format :

```
eventId · eventType · providerTransactionId
providerStatus · amount · currency · metadata
```

> Le domaine ne manipule **jamais** de payload PSP brut.

---

### LedgerWriter / LedgerReader _(Phase 5+)_

| Composant      | Responsabilité                                           |
| -------------- | -------------------------------------------------------- |
| `LedgerWriter` | Crée les écritures double-entry dans `DB::transaction()` |
| `LedgerReader` | Calcule les balances via `SUM(ledger_entries)`           |

**Invariant absolu :**

```
∀ transaction : SUM(debits) = SUM(credits)
```

**Flux ledger par événement :**

| Événement         | Flow déclenché       |
| ----------------- | -------------------- |
| CHARGE COMPLETED  | `writeCharge`        |
| PAYOUT PENDING    | `writePayoutRelease` |
| PAYOUT COMPLETED  | `writePayoutPaid`    |
| PAYMENT_FEE créée | `writePaymentFee`    |
| REFUND COMPLETED  | `writeRefund`        |

---

### Job (Async)

| ✅ Autorisé                     | ❌ Interdit                     |
| ------------------------------- | ------------------------------- |
| Déléguer à une Action           | Logique métier complexe         |
| Porter `correlation_id`         | Modifier le domaine directement |
| Gérer retry / backoff / timeout | Dépendre de `request()`         |
| Logger erreurs + alerter        |                                 |

---

### Event / Listener

| ✅ Autorisé                      | ❌ Interdit                 |
| -------------------------------- | --------------------------- |
| Notifications, analytics, logs   | Logique métier critique     |
| Effets secondaires non critiques | Modification d'état central |

---

## 🔒 Règles financières

```
Transaction = source de vérité financière
Ledger      = vérité comptable double-entry (SUM debits = SUM credits)

PAYOUT ⊕ REFUND     (mutuellement exclusifs)
PAYOUT + FEE + REFUND ≤ CHARGE
LIVREE ≠ payout immédiat  (escrow 48h)

Montants : integer minor units (centimes) — float INTERDIT
```

---

## 🔄 Concurrence

Toutes les opérations critiques doivent :

- utiliser `DB::transaction()`
- utiliser `lockForUpdate()` avant écriture
- être idempotentes
- vérifier les invariants avant écriture

**Cas critiques :**
réservation capacité · confirmation booking · payout (escrow release) · refund · webhook · ouverture/résolution dispute

---

## 🔍 Observabilité

```
correlation_id = fil conducteur

HTTP → logs Laravel → Job → webhook_logs → transactions → audit_logs
```

---

## ❌ Interdictions globales

```
Logique métier dans Controller ou Policy
Accès DB dans Enum
Duplication Action / Service
Statuts hardcodés (string)
Service appelant une Action
Calcul financier hors Calculator
Absence d'idempotence sur flux critique
Modification d'une transaction finalisée
Payload PSP brut dans le domaine
Appel PSP concret hors infrastructure adapter
Provider hardcodé dans Actions / Controllers / Models
Balance matérialisée sur ledger_accounts
Écriture ledger hors DB::transaction()
Modification d'une LedgerEntry existante
Float / decimal pour money ou poids
Payout immédiat à LIVREE (bypass escrow)
Dispute ignorée lors de l'escrow release
```

---

## 📌 Règles fondamentales

```
Une Action     = un use case
Un Service     = logique transverse
Un Model       ≠ orchestrateur
Un Controller  ≠ logique métier

Webhook  → WebhookProcessor → PaymentEventData → Job → Action
PSP      → PaymentProviderResolver → PaymentProvider → DTOs
Ledger   → LedgerWriter (DB::transaction()) → LedgerReader (balances)
Escrow   → scheduler horaire → ReleaseEscrowBatch → guards invariants
```

---

## 🗂️ Références

| Domaine          | Fichier                                      |
| ---------------- | -------------------------------------------- |
| Booking / Escrow | `.adamas/domain/booking.md`                  |
| Dispute workflow | `.adamas/domain/dispute-strategy.md`         |
| Escrow lifecycle | `.adamas/domain/escrow-lifecycle.md`         |
| Ledger strategy  | `.adamas/domain/ledger-strategy.md`          |
| PSP Architecture | `.adamas/domain/psp-routing/Architecture.md` |
| PSP Invariants   | `.adamas/domain/psp-routing/Invariants.md`   |
| PSP Routing      | `.adamas/domain/psp-routing/Routing.md`      |
| Méthodes         | `.adamas/ai/engineering/method-rules.md`     |
| Standards code   | `.adamas/ai/engineering/standards.md`        |
| Checklist review | `.adamas/ai/governance/checklist.md`         |
