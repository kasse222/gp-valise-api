# 💰 Financial Rules — GP-Valise

> Toute règle financière violée = bug critique 🔴

---

## 🧠 Principe fondamental

```
Transaction = source de vérité financière
Ledger      = vérité comptable double-entry
```

| Objet         | Rôle                              |
| ------------- | --------------------------------- |
| `Payment`     | Vue métier / workflow utilisateur |
| `Transaction` | Réalité comptable atomique        |
| `LedgerEntry` | Écriture comptable double-entry   |

**Aucune décision financière ne doit dépendre uniquement du `Booking`.**

---

## 💱 Représentation monétaire

| ✅ Obligatoire                  | ❌ Interdit         |
| ------------------------------- | ------------------- |
| Integer minor units (centimes)  | float               |
| `1500 = 15.00€`                 | decimal métier      |
| Arithmetic entière déterministe | Calcul approximatif |

---

## 🧩 Types de transactions

| Type          | Sens métier             | Moment de création                 |
| ------------- | ----------------------- | ---------------------------------- |
| `CHARGE`      | Expéditeur → Plateforme | Paiement PSP                       |
| `PAYOUT`      | Plateforme → Voyageur   | Escrow release (après 48h)         |
| `FEE`         | Commission GP-Valise    | Avec le PAYOUT                     |
| `REFUND`      | Plateforme → Expéditeur | Webhook refund.completed           |
| `PAYMENT_FEE` | Frais PSP/bancaires     | Avec la CHARGE (estimé config MVP) |

> ⚠️ **La FEE est créée au moment du PAYOUT, pas à la CONFIRMEE ni à la LIVREE.**

---

## 🔒 Invariants financiers critiques

### 1. Conservation de valeur

```
PAYOUT + FEE + REFUND ≤ CHARGE
```

### 2. Exclusivité financière

```
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir simultanément un payout ET un refund.

### 3. Unicité des transactions

Pour un booking : une seule `CHARGE` · un seul `PAYOUT` · un seul `REFUND` · une seule `FEE`.

### 4. Immutabilité

```
COMPLETED ou FAILED = verrou définitif
```

Une transaction finalisée ne peut jamais être modifiée.
Toute correction passe par une **nouvelle transaction liée**.

### 5. Ledger double-entry

```
∀ transaction : SUM(debits) = SUM(credits)
```

### 6. Escrow consistency

```
LIVREE ≠ payout immédiat
```

Le payout devient éligible uniquement après :

- délai escrow écoulé (`escrow_releasable_at <= now()`)
- absence de dispute (`disputed_at IS NULL`)
- validation des guards `TransactionEligibilityService`

---

## 💸 Calculs financiers

**Centralisés dans `TransactionAmountCalculator`. Aucun calcul inline dans les Actions.**

| Formule       | Valeur                            |
| ------------- | --------------------------------- |
| `FEE`         | `CHARGE × fee_percentage`         |
| `PAYOUT`      | `CHARGE - FEE`                    |
| `REFUND`      | `CHARGE - FEE`                    |
| `PAYMENT_FEE` | `CHARGE × payment_fee_percentage` |
| `profit_net`  | `FEE - PAYMENT_FEE`               |

**Règles importantes :**

- `PAYMENT_FEE` ne réduit **pas** le payout voyageur
- `PAYMENT_FEE` ne réduit **pas** le refund (MVP)
- `FEE` calculée sur `CHARGE` brute (jamais sur le payout)
- Montants **persistés à la création**, jamais recalculés après coup

---

## 🔁 Flows transactionnels

### Flow normal

```
EN_PAIEMENT
  └─► CHARGE COMPLETED (webhook)
          └─► Booking CONFIRMEE
                  └─► Booking LIVREE (delivered_at + escrow_releasable_at)
                          └─► [scheduler 48h + guards]
                                  └─► PAYOUT + FEE créés
                                          └─► Booking TERMINE
