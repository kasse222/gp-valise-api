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
- effectue un paiement
- suit la livraison
- peut ouvrir un litige

---

### Voyageur

- publie un trajet (Trip)
- transporte les bagages
- confirme / finalise la livraison
- reçoit un payout

---

### Admin

- supervise la plateforme
- gère les litiges
- contrôle les fraudes

---

## 🧱 Objets métier

### Trip

Représente un trajet avec :

- capacité (kg)
- dates
- points de passage

---

### Luggage

Représente un objet transporté :

- poids
- statut
- tracking

---

### Booking

Réservation entre un expéditeur et un trajet.

👉 **Entité centrale du système**

Responsabilités :

- lien entre paiement, logistique et livraison
- pilotage du cycle métier

---

### BookingItem

Liaison concrète :

```txt
Luggage ↔ Trip ↔ Booking
```

Permet :

- gestion fine des bagages
- allocation de capacité

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
- (voir payment-logic.md pour FEE / PAYMENT_FEE)

👉 Toute décision financière repose sur les transactions.

---

## 💰 Principe fondamental

> Transaction = source de vérité financière

- Payment = workflow utilisateur
- Transaction = réalité comptable

👉 Un Booking ne doit jamais dépendre directement du provider.

---

## 🔁 Cycle Booking

### Flow principal

```txt
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ LIVREE
→ TERMINE
```

---

### Cas alternatifs

- EXPIREE (paiement non effectué à temps)
- ANNULE (par utilisateur ou système)
- REMBOURSEE (après refund)
- EN_LITIGE (problème après livraison)

---

## 🔒 Règles critiques

- un booking final est immuable
- toute transition doit être validée
- toute transition doit être historisée
- un booking ne peut évoluer que via des règles métier contrôlées
- aucune confirmation sans paiement valide

---

## 💳 Règle de confirmation

Un booking est `CONFIRMEE` uniquement si :

- une transaction `CHARGE` existe
- cette transaction est `COMPLETED`

👉 Le statut Booking dépend uniquement des Transactions, jamais du provider.

---

## 🔁 Règle critique (finance)

Un booking ne peut pas avoir simultanément :

```txt
PAYOUT + REFUND
```

👉 Ces deux états sont mutuellement exclusifs.

---

## 🎒 Règles Luggage

- un bagage ne peut pas être réservé plusieurs fois simultanément
- cycle strict :

```txt
EN_ATTENTE → RESERVEE → EN_TRANSIT → LIVREE
```

- un bagage suit le cycle du booking associé

---

## 🚚 Règles Trip

- `ACTIVE` → réservable
- `CANCELLED` / `COMPLETED` → non réservable

---

### Capacité

La capacité d’un trip doit prendre en compte :

- bookings `CONFIRMEE`
- bookings `EN_PAIEMENT` non expirés

👉 évite overbooking + race conditions

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

```txt
Booking ↔ Luggage ↔ Trip
```

👉 Toute incohérence est un bug critique.

---

## 🔄 Concurrence

- opérations critiques protégées par `lockForUpdate()`
- transitions métier idempotentes
- état final bloque toute modification

---

## 📊 Position actuelle

Le système est :

- transactionnel
- event-driven
- concurrent-safe
- idempotent
- compatible async (webhooks)

👉 Niveau backend SaaS crédible

---

## 🧠 Design intention

Le système privilégie :

- cohérence métier
- traçabilité
- sécurité des transitions
- simplicité MVP

👉 plutôt que complexité prématurée (ledger complet, multi-refund, etc.)

```

---

# 🧠 Ce que j’ai amélioré

### 1. ✔ Ajout règle critique
👉 `PAYOUT` vs `REFUND` (très important)

### 2. ✔ Clarification dépendances
👉 Booking dépend des **transactions**, pas du provider

### 3. ✔ Renforcement cohérence système
👉 Booking ↔ Luggage ↔ Trip

### 4. ✔ Préparation refactor suivant
👉 transitions métier bien cadrées
```
