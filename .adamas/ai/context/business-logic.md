# 🧠 Business Logic — GP-Valise

## 🎯 Vision

GP-Valise est une plateforme logistique de confiance permettant :

- à un **expéditeur** d'envoyer un objet
- via un **voyageur** tiers
- avec un système sécurisé (paiement, suivi, litige)

Le produit repose sur 3 piliers :

- **Logistique** : Trip, Luggage, Booking
- **Confiance** : KYC, Reports, Litiges
- **Finance** : Payment, Transaction

---

## 👥 Acteurs

### Expéditeur

- crée des bagages
- réserve un trajet (Booking)
- paie
- suit la livraison
- peut ouvrir un litige

### Voyageur

- publie un trajet (Trip)
- transporte des bagages
- confirme la livraison
- reçoit un payout

### Admin

- supervise la plateforme
- gère les litiges
- contrôle les fraudes

---

## 🧱 Objets métier

### Trip

Trajet proposé par un voyageur : capacité (kg), dates, étapes.

### Luggage

Objet transporté : poids, statut, tracking.

### Booking (entité centrale)

Lien entre expéditeur, trajet, paiement et livraison.

Le Booking pilote le cycle métier, la cohérence logistique et le lien avec les transactions.

### BookingItem

Relation `Luggage ↔ Trip ↔ Booking`. Permet l'allocation de capacité et la gestion multi-bagages.

### Payment

Vue métier du paiement : état utilisateur (en attente, payé, échoué), abstraction du provider.

### Transaction

Vérité financière atomique : `CHARGE`, `PAYOUT`, `REFUND`, `FEE`, `PAYMENT_FEE`.

> Toute décision financière repose sur les transactions, jamais sur le statut Booking seul.

---

## 💰 Principe fondamental

> Transaction = source de vérité financière.

- **Payment** = workflow utilisateur
- **Transaction** = réalité comptable

Le Booking ne dépend jamais directement du provider PSP.

---

## 🔁 Cycle de vie du Booking

### Flow principal

```
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ LIVREE
→ TERMINE
```

### Cas alternatifs

| Statut       | Condition déclenchante                      |
| ------------ | ------------------------------------------- |
| `EXPIREE`    | paiement non effectué dans le délai imparti |
| `ANNULE`     | annulation avant confirmation               |
| `REMBOURSEE` | refund COMPLETED après annulation           |
| `EN_LITIGE`  | problème déclaré après livraison            |

### Règles critiques

- un booking en statut final est **immuable**
- toute transition doit être **validée** par l'Enum
- toute transition doit être **historisée**
- aucune transition directe sans règle métier explicite
- aucune confirmation sans paiement `CHARGE COMPLETED`

---

## 💳 Règles de confirmation

Un booking devient `CONFIRMEE` uniquement si :

- une transaction `CHARGE` existe
- cette transaction est `COMPLETED`

> Le statut Booking dépend des Transactions, pas du provider.

---

## 🔒 Règle d'exclusivité financière

Un booking ne peut jamais avoir simultanément un `PAYOUT` et un `REFUND`.

```
PAYOUT ⊕ REFUND  (mutuellement exclusifs)
```

---

## 🎒 Règles Luggage

- un bagage ne peut pas être réservé plusieurs fois simultanément
- cycle strict aligné sur le Booking :

```
EN_ATTENTE → RESERVEE → EN_TRANSIT → LIVREE
```

---

## 🚚 Règles Trip

- `ACTIVE` → réservable
- `CANCELLED` / `COMPLETED` → non réservable

### Calcul de capacité disponible

La capacité restante d'un trip est calculée en fonction des bookings :

- `CONFIRMEE` : capacité définitivement allouée
- `EN_PAIEMENT` non expirés : capacité temporairement réservée

> Objectif : éviter l'overbooking et gérer la concurrence.

---

## ⚠️ Contraintes critiques

### Overbooking

Strictement interdit. La capacité du trip doit être vérifiée avec `lockForUpdate()` avant toute réservation.

### Expiration

Un booking `EN_PAIEMENT` expire automatiquement si le paiement n'est pas complété dans le délai imparti. L'expiration libère la capacité du trip.

### Cohérence des entités

Les trois entités doivent rester alignées à tout moment :

```
Booking ↔ Luggage ↔ Trip
```

Toute incohérence entre ces entités est un bug critique.

---

## 🔄 Concurrence

- opérations critiques protégées par `lockForUpdate()`
- transitions métier idempotentes
- statut final bloque toute modification ultérieure

---

## ⚖️ Lien Booking ↔ Finance

Le Booking ne pilote pas directement la finance. Il dépend des Transactions pour :

- **confirmation** → CHARGE COMPLETED
- **remboursement** → REFUND COMPLETED
- **finalisation** → PAYOUT COMPLETED

Cela garantit : découplage avec le provider, cohérence métier, traçabilité complète.

---

## 🔮 Préparation v5

### Escrow

- CHARGE retenue par la plateforme
- PAYOUT différé après validation de livraison

### Litige

- statut `EN_LITIGE` bloque le payout
- permet refund ou compensation manuelle via admin

### Compensation future

- refund partiel
- payout partiel
- arbitrage admin

---

## 🧠 Design intention

Le système privilégie cohérence métier, traçabilité, simplicité MVP et évolutivité — plutôt que la complexité prématurée (ledger complet, multi-refund, compensation automatique).

Le système est : transactionnel, event-driven, concurrent-safe, idempotent, compatible async (webhooks), prêt pour extension fintech.
