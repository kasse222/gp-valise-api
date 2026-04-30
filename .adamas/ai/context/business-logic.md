# 🧠 Business Logic — GP-Valise

## 🎯 Vision

GP-Valise est une plateforme logistique de confiance permettant :

- à un expéditeur d’envoyer un objet
- via un voyageur tiers
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

---

### Voyageur

- publie un trajet (Trip)
- transporte des bagages
- confirme la livraison
- reçoit un payout

---

### Admin

- supervise la plateforme
- gère les litiges
- contrôle les fraudes

---

## 🧱 Objets métier

### Trip

Trajet proposé par un voyageur :

- capacité (kg)
- dates
- étapes

---

### Luggage

Objet transporté :

- poids
- statut
- tracking

---

### Booking (entité centrale)

Lien entre :

- expéditeur
- trajet
- paiement
- livraison

👉 Le Booking pilote :

- le cycle métier
- la cohérence logistique
- le lien avec les transactions

---

### BookingItem

Relation :

```

Luggage ↔ Trip ↔ Booking

```

Permet :

- allocation de capacité
- gestion multi-bagages

---

### Payment

Vue métier du paiement :

- état utilisateur (en attente, payé, échoué)
- abstraction du provider

---

### Transaction

Vérité financière atomique :

- CHARGE
- PAYOUT
- REFUND
- FEE
- PAYMENT_FEE

👉 Toute décision financière repose sur les transactions.

---

## 💰 Principe fondamental

> Transaction = source de vérité financière

- Payment = workflow utilisateur
- Transaction = réalité comptable

👉 Le Booking ne dépend jamais directement du provider.

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

---

### Cas alternatifs

- EXPIREE (paiement non effectué à temps)
- ANNULE (avant confirmation)
- REMBOURSEE (après refund)
- EN_LITIGE (problème après livraison)

---

## 🔒 Règles critiques

- un booking final est immuable
- toute transition doit être validée
- toute transition doit être historisée
- aucune transition directe sans règle métier
- aucune confirmation sans paiement valide

---

## 💳 Règle de confirmation

Un booking est `CONFIRMEE` uniquement si :

- une transaction `CHARGE` existe
- cette transaction est `COMPLETED`

👉 Le statut Booking dépend uniquement des transactions.

---

## 🔁 Règle critique (finance)

Un booking ne peut jamais avoir :

```

PAYOUT + REFUND

```

👉 Ces états sont mutuellement exclusifs.

---

## 🎒 Règles Luggage

- un bagage ne peut pas être réservé plusieurs fois simultanément
- cycle strict :

```

EN_ATTENTE → RESERVEE → EN_TRANSIT → LIVREE

```

- un bagage suit le cycle du booking

---

## 🚚 Règles Trip

- `ACTIVE` → réservable
- `CANCELLED` / `COMPLETED` → non réservable

---

### Capacité

La capacité d’un trip dépend de :

- bookings `CONFIRMEE`
- bookings `EN_PAIEMENT` non expirés

👉 Objectif :

- éviter l’overbooking
- gérer la concurrence

---

## ⚠️ Contraintes critiques

### Overbooking

→ strictement interdit

---

### Expiration

- un booking `EN_PAIEMENT` expire automatiquement
- libère la capacité du trip

---

### Cohérence système

Les entités doivent rester alignées :

```

Booking ↔ Luggage ↔ Trip

```

👉 Toute incohérence = bug critique

---

## 🔄 Concurrence

- opérations critiques protégées par `lockForUpdate()`
- transitions métier idempotentes
- état final bloque toute modification

---

## ⚖️ Lien avec la logique financière

Le Booking ne pilote pas directement la finance.

👉 Il dépend des Transactions pour :

- confirmation (CHARGE)
- remboursement (REFUND)
- finalisation (PAYOUT)

👉 Cela garantit :

- découplage avec le provider
- cohérence métier
- traçabilité

---

## 🔮 Préparation v5 (escrow / dispute)

Le modèle actuel prépare :

### Escrow

- CHARGE retenue par la plateforme
- PAYOUT différé après validation

---

### Litige

- statut `EN_LITIGE`
- bloque payout
- permet refund ou compensation

---

### Compensation future

- refund partiel
- payout partiel
- arbitrage admin

---

## 📊 Position actuelle

Le système est :

- transactionnel
- event-driven
- concurrent-safe
- idempotent
- compatible async (webhooks)
- prêt pour extension fintech

---

## 🧠 Design intention

Le système privilégie :

- cohérence métier
- traçabilité
- simplicité MVP
- évolutivité

👉 plutôt que complexité prématurée (ledger complet, multi-refund, etc.)

```

```
