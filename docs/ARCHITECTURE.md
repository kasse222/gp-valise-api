# 🧠 ARCHITECTURE GP-VALISE

## 1. Principe directeur

GP-Valise suit une architecture orientée cas d’usage.

Chaque couche a une responsabilité claire et limitée.

### Répartition des responsabilités

- `Controller` = orchestration HTTP uniquement
- `FormRequest` = validation d’entrée HTTP
- `Policy` = autorisation / contrôle d’accès
- `Action` = un cas d’usage métier complet
- `Validator` = validation métier réutilisable hors HTTP
- `Enum` = règles d’état, transitions et comportement métier pur
- `Service` = orchestration transverse rare

---

## 2. Interdits

Les règles suivantes structurent le projet :

- pas de logique métier dans les `Controllers`
- pas de logique métier dans les `Policies`
- pas d’accès base de données dans les `Enums`
- pas de duplication entre `Action` et `Service`
- pas de `Service` pour un use case métier simple et isolé

---

## 3. Convention d’architecture retenue

### Convention principale

- injection d’instance dans le contrôleur
- appel uniforme des use cases via `execute(...)`

### Cible

Le flux standard doit être :

`Controller -> Action -> Model / Enum / Validator`

avec :

- `Policy` pour l’accès
- `FormRequest` pour la validation HTTP
- `Resource` pour la réponse API

---

## 4. Décisions déjà prises

## 📦 Booking lifecycle (règle métier)

1. Création → `EN_PAIEMENT`
    - bloque temporairement les kg
    - définit `payment_expires_at`

2. Paiement validé + confirmation métier → `CONFIRMEE`
    - confirme la réservation
    - kg définitivement utilisés

3. Paiement échoué / expiré
    - booking → `PAIEMENT_ECHOUE` ou `EXPIREE` selon le cas
    - kg libérés
    - luggage remis `EN_ATTENTE`

4. Livraison → `LIVREE` puis `TERMINE`

### Booking

- `BookingService` supprimé
- `BookingController` refactoré
- usage plus cohérent du route model binding
- actions appelées par injection d’instance
- flow paiement introduit : `EN_ATTENTE -> EN_PAIEMENT -> CONFIRMEE`
- expiration automatique des bookings en attente de paiement
- batch d’expiration isolé et idempotent
- events métier alignés : `BookingConfirmed`, `BookingCanceled`, `BookingDelivered`, `BookingExpired`
- listeners de logs homogénéisés (`booking.*`)
- création directe d’historique de statut supprimée au profit des transitions métier uniquement
- tests métier, events et batch alignés

### Transaction

- `TransactionService` supprimé
- `TransactionController` aligné sur le pattern Action-first
- `CreateTransaction` et `RefundTransaction` centralisent les invariants métier
- création de transaction autorisée uniquement pour un booking :
    - appartenant à l’utilisateur
    - en `EN_PAIEMENT`
    - non expiré
    - sans transaction existante
- `UpdateTransactionRequest` supprimé (reliquat mort)
- eager loading global implicite supprimé du modèle `Transaction`
- events métier alignés : `TransactionCreated`, `TransactionRefunded`
- listeners de logs homogènes (`transaction.*`)

### Payment

- module réaligné sur le pattern Action-first
- `PaymentController` refactoré pour utiliser des actions injectées
- lecture sortie du controller via `ListPayments` et `GetPaymentDetails`
- `CreatePayment` et `UpdatePayment` convertis en actions d’instance
- `Payment` reste pour l’instant une couche métier simple côté produit/UI
- `Transaction` reste la couche financière la plus robuste du système
- évolution future envisagée : modèle de type escrow / marketplace géré progressivement

### Trip

- `UpdateTrip` créé
- `TripController` partiellement refactoré
- `index()` extrait vers `ListTrips`
- `show()` extrait vers `GetTripDetails`
- module désormais plus cohérent avec la convention `Controller -> Action`

---

## 5. État actuel

### Modules bien alignés

- Booking
- Transaction
- Payment
- Booking est désormais batch-ready

### Modules partiellement alignés

- Trip

### Modules encore à refactorer

- Plan
- Report
- Trip (suppression complète de `TripService` si encore présent)

---

## 6. Décisions métier à porter progressivement

### Payment vs Transaction

- `Payment` = couche métier produit / cycle de paiement côté plateforme
- `Transaction` = mouvements financiers unitaires et traçables

### Direction cible (progressive, sans big bang)

Le projet évolue vers un modèle marketplace / escrow simple :

1. l’expéditeur paie la plateforme
2. la plateforme conserve temporairement les fonds
3. le voyageur livre le colis
4. la plateforme libère les fonds au voyageur
5. un remboursement reste possible en cas d’échec ou de litige

### Conséquence architecturale

À moyen terme, `Payment` devra représenter un cycle de paiement métier plus complet, tandis que `Transaction` portera les mouvements atomiques du type :

- `charge`
- `payout`
- `refund`
- `fee` (si commission plateforme)

Cette évolution doit se faire par étapes, sans refactor destructif global.

---

## 7. Priorités de refactor à venir

- finaliser l’alignement de `Trip`
- clarifier davantage le rôle futur de `Payment`
- introduire progressivement la logique escrow / marketplace
- refactorer `Plan` et `Report` selon la même convention
- enrichir le README et la documentation d’architecture pour valoriser le projet
