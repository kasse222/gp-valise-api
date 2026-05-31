````md id="t8x4m1"
# 💳 Payment Logic — GP-Valise

---

# 🎯 Objectif

Définir le comportement financier transactionnel de GP-Valise.

Le système gère :

- `CHARGE`
- `PAYOUT`
- `REFUND`
- `FEE`
- `PAYMENT_FEE`

avec :

- cohérence transactionnelle
- idempotence
- escrow
- compatibilité async/webhooks
- compatibilité multi-PSP
- extensibilité treasury / ledger

---

# 🧠 Principe fondamental

> Transaction = événement financier persisté  
> Payment = workflow métier utilisateur

Les décisions financières ne doivent jamais dépendre uniquement :

- du statut Booking
- du statut Payment

Elles dépendent :

- des Transactions
- des invariants métier
- des règles escrow
- des guards treasury

---

# 🧩 Types de transactions

| Type          | Sens métier             |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → plateforme |
| `PAYOUT`      | Plateforme → voyageur   |
| `REFUND`      | Plateforme → expéditeur |
| `FEE`         | Commission plateforme   |
| `PAYMENT_FEE` | Frais PSP/bancaires     |

---

# 🔁 Flow financier global

```txt id="t8f2j7"
CHARGE       = 10000   expéditeur → plateforme
FEE          = 1000    revenu GP-Valise (10%)
PAYMENT_FEE  = 200     coût PSP (2%)
PAYOUT       = 9000    plateforme → voyageur
profit_net   = 800     FEE - PAYMENT_FEE
```
````

---

# 💰 Représentation monétaire

## Canonical unit

```txt id="r5n7q2"
minor integer units
```

Exemple :

```txt id="c2k8z9"
1500 = 15.00€
```

---

# 🚫 Interdits

- float
- decimal métier
- calcul approximatif

---

# ✅ Autorisés

- integer arithmetic
- deterministic computation

---

# 🔒 Invariants financiers

---

## 1. Exclusivité financière

```txt id="q1d6m4"
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir :

- payout ET refund

---

## 2. Conservation de valeur

```txt id="y3k8b1"
PAYOUT + FEE + REFUND ≤ CHARGE
```

---

## 3. Profit plateforme

```txt id="v6x9h2"
profit_net = FEE - PAYMENT_FEE
```

---

## 4. Escrow consistency

```txt id="f4j7p2"
LIVREE ≠ payout immédiat
```

Le payout devient éligible uniquement après :

- expiration escrow
- absence de dispute
- validation des guards financiers

---

# 🏦 Treasury Ownership

## CHARGE

```txt id="j7r3v1"
expéditeur → plateforme
```

Les fonds restent détenus par la plateforme durant l’escrow.

---

## PAYOUT

```txt id="x5m1w8"
plateforme → voyageur
```

---

## REFUND

```txt id="q2t6p4"
plateforme → expéditeur
```

---

## FEE

```txt id="s8k3n7"
revenu plateforme
```

---

## PAYMENT_FEE

```txt id="h4d9m2"
coût PSP/bancaire
```

---

# 🔗 Relations

Une transaction est liée à :

| Champ                     | Description             |
| ------------------------- | ----------------------- |
| `booking_id`              | réservation concernée   |
| `user_id`                 | acteur métier           |
| `platform_account_id`     | compte treasury         |
| `provider_transaction_id` | identifiant PSP externe |

Préparation future :

| Champ futur             | Objectif                   |
| ----------------------- | -------------------------- |
| `parent_transaction_id` | linked transactions        |
| `ledger_entry_id`       | comptabilité double entrée |

---

# 🔁 Cycle de vie transactionnel

```txt id="g3m9t5"
PENDING
→ COMPLETED
→ FAILED
```

---

# 🔒 Règles de cycle de vie

Une transaction finalisée :

- est immuable
- ne peut pas être modifiée
- ne peut pas revenir à `PENDING`

Toute correction future doit passer par :

```txt id="n8r2x6"
une nouvelle transaction liée
```

---

# 🧮 TransactionAmountCalculator

## Objectif

Centraliser tous les calculs financiers.

Les Actions :

```txt id="v7f1q8"
orchestrent
```

Le calculator :

```txt id="m5k2z1"
calcule
```

---

# ⚠️ Règle absolue

Aucun calcul financier ne doit être écrit directement dans une Action.

---

# 📐 Formules MVP

## Fee

```txt id="c9d4h7"
FEE = CHARGE * fee_percentage
```

---

## Payout

```txt id="b2x6m9"
PAYOUT = CHARGE - FEE
```

---

## Refund

```txt id="k4r8n3"
REFUND = CHARGE - FEE
```

---

## Payment fee

```txt id="t1m5w7"
PAYMENT_FEE = CHARGE * payment_fee_percentage
```

---

# ⚠️ Règles importantes

- PAYMENT_FEE ne réduit pas le payout
- PAYMENT_FEE ne réduit pas le refund MVP
- FEE calculée sur CHARGE brute
- montants persistés à la création
- aucun recalcul post-persistence

---

# ⚙️ Configuration MVP

```env id="r9x1p4"
GPVALISE_FEE_PERCENTAGE=10
GPVALISE_PAYMENT_FEE_PERCENTAGE=2
```

---

# 🧱 Responsabilités par composant

| Composant                       | Responsabilité        |
| ------------------------------- | --------------------- |
| `TransactionAmountCalculator`   | calcul financier      |
| `FeeCalculator`                 | résolution du taux    |
| `TransactionEligibilityService` | guards payout/refund  |
| `CreatePayoutTransaction`       | création payout + fee |
| `RefundTransaction`             | refund standard       |
| `AdminRefundTransaction`        | refund admin          |
| `PaymentProvider`               | communication PSP     |

---

# 💸 FEE — Commission plateforme

---

# Règles

- calculée depuis CHARGE brute
- créée une seule fois
- indépendante du provider
- créée au payout release

---

# ⚠️ Important

La FEE n’est plus créée lors du passage à `LIVREE`.

Flow réel :

```txt id="d6j8w2"
LIVREE
→ escrow pending
→ payout release
→ PAYOUT + FEE
```

---

# 🏦 PAYMENT_FEE

---

# Règles

- persistée en base
- coût plateforme uniquement
- indépendante du payout
- indépendante du refund MVP

---

# Moment de création

```txt id="y5q8m1"
Idéal :
webhook charge.completed

