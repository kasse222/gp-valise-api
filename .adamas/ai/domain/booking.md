# 🧠 Booking Domain — GP-Valise

````md
# 🧠 Booking Domain — GP-Valise

---

## 🎯 Objectif

Le Booking est l’entité centrale du système GP-Valise.

Il représente :

- une **réservation logistique**
- un **engagement financier**
- un **cycle métier complet**

Il garantit la cohérence entre :

```txt
Expéditeur ↔ Trip ↔ Luggage ↔ Transaction
```
````

---

## 🧠 Principe fondamental

> Le Booking est le pivot métier.

- Il orchestre la logistique (Trip, Luggage)
- Il dépend de la finance (Transaction)
- Il ne contient pas la vérité financière

---

## 🧱 Structure

### Relations principales

| Relation          | Description                |
| ----------------- | -------------------------- |
| `user_id`         | expéditeur                 |
| `trip_id`         | trajet associé             |
| `bookingItems`    | bagages réservés           |
| `transactions`    | flux financiers            |
| `statusHistories` | historique des transitions |

---

## 🔁 Cycle de vie

### Flow principal

```txt
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ LIVREE
→ TERMINE
```

---

### États alternatifs

| Statut     | Description               |
| ---------- | ------------------------- |
| EXPIREE    | paiement non effectué     |
| ANNULE     | annulé avant confirmation |
| REMBOURSEE | refund effectué           |
| EN_LITIGE  | problème déclaré          |

---

## 🔒 Invariants critiques

### 1. Immutabilité finale

```txt
CONFIRMEE → LIVREE → TERMINE
```

Une fois finalisé :

- aucune modification métier possible
- aucun changement de statut
- aucune mutation des données critiques

---

### 2. Couplage avec Transaction

Un Booking dépend toujours des transactions pour :

| Action     | Condition                |
| ---------- | ------------------------ |
| CONFIRMEE  | CHARGE COMPLETED         |
| REMBOURSEE | REFUND COMPLETED         |
| LIVREE     | action métier (voyageur) |
| TERMINE    | PAYOUT COMPLETED         |

---

### 3. Exclusivité financière

```txt
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir :

- payout ET refund

---

### 4. Cohérence logistique

Toujours garantir :

```txt
Booking ↔ BookingItem ↔ Luggage ↔ Trip
```

---

## ⚠️ Règles métier critiques

---

### Confirmation

Un booking devient `CONFIRMEE` uniquement si :

- transaction `CHARGE` existe
- status = `COMPLETED`

---

### Expiration

Un booking `EN_PAIEMENT` devient `EXPIREE` si :

- `payment_expires_at` dépassé

Effets :

- libère la capacité du trip
- remet les bagages disponibles

---

### Livraison

Lors du passage à `LIVREE` :

- création `PAYOUT`
- création `FEE`
- aucune duplication possible

---

### Annulation

Possible uniquement si :

- pas encore confirmé
- ou cas métier spécifique

---

### Litige

- bloque payout
- ouvre possibilité de refund admin

---

## 🎒 Gestion des bagages

### Cycle Luggage

```txt
EN_ATTENTE → RESERVEE → EN_TRANSIT → LIVREE
```

### Règles

- un bagage ne peut être réservé qu’une fois
- dépend du booking
- libéré si booking expire ou annulé

---

## 🚚 Gestion de capacité

### Calcul

Capacité utilisée :

- CONFIRMEE → définitif
- EN_PAIEMENT non expiré → temporaire

### Règle

```txt
capacity_used ≤ capacity_trip
```

---

## 🔒 Concurrence

### Cas critiques

- réservation
- confirmation
- expiration
- ajout de bookingItem

### Stratégie

- transaction DB obligatoire
- `lockForUpdate()`
- vérification capacité avant écriture

---

## 🔁 Idempotence

Doit être garantie pour :

- confirmation
- expiration
- livraison
- refund
- payout

---

## 🔄 Transitions

Centralisées dans :

```php
BookingStatusEnum
```

---

### Règles

- aucune transition hardcodée
- toujours passer par Enum
- transitions validées par `canTransitionTo()`

---

## 🧾 Historisation

Chaque changement de statut doit :

- être enregistré dans `booking_status_histories`
- contenir :
    - ancien statut
    - nouveau statut
    - acteur
    - raison (si applicable)

---

## 🔍 Observabilité

Un booking doit être traçable via :

- transactions
- audit logs
- webhook logs
- correlation_id

---

## ⚖️ Couplage avec finance

Le Booking ne calcule jamais :

- fee
- payout
- refund

Il dépend uniquement des Transactions.

---

## 🔐 Sécurité

- accès via Policy
- expéditeur → ses bookings
- voyageur → bookings de ses trips
- admin → accès global

---

## 🧪 Testabilité

Doit être testé :

- transitions
- capacité
- expiration
- idempotence
- couplage avec transaction
- concurrence

---

## ⚠️ Anti-patterns interdits

- modifier statut sans Enum
- calcul financier dans Booking
- bypass Transaction
- ignorer expiration
- réserver sans lock
- accès direct DB sans transaction

---

## 🔮 Extensions futures

- multi-valises avancé
- réservation partielle dynamique
- réservation par volume (cm3)
- litige avancé avec compensation
- escrow complet

---

## 🧠 Résumé exécutif

```txt
Booking = pivot métier

- orchestre logistique
- dépend de la finance
- ne calcule rien financièrement
```

---

## 🧠 Design intention

Le Booking est conçu pour :

- centraliser la logique métier
- rester simple en MVP
- garantir cohérence et traçabilité
- évoluer vers un système transactionnel complet

> Un Booking mal conçu casse tout le système.

```

```
