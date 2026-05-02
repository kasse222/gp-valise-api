# 💳 Transaction Domain — GP-Valise

## 🎯 Objectif

Ce document définit le modèle transactionnel de GP-Valise.

La transaction est la **source de vérité financière absolue** du système.

Elle garantit :

- traçabilité complète ;
- cohérence comptable ;
- auditabilité ;
- compatibilité async (webhooks) ;
- extensibilité vers un ledger complet.

---

## 🧠 Principe fondamental

> Transaction = vérité financière  
> Payment = vue métier

Aucune décision financière ne doit dépendre uniquement du statut Booking ou Payment.

---

## 🧩 Types de transactions

| Type          | Description             |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → plateforme |
| `PAYOUT`      | Plateforme → voyageur   |
| `REFUND`      | Plateforme → expéditeur |
| `FEE`         | Commission GP-Valise    |
| `PAYMENT_FEE` | Frais PSP / bancaires   |

---

## 🔁 Cycle de vie d’une transaction

```txt
PENDING → COMPLETED
        → FAILED
```

### Règles

- une transaction finalisée est immuable ;
- aucune transition inverse autorisée ;
- une transaction ne peut être traitée qu’une seule fois ;
- toute transition doit être atomique.

---

## 🔒 Invariants financiers

### Exclusivité

```txt
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir :

- un payout ET un refund.

---

### Conservation de la valeur

```txt
PAYOUT + FEE + REFUND ≤ CHARGE
```

---

### Profit plateforme

```txt
profit_net = FEE - PAYMENT_FEE
```

---

## 🔗 Relations

Une transaction est liée à :

- `booking_id` (obligatoire)
- `user_id` (acteur du mouvement)
- `provider_transaction_id` (si externe)

Option future :

- `parent_transaction_id` (ledger complet)

---

## 💰 Calculs financiers

Centralisés dans :

```php
TransactionAmountCalculator
```

### Formules MVP

```txt
FEE         = CHARGE * fee_percentage
PAYOUT      = CHARGE - FEE
REFUND      = CHARGE - FEE
PAYMENT_FEE = CHARGE * payment_fee_percentage
```

---

## ⚠️ Règles critiques

- aucun calcul dans les Actions ;
- aucun recalcul après persistance ;
- tous les montants sont figés à la création ;
- PAYMENT_FEE n’impacte pas payout ni refund (MVP).

---

## 🔁 Flows transactionnels

### 1. CHARGE

Créée lors du paiement :

```txt
EN_PAIEMENT → CHARGE → COMPLETED
```

Conditions :

- booking appartient à l’utilisateur ;
- booking non expiré ;
- aucune autre charge existante.

---

### 2. PAYOUT

Créé lors de la livraison :

```txt
LIVREE → PAYOUT + FEE
```

Conditions :

- booking livré ;
- charge complétée ;
- aucun payout existant ;
- aucun refund existant ;
- aucune fee existante.

---

### 3. REFUND (standard)

Conditions :

- booking confirmé ou en litige ;
- charge complétée ;
- aucun refund existant ;
- aucun payout existant.

---

### 4. REFUND (admin override)

Conditions :

- admin uniquement ;
- booking en litige ou livré ;
- aucun payout existant ;
- raison obligatoire ;
- audit log obligatoire.

---

## 🔄 Idempotence

### Objectif

Éviter :

- double charge ;
- double payout ;
- double refund.

### Stratégies

- contraintes métier ;
- vérification préalable en DB ;
- `lockForUpdate()` ;
- statut final comme verrou logique.

---

## 🔁 Async & Webhooks

### Flow

```txt
Webhook → Controller → Job → Action → Transaction
```

### Garanties

- idempotence via `event_id` ;
- transaction DB ;
- retry contrôlé ;
- log complet via `webhook_logs`.

---

## 🔐 Concurrence

Toutes les opérations critiques doivent :

- utiliser une transaction DB ;
- utiliser `lockForUpdate()` ;
- vérifier les invariants métier ;
- être idempotentes.

---

## 🧾 Audit & traçabilité

Chaque transaction critique doit être traçable via :

- audit_logs ;
- webhook_logs ;
- logs applicatifs ;
- correlation_id.

---

## 🔍 Observabilité

Chaque transaction doit être corrélée avec :

- correlation_id (requête → job → DB) ;
- logs Laravel ;
- webhook events.

Objectif :

```txt
retrouver l’origine exacte d’une transaction
```

---

## 🏗️ Extension future (Ledger)

Préparation pour :

- `parent_transaction_id`
- double-entry accounting
- comptes plateforme multi-devise
- journaux financiers complets

---

## ⚠️ Anti-patterns interdits

- calcul financier dans une Action ;
- dépendre du statut Booking pour une décision financière ;
- créer une transaction sans vérifier l’existant ;
- modifier une transaction finalisée ;
- ignorer l’idempotence ;
- bypass audit pour les actions admin ;
- coupler Transaction avec le provider directement.

---

## 🧠 Résumé exécutif

```txt
CHARGE      = source de vérité
FEE         = revenu plateforme
PAYMENT_FEE = coût externe
PAYOUT      = paiement voyageur
REFUND      = remboursement utilisateur

PAYOUT ⊕ REFUND
profit_net = FEE - PAYMENT_FEE
```

---

## 🧠 Design intention

Le système transactionnel de GP-Valise est conçu pour être :

- simple en MVP ;
- strict sur les invariants ;
- robuste face à la concurrence ;
- compatible async ;
- extensible vers un système fintech complet.

> La priorité est la cohérence et la traçabilité, pas la complexité prématurée.

```

```