Fallback MVP :
estimation config
```

---

# 🔁 Escrow Integration

## Nouveau modèle

```txt id="m2v7k4"
delivery event ≠ payout event
```

---

# Flow escrow

```txt id="q8x1d5"
CONFIRMEE
→ LIVREE
→ escrow pending
→ payout releasable
→ PAYOUT + FEE
```

---

# Conditions payout

```txt id="w3m7t1"
booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND charge COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS
```

---

# Scheduler

Le payout est déclenché via :

```txt id="v9r4k6"
scheduler escrow
```

et non via l’événement livraison.

---

# 🔁 Refund

---

# Principe

Le refund est toujours :

```txt id="s7d2x8"
une transaction explicite
```

Jamais :

- implicite
- déduite d’un statut

---

# Refund standard

Conditions :

- booking CONFIRMEE ou EN_LITIGE
- CHARGE COMPLETED
- aucun refund existant
- aucun payout existant

---

# Refund admin override

Conditions :

- admin uniquement
- booking EN_LITIGE
- aucun payout existant
- raison obligatoire
- audit log obligatoire

---

# Invariant absolu

```txt id="n1w6p9"
jamais de refund si payout existe
```

---

# 🔍 Traçabilité financière

Chaque transaction critique doit être traçable via :

- transactions
- webhook_logs
- audit_logs
- correlation_id
- application logs

---

# 🔐 Concurrence

Toutes les opérations critiques doivent :

- utiliser transaction DB
- utiliser `lockForUpdate()`
- vérifier les invariants métier
- rester idempotentes

---

# 🔁 Async & Webhooks

## Flow

```txt id="x4k7m2"
Provider
→ WebhookController
→ verification
→ Job
→ Action
→ Transaction update
→ Booking update
→ WebhookLog
```

---

# Garanties

- idempotence via `event_id`
- transaction DB obligatoire
- retry contrôlé
- webhook logs complets

---

# Webhook Priority

Les webhooks restent :

```txt id="b7m3r1"
la source primaire de signal financier async
```

Les réponses PSP optimistes ne suffisent jamais.

---

# 🔄 Events MVP

| Event                 | Effets                                |
| --------------------- | ------------------------------------- |
| `transaction.success` | CHARGE COMPLETED + Booking CONFIRMEE  |
| `transaction.failed`  | CHARGE FAILED                         |
| `refund.completed`    | REFUND COMPLETED + Booking REMBOURSEE |
| `refund.failed`       | REFUND FAILED                         |
| autres events         | ignored                               |

---

# 🛡️ Garanties système

- CHARGE obligatoire avant confirmation
- pas de double payout/refund/fee
- payout/refund mutuellement exclusifs
- idempotence webhook
- auditabilité complète
- persistence immuable

---

# 🏗️ Ledger Compatibility

Le système est progressivement aligné vers :

```txt id="z6t1k8"
un modèle ledger-compatible
```

Préparations déjà présentes :

- PlatformAccount
- integer units
- PostgreSQL
- escrow layer
- immutable transactions
- treasury routing

---

# 🔮 Extensions futures

- double-entry accounting
- reconciliation engine
- reserve balances
- suspense accounts
- dispute compensation
- settlement batching
- provider failover
- partial refunds
- multi-currency treasury

---

# 🚫 Anti-patterns interdits

- calcul financier dans Actions
- dépendre uniquement du statut Booking
- modifier transaction finalisée
- bypass escrow
- ignorer idempotence
- coupler domaine ↔ PSP
- utiliser float pour money

---

# 🧠 Résumé exécutif

```txt id="g8v2k5"
CHARGE      = entrée plateforme
PAYOUT      = sortie voyageur
REFUND      = retour expéditeur
FEE         = revenu plateforme
PAYMENT_FEE = coût PSP

PAYOUT ⊕ REFUND
profit_net = FEE - PAYMENT_FEE
```

---

# 🧠 Design intention

Le système financier GP-Valise est conçu pour être :

- déterministe
- audit-friendly
- escrow-aware
- treasury-oriented
- async-compatible
- provider-isolated
- extensible ledger-compatible

> La priorité absolue est la cohérence transactionnelle.

# PSP actif en production (sandbox)

| Corridor | Provider     | Méthode      | Statut           |
| -------- | ------------ | ------------ | ---------------- |
| SN       | PayDunya     | Mobile Money | ✅ sandbox actif |
| BJ / CI  | Kkiapay      | Mobile Money | ⏳ à configurer  |
| MA / FR  | Stripe       | Card         | ⏳ à configurer  |
| fallback | FakeProvider | —            | ❌ prod interdit |

Flow validé end-to-end :

```txt
POST /api/v1/bookings/{id}/pay
→ PayDunyaProvider::charge()
→ token + checkout_url
→ redirect browser → PayDunya sandbox
```

```

```
