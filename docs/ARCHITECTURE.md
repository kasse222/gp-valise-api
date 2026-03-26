# 🧠 ARCHITECTURE GP-VALISE

## Responsabilités

- Controller = orchestration HTTP uniquement
- FormRequest = validation d’entrée HTTP
- Policy = autorisation / contrôle d’accès
- Action = un cas d’usage métier complet
- Validator = validation métier réutilisable, hors HTTP
- Enum = règles d’état et transitions
- Service = orchestration transverse rare, utilisée seulement si plusieurs actions partagent la même logique

## Interdits

- logique métier dans Controller
- logique métier dans Policy
- accès DB dans Enum
- duplication entre Action et Service

## Décisions prises

- `BookingService` supprimé : non utilisé et redondant avec `ReserveBooking`
- `BookingController` refactoré pour uniformiser les appels aux actions
- adoption d’une convention : injection d’instance + `execute(...)`
- usage plus cohérent du route model binding sur le module Booking

## Points à améliorer ensuite

- harmoniser les signatures internes des actions (`id` vs modèle)
- clarifier le rôle réel des `Services`
- vérifier que toutes les `Policies` restent centrées sur l’accès

## Audit BookingController

### Points positifs

- Le contrôleur ne contient pas de logique métier lourde.
- Les cas d’usage métier sont extraits dans des actions dédiées.
- La policy est globalement bien séparée de la logique métier.

### Points à améliorer

- Les actions sont appelées de manière incohérente (statique vs injection).
- Les signatures de méthodes ne sont pas uniformes (`id` brut vs route model binding).
- Une partie du chargement des modèles et relations reste dans le contrôleur.
- Le sens métier de `index()` n’est pas totalement aligné avec `viewAny()`.

### Décision cible

- Utiliser le route model binding partout où possible.
- Utiliser les actions en injection d’instance uniquement.
- Réserver le contrôleur à l’orchestration HTTP.

## Refactor BookingController

### Changements effectués

- Uniformisation des appels aux actions via injection d’instance.
- Adoption du route model binding pour les méthodes manipulant une réservation existante.
- Réduction du chargement manuel des modèles dans le contrôleur.

### Bénéfices

- Contrôleur plus cohérent
- Architecture plus lisible
- Convention d’appel des actions clarifiée

## TODO – Harmonisation TripController

### À améliorer

#### index()

- Extraire la logique de récupération dans une action (ex: ListTrips)
- Décider si pagination / filtres doivent être gérés côté Action

#### show()

- Vérifier cohérence avec policies (view)
- Éventuellement centraliser dans une action (ex: GetTripDetails)

### Pourquoi

- Alignement avec l’architecture basée sur les Actions
- Réduction de la logique Eloquent dans les Controllers

## Refactor TripController

### Changements effectués

- Création de l’action `UpdateTrip`
- Harmonisation de `TripController` avec la convention `Controller -> Action`
- Uniformisation des appels d’actions par injection d’instance

### Convention retenue

- Les use cases métier de création et de modification doivent vivre dans des actions dédiées
- Le contrôleur ne doit pas exécuter directement les mises à jour métier

## Refactor TransactionController

## Décisions prises – Transactions

- `TransactionController` refactoré pour supprimer la logique d’accès inline
- création extraite dans `CreateTransaction`
- remboursement extrait dans `RefundTransaction`
- `RefundTransactionRequest` ajouté pour sortir la validation du contrôleur
- `TransactionService` supprimé car devenu redondant et non utilisé

### Convention appliquée

- Controller = orchestration HTTP
- Policy = accès
- FormRequest = validation HTTP
- Action = use case métier

## Règle retenue pour les Services

Un Service ne doit exister que s’il porte une logique transverse :

- multi-modules
- multi-actions
- orchestration complexe
- intégration externe

Un Service ne doit pas exister pour un simple use case métier isolé.

## État actuel

- Booking et Transaction sont mieux alignés avec l’architecture cible :
    - Controller = orchestration
    - Action = use case
- Plan n’est pas encore au même niveau car `PlanService` porte encore directement la logique métier.

## Hypothèse d’évolution

- Booking est le module le plus susceptible d’avoir un vrai Service plus tard si son orchestration devient transverse et complexe.
- À ce stade, les Actions restent suffisantes.
