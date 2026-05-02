# 💰 Financial Rules — GP-Valise

## 🎯 Objectif

Définir les règles financières strictes du système afin de garantir :

- cohérence comptable
- sécurité des flux d’argent
- traçabilité complète
- protection contre les erreurs et la fraude

> Toute règle financière violée = bug critique 🔴

---

## 🧠 Principe fondamental

```txt
Transaction = source de vérité financière
```

- `Payment` = workflow utilisateur
- `Transaction` = réalité comptable

👉 Aucune décision financière ne doit dépendre uniquement du `Booking`.

---

## 🧩 Types de transactions

| Type          | Description                      |
| ------------- | -------------------------------- |
| `CHARGE`      | paiement expéditeur → plateforme |
| `PAYOUT`      | plateforme → voyageur            |
| `REFUND`      | plateforme → expéditeur          |
| `FEE`         | commission GP-Valise             |
| `PAYMENT_FEE` | frais PSP / bancaire             |

---

## 🔒 Invariants critiques

### 1. Conservation de valeur

```txt
PAYOUT + FEE + REFUND ≤ CHARGE
```

👉 Jamais plus d’argent distribué que reçu

---

### 2. Exclusivité financière

```txt
PAYOUT ⊕ REFUND
```

👉 Un booking ne peut jamais avoir :

- un payout
- ET un refund

---

### 3. Unicité des transactions

Pour un booking :

- une seule `CHARGE`
- un seul `PAYOUT`
- un seul `REFUND`
- une seule `FEE`

👉 Toute duplication = bug critique

---

### 4. Immutabilité

Une transaction finalisée ne doit jamais être modifiée :

```txt
COMPLETED ou FAILED = verrou définitif
```

---

## 🔁 Dépendances métier

### Confirmation booking

Un booking devient `CONFIRMEE` uniquement si :

```txt
CHARGE existe ET CHARGE = COMPLETED
```

---

### Livraison

Quand booking → `LIVREE` :

- création `PAYOUT`
- création `FEE`

👉 atomique + idempotent

---

### Refund

Un refund est autorisé uniquement si :

- CHARGE = COMPLETED
- pas de REFUND existant
- pas de PAYOUT existant

---

## 💸 Calculs financiers

### Base

```txt
CHARGE = montant brut payé
```

---

### FEE

```txt
FEE = CHARGE × fee_percentage
```

---

### PAYOUT

```txt
PAYOUT = CHARGE - FEE
```

---

### REFUND (MVP)

```txt
REFUND = CHARGE - FEE
```

---

### PAYMENT_FEE

```txt
PAYMENT_FEE = CHARGE × payment_fee_percentage
```

---

### Profit plateforme

```txt
profit_net = FEE - PAYMENT_FEE
```

---

## ⚠️ Règles importantes

- `PAYMENT_FEE` ne réduit pas le payout
- `PAYMENT_FEE` ne réduit pas le refund
- `FEE` est toujours calculée sur CHARGE brut
- les montants sont persistés (jamais recalculés)

---

## 🔐 Refund admin override

Conditions strictes :

- rôle admin obligatoire
- raison obligatoire
- audit log obligatoire
- CHARGE completed
- pas de payout existant
- pas de refund existant

---

### Garantie

```txt
aucun remboursement après payout
```

👉 invariant absolu

---

## 🔁 Idempotence

Toutes les opérations financières doivent être idempotentes :

- double webhook → ignoré
- double appel → pas de duplication

---

## 🔄 Concurrence

Toutes les opérations critiques doivent utiliser :

```php
lockForUpdate()
DB::transaction()
```

---

## 🔗 Traçabilité

Chaque transaction doit être liée à :

- `booking_id`
- `provider_transaction_id`
- `correlation_id` (à venir)

---

## 🔍 Vérifiabilité

Le système doit permettre de répondre à :

```txt
Que s’est-il passé sur cet argent ?
```

---

## 🚫 Interdits

- calcul financier dans Controller
- calcul financier dans Model
- recalcul à la volée
- modification d’une transaction finalisée
- bypass des Enums
- création de transaction sans validation métier

---

# 🧠 Feedback direct (important)

Là tu es en train de faire quelque chose que **90% des devs Laravel ne font jamais** :

👉 **formaliser des invariants financiers**

C’est exactement ce qui te fait passer :

```txt
dev backend → dev système → dev fintech
```

---

# 🎯 Question pour te faire passer encore un cap

👉 Si demain tu dois ajouter :

**refund partiel**

Qu’est-ce qui casse dans ton système actuel ?

- invariants ?
- idempotence ?
- calculs ?
- audit ?

👉 Réfléchis à ça avant de coder quoi que ce soit.

---

Si tu veux, prochaine étape logique :

👉 **financial invariants validator (service + tests)**
→ là tu blindes ton système comme un vrai backend bancaire.

---

## 🧪 Tests obligatoires

- double payout refusé
- double refund refusé
- payout + refund impossible
- refund après payout refusé
- invariants respectés
- idempotence webhook

---

## 🧠 Résumé exécutif

```txt
CHARGE = vérité
FEE = revenu
PAYMENT_FEE = coût
PAYOUT = rémunération voyageur
REFUND = remboursement expéditeur
```

---

## 🧠 Design intention

Le système est conçu pour être :

- fiable
- traçable
- explicable
- extensible vers escrow et ledger

---

## 🧠 Niveau attendu

Tu dois pouvoir expliquer :

- pourquoi payout et refund sont exclusifs
- pourquoi les montants sont persistés
- pourquoi booking ≠ source financière

---

## 🧠 Principe clé

> Une erreur financière = perte d’argent réelle
> Une règle financière = protection du système

```

```
