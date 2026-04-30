# Coding Standards — GP-Valise

## 🧼 Style de code

- PSR-12
- `declare(strict_types=1);`
- Retours typés obligatoires
- Early return (éviter les `else` inutiles)

---

## 📁 Nommage

- **Controller** : `XxxController`
- **Action** : `VerbeNom` (ex: `ConfirmBooking`)
- **FormRequest** : `XxxRequest`
- **Policy** : `XxxPolicy`
- **Enum** : `XxxEnum` (ex: `BookingStatusEnum`)
- **Service** : `XxxService` (rare, explicite)

---

## 🧪 Tests

- Une Action = plusieurs tests si nécessaire
- Tests d’Action = priorité (tests métier)
- Tests HTTP = secondaires (routing, codes HTTP)

### À couvrir obligatoirement :

- cas nominal
- erreurs
- edge cases
- idempotence si applicable

### Bonnes pratiques :

- utiliser des factories (pas de données en dur)
- isoler la logique métier des tests HTTP

---

## 🚫 Interdits

- `dd()`, `dump()` en production
- accès direct à `request()` ou `Auth` dans une Action
- utilisation de chaînes magiques pour les statuts
- événements non typés
- commentaires inutiles (le code doit être auto-explicatif)
- logique métier dans les migrations ou seeders

---

## 🎯 Actions (bonnes pratiques)

- Signature explicite (éviter `array $data` si possible)
- Paramètres typés
- Retour typé (Model, DTO ou valeur claire)
- Aucune dépendance implicite (`request()`, `Auth`, globals)

---

## 📜 Logging

- Logger uniquement les événements utiles :
    - erreurs métier
    - événements critiques (paiement, refund)

- Ne jamais logger :
    - données sensibles (KYC, paiement, identité)

- Éviter le bruit (logs inutiles)

---

## 📌 Règle importante

Les règles d’architecture (Model, Service, responsabilités, etc.)
ne sont pas définies ici.

👉 Voir :

- `.adamas/ai/context/architecture.md`
- `.adamas/ai/context/method-rules.md`
