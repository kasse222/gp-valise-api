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

1. Création → EN_PAIEMENT
    - bloque temporairement les kg
    - définit payment_expires_at

2. Paiement réussi → CONFIRMEE
    - confirme la réservation
    - kg définitivement utilisés

3. Paiement échoué / expiré
    - booking annulé
    - kg libérés
    - luggage remis EN_ATTENTE

4. Livraison → COMPLETEE

### Booking

- `BookingService` supprimé
- `BookingController` refactoré
- usage plus cohérent du route model binding
- actions appelées par injection d’instance

### Plan de refactor TransactionController

1. Supprimer authorize inline
2. Ajouter TransactionPolicy::refund()
3. Créer RefundTransactionRequest
4. Créer RefundTransaction Action
5. Supprimer TransactionService
6. Injecter Action dans Controller

### Trip

- `UpdateTrip` créé
- début d’alignement du module avec la convention `Controller -> Action`

---

## 5. État actuel

### Modules bien alignés

- Booking
- Transaction

### Modules partiellement alignés

- Trip

### Modules encore à refactorer

- Plan
- Report
- Trip (suppression complète de `TripService` si encore présent)

---
