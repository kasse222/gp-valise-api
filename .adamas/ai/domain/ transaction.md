````md id="q4w9h2"
# 💳 Transaction Domain — GP-Valise

---

# 🎯 Objectif

Ce document définit le modèle transactionnel de GP-Valise.

La transaction représente :

```txt
les événements financiers persistés du système
```
````

Elle garantit :

- traçabilité complète
- cohérence transactionnelle
- auditabilité
- compatibilité async/webhooks
- extensibilité ledger-compatible

---

# 🧠 Principe fondamental

> Transaction = événement financier métier persisté
> Payment = vue métier utilisateur

Aucune décision financière ne doit dépendre uniquement :

- du statut Booking
- du statut Payment

Les décisions financières dépendent toujours :

- des Transactions
- des invariants métier
- de l’état escrow
- du treasury routing

---

# 🧩 Types de transactions

| Type          | Description             |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → plateforme |
| `PAYOUT`      | Plateforme → voyageur   |
| `REFUND`      | Plateforme → expéditeur |
| `FEE`         | Commission plateforme   |
| `PAYMENT_FEE` | Frais PSP/bancaires     |

---

# 🔁 Cycle de vie d’une transaction

```txt id="wzj5ht"
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

```txt id="84ww80"
une nouvelle transaction liée
```

et jamais par mutation directe.

---

# 🔒 Invariants financiers

---

## 1. Exclusivité financière

```txt id="mjlwmg"
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir :

- payout ET refund

---

## 2. Conservation de valeur

```txt id="nt6u7w"
PAYOUT + FEE + REFUND ≤ CHARGE
```

---

## 3. Profit plateforme

```txt id="jlwm7z"
profit_net = FEE - PAYMENT_FEE
```

---

## 4. Escrow consistency

```txt id="jlwmg5"
LIVREE ≠ payout immédiat
```

Le payout devient éligible uniquement après :

- délai escrow écoulé
- absence de dispute
- validation des guards financiers

---

# 🔗 Relations

Une transaction est liée à :

| Champ                     | Description             |
| ------------------------- | ----------------------- |
| `booking_id`              | réservation concernée   |
| `user_id`                 | acteur financier        |
| `platform_account_id`     | compte treasury interne |
| `provider_transaction_id` | identifiant PSP externe |

Préparation future :

| Champ futur             | Objectif                   |
| ----------------------- | -------------------------- |
| `parent_transaction_id` | linked transactions        |
| `ledger_entry_id`       | comptabilité double entrée |

---

# 🏦 Treasury Ownership

## CHARGE

```txt id="jlwmwe"
expéditeur → plateforme
```

Les fonds sont détenus temporairement par la plateforme.

---

## PAYOUT

```txt id="jlwmg9"
plateforme → voyageur
```

---

## REFUND

```txt id="jlwm8m"
plateforme → expéditeur
```

---

## FEE

```txt id="jlwmvs"
revenu plateforme
```

---

## PAYMENT_FEE

```txt id="jjlwmz"
coût PSP / bancaire
```

---

# 💰 Représentation monétaire

## Canonical unit

```txt id="jlwm8w"
minor integer units
```

Exemple :

```txt id="jlwm6y"
1500 = 15.00€
```

---

## Interdits

- float
- decimal métier
- calcul approximatif

---

## Autorisés

- integer arithmetic
- deterministic computation

---

# 💰 Calculs financiers

Centralisés dans :

```php id="jlwmr7"
TransactionAmountCalculator
```

---

# 📐 Formules MVP

## Fee

```txt id="jlwm1t"
FEE = CHARGE * fee_percentage
```

---

## Payout

```txt id="jlwmz2"
PAYOUT = CHARGE - FEE
```

---

## Refund

```txt id="jlwm4x"
REFUND = CHARGE - FEE
```

### Règle MVP

```txt id="jlwmv8"
La commission plateforme n’est pas remboursée.
```

---

## Payment fee

```txt id="jlwm8v"
PAYMENT_FEE = CHARGE * payment_fee_percentage
```

---

# ⚠️ Règles critiques

- aucun calcul financier dans les Actions
- aucun recalcul après persistance
- montants figés à la création
- PAYMENT_FEE n’impacte pas payout/refund dans le MVP

---

# 🔁 Flows transactionnels

---

## 1. CHARGE

Créée lors du paiement :

```txt id="jlwm9v"
EN_PAIEMENT
→ CHARGE
→ COMPLETED
```

Conditions :

- booking appartient à l’utilisateur
- booking non expiré
- aucune charge existante

---

## 2. PAYOUT

Le payout n’est plus immédiat.

Flow :

```txt id="jlwm4l"
LIVREE
→ escrow pending
→ payout releasable
→ PAYOUT + FEE
```

Conditions :

```txt id="jlwm8l"
booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND charge COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS
```

Le payout est déclenché par :

```txt id="jlwmr3"
scheduler escrow
```

et non directement par l’événement livraison.

---

## 3. REFUND (standard)

Conditions :

- booking confirmé ou en litige
- charge complétée
- aucun refund existant
- aucun payout existant

---

## 4. REFUND (admin override)

Conditions :

- admin uniquement
- booking en litige
- aucun payout existant
- raison obligatoire
- audit log obligatoire

---

# 🔄 Idempotence

Objectifs :

- éviter double charge
- éviter double payout
- éviter double refund

---

# 🛡️ Stratégies idempotence

- contraintes métier
- vérification DB
- `lockForUpdate()`
- statuts finaux comme verrou logique

---

# 🔁 Async & Webhooks

## Flow

```txt id="jlwm6j"
Webhook
→ Controller
→ Job
→ Action
→ Transaction
```

---

# Garanties

- idempotence via `event_id`
- DB transaction obligatoire
- retry contrôlé
- webhook logs complets

---

# 🔐 Concurrence

Toutes les opérations critiques doivent :

- utiliser transaction DB
- utiliser `lockForUpdate()`
- vérifier les invariants métier
- rester idempotentes

---

# 🧾 Audit & traçabilité

Chaque transaction critique doit être corrélée avec :

- audit_logs
- webhook_logs
- application logs
- correlation_id

---

# 🔍 Observabilité

Objectif :

```txt id="jlwm9q"
retrouver l’origine exacte d’un événement financier
```

Une transaction doit être traçable :

- requête
- webhook
- job
- transition métier
- écriture DB

---

# 🏗️ Ledger Compatibility

Le système est progressivement aligné vers :

```txt id="jlwm5h"
un modèle ledger-compatible
```

Préparations déjà présentes :

- PlatformAccount
- integer units
- PostgreSQL
- treasury routing
- escrow layer
- immutable transactions

---

# 🔮 Extensions futures

- parent_transaction_id
- linked transactions
- double-entry accounting
- reconciliation engine
- reserve balances
- suspense accounts
- settlement batching
- dispute compensation
- multi-currency treasury

---

# ⚠️ Anti-patterns interdits

- calcul financier dans Actions
- dépendre uniquement du statut Booking
- modifier transaction finalisée
- bypass escrow
- ignorer idempotence
- bypass audit admin
- coupler Transaction avec PSP
- utiliser float pour money

---

# 🧠 Résumé exécutif

```txt id="jlwmv4"
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

Le système transactionnel GP-Valise est conçu pour être :

- robuste
- déterministe
- audit-friendly
- async-compatible
- escrow-aware
- treasury-oriented
- extensible ledger-compatible

> La priorité absolue est la cohérence transactionnelle.

```

```