```

### Flow refund standard

```
CONFIRMEE ou EN_LITIGE
  └─► RefundTransaction::execute()
          └─► REFUND PENDING
                  └─► webhook refund.completed
                          └─► REFUND COMPLETED
                                  └─► Booking REMBOURSEE
```

### Flow refund admin override

```
EN_LITIGE (aucun PAYOUT existant)
  └─► AdminRefundTransaction::execute()
          ├─► vérification guards
          ├─► REFUND créé
          ├─► AuditLog créé + seal() [même DB::transaction()]
          └─► webhook refund.completed → Booking REMBOURSEE
```

---

## 🔐 Refund admin override

**Conditions strictes :**

| Condition       | Valeur requise                            |
| --------------- | ----------------------------------------- |
| Rôle            | Admin uniquement                          |
| Statut booking  | `EN_LITIGE`                               |
| CHARGE          | `COMPLETED`                               |
| REFUND existant | ❌ Aucun                                  |
| PAYOUT existant | ❌ Aucun (invariant absolu)               |
| Raison          | Obligatoire (non vide)                    |
| Audit log       | Obligatoire dans même `DB::transaction()` |

```
Invariant absolu : aucun remboursement si payout existe
```

---

## 🏦 Ledger — Flows d'écritures

| Événement                | Débit                   | Crédit                              |
| ------------------------ | ----------------------- | ----------------------------------- |
| CHARGE COMPLETED         | `external_psp_clearing` | `escrow`                            |
| PAYOUT PENDING (release) | `escrow`                | `payable_voyageur` + `revenue_fees` |
| PAYOUT COMPLETED         | `payable_voyageur`      | `external_psp_clearing`             |
| PAYMENT_FEE              | `expense_psp`           | `external_psp_clearing`             |
| REFUND COMPLETED         | `escrow`                | `external_psp_clearing`             |

---

## 🔁 Idempotence

| Opération      | Protection                            |
| -------------- | ------------------------------------- |
| Double webhook | `event_id` UNIQUE + `lockForUpdate()` |
| Double charge  | Guard `TransactionEligibilityService` |
| Double payout  | Guard `TransactionEligibilityService` |
| Double refund  | Guard `TransactionEligibilityService` |
| Double fee     | Guard `hasExistingEntries`            |
| Double ledger  | Guard `hasExistingEntries`            |

---

## 🔄 Concurrence

```php
DB::transaction(function () {
    $booking->lockForUpdate()->first();
    // vérification invariants
    // écriture Transaction + LedgerEntries
});
```

---

## 🚫 Interdits

```
Calcul financier dans Controller / Model / Policy
Recalcul de montants après persistance
Modification d'une transaction finalisée
Bypass des Enums pour les statuts
Création de transaction sans validation métier
Payout immédiat à LIVREE (bypass escrow)
Float ou decimal pour les montants
Balance matérialisée sur ledger_accounts
Écriture ledger hors DB::transaction()
```

---

## 🧪 Tests obligatoires

- Double payout refusé
- Double refund refusé
- `PAYOUT ⊕ REFUND` impossible
- Refund après payout refusé
- Guards escrow : payout bloqué si `disputed_at IS NOT NULL`
- Guards escrow : payout bloqué si `escrow_releasable_at > now()`
- `SUM(debits) = SUM(credits)` après chaque flow ledger
- Idempotence webhook

---

## 🧠 Résumé exécutif

```
CHARGE      = entrée plateforme (source de vérité)
FEE         = revenu plateforme (créée au PAYOUT)
PAYMENT_FEE = coût PSP (estimé config MVP)
PAYOUT      = sortie voyageur (post-escrow 48h)
REFUND      = retour expéditeur (via webhook)

PAYOUT ⊕ REFUND
profit_net = FEE - PAYMENT_FEE
SUM(debits) = SUM(credits)
LIVREE ≠ payout immédiat
```
