# 🔍 AUDIT GP-VALISE — SEMAINE 1

## 1. État général

- Tests : OK (129 passés, 330 assertions)
- API : fonctionnelle
- Docker : OK
- Seeders : OK

---

## 2. Incident résolu pendant la baseline

- La suite de tests échouait massivement au départ.
- Cause probable : cache Laravel/bootstrap incohérent ou stale.
- Action corrective : `php artisan optimize:clear`
- Résultat : restauration complète de la suite de tests.

---

## 3. Première conclusion

- La base projet est saine.
- Les prochains audits peuvent maintenant porter sur l’architecture, la séparation des responsabilités et les conventions.

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

## TODO – Audit / refactor TransactionController

### Problèmes identifiés

- Mélange entre `authorizeResource`, `authorize()` manuel et contrôles d’accès inline (`abort(403)`).
- Logique d’autorisation encore présente dans le contrôleur (`show`, `refund`).
- Usage répétitif de `Auth::user()` au lieu d’un passage explicite du user.
- Validation inline dans `refund()` à extraire dans une FormRequest dédiée.
- Contrôleur encore trop couplé à `TransactionService`.
- Convention architecture non alignée avec les autres modules refactorés.

### Objectif de refactor

- Controller = orchestration HTTP uniquement
- Policy = accès uniquement
- FormRequest = validation HTTP
- Action ou Service = logique métier
- Zéro logique d’autorisation métier inline dans le contrôleur
