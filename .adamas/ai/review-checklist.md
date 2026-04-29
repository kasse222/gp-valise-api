# ✅ Review Checklist — GP-Valise

## 🎯 Objectif

Cette checklist doit être exécutée **avant toute PR, avant tout merge, avant tout audit IA**.

Elle est volontairement courte et binaire : ✅ OK / ❌ Violation / ⚠️ À surveiller.

---

## 🔁 Checklist — Couches

### Controller

- [ ] N'appelle qu'une seule Action par endpoint
- [ ] Aucune logique métier présente
- [ ] Délègue la validation au FormRequest
- [ ] Capture les exceptions métier et retourne le bon code HTTP
- [ ] Appelle la Policy correctement (`$this->authorize`)

### FormRequest

- [ ] Validation d'entrée simple uniquement (format, présence, contraintes basiques)
- [ ] Aucune vérification d'état métier (`isAvailable`, `alreadyBooked`, etc.)
- [ ] Pas de logique conditionnelle complexe

### Action

- [ ] Représente **un seul use case** métier
- [ ] Signature explicite (pas de tableau générique `array $data`)
- [ ] Paramètres typés, retour typé
- [ ] Pas de dépendance à `request()` ou `Auth::user()` directement
- [ ] Lève une exception métier si règle violée
- [ ] Ouvre une transaction DB si plusieurs modèles modifiés
- [ ] Testable isolément (sans HTTP)
- [ ] Pas de logique métier dupliquée dans une autre Action

### Policy

- [ ] Contient **uniquement** de la logique d'autorisation
- [ ] Aucune transition de statut
- [ ] Aucune modification de données
- [ ] Aucune logique métier

### Enum

- [ ] Source de vérité pour les statuts et transitions
- [ ] Contient `canTransitionTo()` si transitions présentes
- [ ] Aucun accès base de données
- [ ] Aucune dépendance à d'autres modèles

### Model

- [ ] Helpers locaux uniquement (`isConfirmed()`, `refundableAmount()`, etc.)
- [ ] Aucune orchestration multi-modèles
- [ ] Aucun appel à un Service ou une Action
- [ ] Aucune API externe appelée
- [ ] Pas de `canTriggerXxx()` (→ doit aller dans une Action)
- [ ] Si `save()` / `update()` présent → méthode marquée ⚠️ à risque

### Service

- [ ] Logique **transverse uniquement** (notification, API externe)
- [ ] N'appelle **jamais** une Action
- [ ] N'est pas utilisé pour remplacer une Action

---

## 🔁 Checklist — Qualité technique

### Sécurité

- [ ] Policy appliquée sur chaque endpoint sensible
- [ ] Données sensibles non loggées
- [ ] Pas de bypass des règles d'accès
- [ ] Entrées validées avant traitement

### Performance

- [ ] Pas de requête N+1 évidente (`with()` utilisé si relations chargées)
- [ ] `lockForUpdate()` sur les opérations critiques concurrentes
- [ ] Transactions DB sur les opérations multi-étapes

### Testabilité

- [ ] Chaque Action a au moins un test : nominal + erreur + edge case
- [ ] Tests d'Action = tests métier (pas tests HTTP)
- [ ] Tests HTTP = vérification code HTTP + routage uniquement
- [ ] Pas de `dd()` / `dump()` dans le code

### Logging

- [ ] Seuls les événements critiques sont loggés (paiement, refund, erreur métier)
- [ ] Aucune donnée sensible loggée (identité, KYC, CB)

---

## 🧪 Checklist — Avant merge

```
[ ] PHPStan / Larastan → 0 erreur niveau configuré
[ ] Laravel Pint → code formaté PSR-12
[ ] Pest → tous les tests passent
[ ] Aucun test existant en échec (non-régression)
[ ] Branche atomique (un seul objectif)
[ ] Commit message explicite
[ ] PR description : objectif + fichiers modifiés + tests lancés
```

---

## 🚦 Classification rapide

| Situation                               | Gravité         |
| --------------------------------------- | --------------- |
| Bug métier / perte de données possible  | 🔴 Critique     |
| Faille sécurité / bypass Policy         | 🔴 Critique     |
| Incohérence de statut (bypass Enum)     | 🔴 Critique     |
| Logique métier dans Controller / Policy | 🟠 Important    |
| Orchestration dans un Model             | 🟠 Important    |
| Service appelant une Action             | 🟠 Important    |
| Duplication de logique                  | 🟠 Important    |
| Naming peu clair                        | 🟡 Amélioration |
| Méthode trop longue                     | 🟡 Amélioration |
| Optimisation de requête mineure         | 🟡 Amélioration |

---

## 📌 Règles de décision rapide

Avant de corriger, répondre à ces 4 questions :

1. Est-ce une violation des règles `.adamas` ?
2. Est-ce un problème métier ou technique ?
3. Quel est l'impact réel si on ne corrige pas ?
4. La correction simplifie-t-elle ou complexifie-t-elle le système ?

> Si la correction complexifie → ne pas corriger maintenant.

---

## 🚫 Rappel — Interdits pendant une review

- Proposer du code sans audit préalable
- Valider un code parce qu'il "fonctionne"
- Ignorer les Enums
- Déplacer de la logique sans justification
- Proposer un refactor incompatible avec la roadmap v5
