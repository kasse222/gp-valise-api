````md
# 🧠 Booking Domain — GP-Valise

---

# 🎯 Objectif

Le Booking est l’entité centrale du système GP-Valise.

Il représente :

- une réservation logistique
- un engagement financier
- un cycle métier transactionnel complet

Il garantit la cohérence entre :

```txt
Expéditeur ↔ Trip ↔ Luggage ↔ Transaction
```
````

---

# 🧠 Principe fondamental

> Le Booking est le pivot métier.

Le Booking :

- orchestre la logistique
- dépend des événements financiers
- ne calcule jamais la finance lui-même

---

# 🧱 Structure

## Relations principales

| Relation          | Description                |
| ----------------- | -------------------------- |
| `user_id`         | expéditeur                 |
| `trip_id`         | trajet associé             |
| `bookingItems`    | bagages réservés           |
| `transactions`    | flux financiers            |
| `statusHistories` | historique des transitions |

---

# 🔁 Cycle de vie

## Flow principal

```txt
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ LIVREE
→ TERMINE
```

---

## États alternatifs

| Statut     | Description               |
| ---------- | ------------------------- |
| EXPIREE    | paiement non effectué     |
| ANNULE     | annulé avant confirmation |
| REMBOURSEE | refund effectué           |
| EN_LITIGE  | problème déclaré          |

---

# 🔒 Invariants critiques

---

## 1. États finaux

États considérés comme finaux :

```txt
TERMINE
REMBOURSEE
ANNULE
EXPIREE
```

Une fois finalisé :

- aucune mutation métier critique
- aucune transition standard
- aucune écriture financière supplémentaire

---

## 2. Couplage avec Transaction

Le Booking dépend toujours des Transactions pour :

| Action     | Condition        |
| ---------- | ---------------- |
| CONFIRMEE  | CHARGE COMPLETED |
| REMBOURSEE | REFUND COMPLETED |
| TERMINE    | PAYOUT COMPLETED |

---

## 3. Exclusivité financière

