# Coding Standards — GP-Valise

## 🧼 Style de code

- PSR-12
- `declare(strict_types=1);`
- Retours typés
- Early return

---

## 📁 Nommage

- **Controller** : `XxxController`
- **Action** : `VerbeNom` (ex: `ConfirmBooking` – placé dans `App\Actions\...`)
- **FormRequest** : `XxxRequest`
- **Policy** : `XxxPolicy`
- **Enum** : `XxxEnum` (ex: `BookingStatusEnum`)
- **Service** : `XxxService` (rare)

---

## 🧪 Tests

- Une Action = plusieurs tests si nécessaire
- Tests d’Action = tests métier (prioritaires)
- Tests HTTP = tests d’intégration (secondaires)
- Tester :
    - cas nominal
    - erreurs
    - edge cases
    - idempotence
- Utiliser factories
- Tests HTTP pour Controllers (uniquement pour vérifier le routage, les codes HTTP et les exceptions capturées – ne pas tester la logique métier déjà couverte par les tests d’Action)
- ***

## 🚫 Interdits

- `dd()`, `dump()` en prod
- accès direct à `request()` ou `Auth` dans Action
- string magic pour statuts
- événements non typés
- commentaires inutiles – le code doit être auto-documenté
- Un Model ne doit pas dépendre d’un autre service ou système externe
- logique métier dans les migrations / seeders

---

## 🔁 Models

Autorisé :

- getters métier simples
- calculs locaux
- transition contrôlée via Enum

## 🎯 Actions

- Signature explicite (pas de tableau générique si évitable)
- Paramètres typés
- Retour typé (Model, DTO ou valeur claire)
- Aucune dépendance implicite (request(), Auth, globals)

## 📜 Logging

- Logger uniquement les événements importants :
    - erreurs métier
    - événements critiques (paiement, refund)
- Ne jamais logger de données sensibles
- Ne pas spammer les logs

❌ Interdit :

- orchestration multi-étapes
- logique transverse
- effets de bord complexes
