# 💳 Payment Logic — GP-Valise

---

## 🎯 Objectif

Définir le comportement financier transactionnel de GP-Valise.

Le système gère : `CHARGE` · `PAYOUT` · `REFUND` · `FEE` · `PAYMENT_FEE`

avec : cohérence transactionnelle · idempotence · escrow · async/webhooks · multi-PSP · ledger-compatible

---

## 🧠 Principe fondamental

> Transaction = événement financier persisté
> Payment = workflow métier utilisateur

Les décisions financières dépendent :

- des Transactions
- des invariants métier
- des règles escrow
- des guards treasury

**Jamais** du seul statut Booking ou Payment.

---

## 🧩 Types de transactions

| Type          | Sens métier             |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → plateforme |
| `PAYOUT`      | Plateforme → voyageur   |
| `REFUND`      | Plateforme → expéditeur |
| `FEE`         | Commission plateforme   |
| `PAYMENT_FEE` | Frais PSP/bancaires     |

---

## 🔁 Flow financier global

```txt
CHARGE       = 10000   expéditeur → plateforme
FEE          = 1000    revenu GP-Valise (10%)
PAYMENT_FEE  = 200     coût PSP (2%)
PAYOUT       = 9000    plateforme → voyageur
profit_net   = 800     FEE - PAYMENT_FEE
```

---

## 💰 Représentation monétaire

**Règle absolue : integer minor units — float INTERDIT**

```txt
1500 = 15.00€
```

- ✅ integer arithmetic · deterministic computation
- ❌ float · decimal métier · calcul approximatif

---

## 🔒 Invariants financiers

```txt
1. PAYOUT ⊕ REFUND          (mutuellement exclusifs)
2. PAYOUT + FEE + REFUND ≤ CHARGE
3. profit_net = FEE - PAYMENT_FEE
4. LIVREE ≠ payout immédiat  (escrow 48h obligatoire)
```

---

## 🏦 Treasury Ownership

| Transaction   | Flux                                               |
| ------------- | -------------------------------------------------- |
| `CHARGE`      | Expéditeur → plateforme (escrow pendant livraison) |
| `PAYOUT`      | Plateforme → voyageur                              |
| `REFUND`      | Plateforme → expéditeur                            |
| `FEE`         | Revenu plateforme                                  |
| `PAYMENT_FEE` | Coût PSP/bancaire                                  |

---

## 🔗 Relations Transaction

| Champ                     | Description             |
| ------------------------- | ----------------------- |
| `booking_id`              | Réservation concernée   |
| `user_id`                 | Acteur métier           |
| `platform_account_id`     | Compte treasury         |
| `provider_transaction_id` | Identifiant PSP externe |
| `correlation_id`          | Traçabilité end-to-end  |

Préparation future : `parent_transaction_id` · `ledger_entry_id`

---

## 🔁 Cycle de vie transactionnel

```txt
PENDING → COMPLETED
        → FAILED
```

Une transaction finalisée est **immuable** — toute correction = nouvelle transaction liée.

---

## 🧮 Calculs financiers

**Règle absolue : aucun calcul dans une Action — tout via `TransactionAmountCalculator`**

```txt
FEE         = CHARGE × fee_percentage
PAYOUT      = CHARGE - FEE
REFUND      = CHARGE - FEE
PAYMENT_FEE = CHARGE × payment_fee_percentage
```

Config :

```env
GPVALISE_FEE_PERCENTAGE=10
GPVALISE_PAYMENT_FEE_PERCENTAGE=2
```

Règles :

- PAYMENT_FEE ne réduit pas le payout
- PAYMENT_FEE ne réduit pas le refund MVP
- FEE calculée sur CHARGE brute
- Montants persistés à la création — aucun recalcul post-persistence

---

## 🧱 Responsabilités par composant

| Composant                       | Responsabilité        |
| ------------------------------- | --------------------- |
| `TransactionAmountCalculator`   | Calcul financier      |
| `FeeCalculator`                 | Résolution du taux    |
| `TransactionEligibilityService` | Guards payout/refund  |
| `CreatePayoutTransaction`       | Création payout + fee |
| `RefundTransaction`             | Refund standard       |
| `AdminRefundTransaction`        | Refund admin          |
| `PaymentProvider`               | Communication PSP     |

---

## 💸 FEE — Commission plateforme

- Calculée depuis CHARGE brute
- Créée **une seule fois** au payout release
- Indépendante du provider

```txt
LIVREE → escrow pending → payout release → PAYOUT + FEE
```

**La FEE n'est pas créée à LIVREE.**

---

## 🏦 PAYMENT_FEE

- Persistée en base dès MVP
- Coût plateforme uniquement
- Indépendante du payout et du refund MVP

Moment de création :