```txt
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir :

- payout ET refund

---

## 4. Cohérence logistique

Toujours garantir :

```txt
Booking ↔ BookingItem ↔ Luggage ↔ Trip
```

---

## 5. Escrow consistency

```txt
LIVREE ≠ payout immédiat
```

Un booking livré entre dans une phase escrow.

Le payout devient éligible uniquement après :

- expiration du délai escrow
- absence de dispute
- validation des invariants financiers

---

# ⚠️ Règles métier critiques

---

## Confirmation

Un booking devient `CONFIRMEE` uniquement si :

- transaction `CHARGE` existe
- status = `COMPLETED`

---

## Expiration

Un booking `EN_PAIEMENT` devient `EXPIREE` si :

- `payment_expires_at` dépassé

Effets :

- libération de capacité
- bagages remis disponibles

---

## Livraison

Lors du passage à `LIVREE` :

- `delivered_at` est renseigné
- `escrow_releasable_at` est calculé
- les fonds restent détenus par la plateforme
- aucun payout immédiat n'est créé

Le payout devient éligible après la période escrow.

---

## Escrow release

Le payout est autorisé uniquement si :

```txt
booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND charge COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS
```

Le scheduler escrow est responsable de la libération.

---

## Annulation

Possible uniquement si :

- booking non confirmé
- aucun engagement financier irréversible

---

## Litige

Un litige :

- bloque l’escrow
- interdit le payout
- peut mener à refund admin
- nécessite résolution explicite

---

## Remboursement

Un booking devient `REMBOURSEE` uniquement si :

- transaction `REFUND` existe
- refund status = `COMPLETED`
- aucun payout existant

Le refund est déclenché via :

```txt
HandlePaymentWebhook::handleSuccess()
```

sur :

```txt
refund.completed
```

---

# 🏦 Treasury Ownership

Avant payout :

```txt
fonds détenus par la plateforme
```

Après payout :

```txt
fonds transférés au voyageur
```

Après refund :

```txt
fonds retournés à l’expéditeur
```

Le Booking ne possède jamais directement les fonds.

La trésorerie est orchestrée via :

- Transactions
- PlatformAccounts
- Escrow lifecycle

---

# 🎒 Gestion des bagages

## Cycle Luggage

```txt
EN_ATTENTE
→ RESERVEE
→ EN_TRANSIT
→ LIVREE
```

---

## Règles

- un bagage ne peut être réservé qu’une seule fois
- dépend toujours d’un Booking
- libéré si booking expire ou annulé

---

# 🚚 Gestion de capacité

## Canonical unit

```txt
grams integer
```

Exemple :

```txt
25000 = 25kg
```

---

## Calcul capacité utilisée

Comptabilisés :

- CONFIRMEE
- EN_PAIEMENT non expiré

---

## Règle

```txt
capacity_used ≤ capacity_trip
```

---

# 💰 Représentation monétaire

## Canonical unit

```txt
minor integer units
```

Exemple :

```txt
1500 = 15.00€
```

---

## Règles

Interdits :

- float
- decimal métier
- calculs approximatifs

Autorisés :

- integer arithmetic
- deterministic computation

---

# 🔒 Concurrence

## Cas critiques

- réservation
- confirmation
- expiration
- payout release
- ouverture litige

---

## Stratégie

- DB transaction obligatoire
- `lockForUpdate()`
- validation avant écriture

---

# 🔁 Idempotence

Doit être garantie pour :

- confirmation
- expiration
- payout release
- refund
- webhook handling

---

# 🔄 Transitions

Centralisées dans :

```php
BookingStatusEnum
```

---

## Règles

- aucune transition hardcodée
- toujours via Enum
- validation via `canTransitionTo()`

---

## Table des transitions autorisées

| De → Vers                | Déclencheur            |
| ------------------------ | ---------------------- |
| EN_ATTENTE → EN_PAIEMENT | paiement initié        |
| EN_PAIEMENT → CONFIRMEE  | charge completed       |
| EN_PAIEMENT → EXPIREE    | expiration paiement    |
| CONFIRMEE → LIVREE       | livraison confirmée    |
| CONFIRMEE → EN_LITIGE    | ouverture litige       |
| CONFIRMEE → REMBOURSEE   | refund completed       |
| LIVREE → EN_LITIGE       | dispute post-livraison |
| LIVREE → TERMINE         | payout completed       |
| EN_LITIGE → REMBOURSEE   | refund admin           |
| EN_LITIGE → LIVREE       | résolution litige      |

---

# 🧾 Historisation

Chaque changement de statut doit :

- être historisé
- contenir :
    - ancien statut
    - nouveau statut
    - acteur
    - raison éventuelle

---

# 🔍 Observabilité

Un booking doit être traçable via :

- transactions
- audit logs
- webhook logs
- correlation_id

---

# ⚖️ Séparation des responsabilités

Le Booking :

- orchestre
- valide
- coordonne

Le Booking ne :

- calcule pas les montants
- ne calcule pas les fees
- ne connaît pas les PSP
- ne gère pas la trésorerie

---

# 🔐 Sécurité

Accès via Policies :

| Acteur     | Accès                 |
| ---------- | --------------------- |
| Expéditeur | ses bookings          |
| Voyageur   | bookings de ses trips |
| Admin      | accès global          |

---

# 🧪 Testabilité

Le domaine doit être testé pour :

- transitions
- capacité
- concurrence
- escrow
- payout guards
- refund guards
- idempotence
- cohérence transactionnelle

---

# ⚠️ Anti-patterns interdits

- mutation statut hors Enum
- finance dans Booking
- bypass Transaction
- bypass escrow
- float pour money/weight
- réservation sans lock
- payout immédiat à LIVREE

---

# 🔮 Extensions futures

- dispute resolution workflow
- escrow configurable
- reserve balances
- payout batching
- reconciliation engine
- ledger interne
- multi-currency treasury
- volume-based booking
- risk scoring

---

# 🧠 Résumé exécutif

```txt
Booking = pivot métier transactionnel

- orchestre logistique
- dépend de la finance
- protège les invariants
- reste découplé des PSP
- évolue vers un système treasury complet
```

---

# 🧠 Design intention

Le Booking est conçu pour :

- centraliser la logique métier
- garantir cohérence transactionnelle
- préserver auditabilité et traçabilité
- supporter escrow et treasury
- évoluer vers un backend marketplace robuste

> Un Booking mal conçu casse tout le système.

```

```