```txt
Idéal   : webhook charge.completed
Fallback: estimation config
```

---

## 🔁 Escrow

```txt
CONFIRMEE → LIVREE → escrow pending → payout releasable → PAYOUT + FEE
```

Conditions payout :

```txt
booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND CHARGE COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS
```

Le payout est déclenché via **scheduler horaire** (`escrow:release-payouts`) — jamais via événement livraison direct.

Config : `GPVALISE_ESCROW_DELAY_HOURS=48`

---

## 🔁 Refund

Le refund est toujours une **transaction explicite** — jamais implicite, jamais déduite d'un statut.

### Refund standard

- Booking CONFIRMEE ou EN_LITIGE
- CHARGE COMPLETED
- Aucun refund existant
- Aucun payout existant

### Refund admin override

- Admin uniquement
- Booking EN_LITIGE
- Aucun payout COMPLETED existant
- Raison obligatoire
- Audit log obligatoire dans la même `DB::transaction()`

**Invariant absolu : jamais de refund si payout COMPLETED existe.**

---

## 🔁 Async & Webhooks

```txt
Provider
→ WebhookController (HMAC verification)
→ WebhookProcessor (normalizeWebhook → PaymentEventData)
→ ProcessPaymentWebhook Job (queue: high)
→ HandlePaymentWebhook Action
→ Transaction + Booking + WebhookLog + LedgerEntry
```

Garanties :

- Idempotence via `event_id` + `lockForUpdate()`
- `DB::transaction()` obligatoire
- Retry avec backoff exponentiel
- Webhook = source primaire de signal financier async

### Events MVP

| Event                 | Effets                                |
| --------------------- | ------------------------------------- |
| `transaction.success` | CHARGE COMPLETED + Booking CONFIRMEE  |
| `transaction.failed`  | CHARGE FAILED                         |
| `refund.completed`    | REFUND COMPLETED + Booking REMBOURSEE |
| `refund.failed`       | REFUND FAILED                         |
| autres                | ignorés                               |

---

## 🔍 Traçabilité financière

```txt
HTTP request
→ X-Correlation-ID
→ logs Laravel
→ Job
→ webhook_logs.correlation_id
→ transactions.correlation_id
→ audit_logs.correlation_id
```

---

## 🔐 Concurrence

Toutes les opérations critiques :

- `DB::transaction()`
- `lockForUpdate()`
- Vérification invariants avant écriture
- Idempotence obligatoire

---

## 🌍 PSP actif en production

| Corridor | Provider     | Méthode      | Env     | Statut           |
| -------- | ------------ | ------------ | ------- | ---------------- |
| SN       | PayDunya     | Mobile Money | Sandbox | ✅ validé        |
| BJ / CI  | Kkiapay      | Mobile Money | —       | ⏳ Phase 7       |
| MA / FR  | Stripe       | Card         | —       | ⏳ Phase 7       |
| fallback | FakeProvider | —            | Dev     | ❌ prod interdit |

Flow end-to-end validé (PayDunya) :

```txt
POST /api/v1/bookings/{id}/pay
→ PayDunyaProvider::charge()
→ appel API PayDunya sandbox
→ token + checkout_url retournés
→ redirect browser → page checkout PayDunya
→ Transaction PENDING créée
→ webhook IPN → CHARGE COMPLETED → Booking CONFIRMEE
```

**Limitation sandbox :** token expire en quelques secondes — comportement normal sandbox PayDunya, pas un bug backend.

**Cadrage entretien :**

> "L'intégration PSP est validée techniquement end-to-end.
> Nous sommes en sandbox en attente de validation KYC pour basculer en production."

Prochaines étapes :

- [ ] KYC PayDunya validé → clés production
- [ ] `PAYDUNYA_MODE=live`
- [ ] Kkiapay sandbox (BJ/CI) — Phase 7
- [ ] Stripe sandbox (MA/FR) — Phase 7

---

## 🚫 Anti-patterns interdits

```txt
Calcul financier dans une Action
Dépendre uniquement du statut Booking
Modifier une transaction finalisée
Bypass escrow
Ignorer idempotence webhook
Coupler domaine ↔ PSP
Utiliser float pour money
Payout immédiat à LIVREE
Dispute ignorée lors escrow release
```

---

## 🧠 Résumé exécutif

```txt
CHARGE      = entrée plateforme
PAYOUT      = sortie voyageur
REFUND      = retour expéditeur
FEE         = revenu plateforme
PAYMENT_FEE = coût PSP

PAYOUT ⊕ REFUND
profit_net = FEE - PAYMENT_FEE
```

> La priorité absolue est la cohérence transactionnelle.
> Le système est déterministe · audit-friendly · escrow-aware · provider-isolated · ledger-compatible.
