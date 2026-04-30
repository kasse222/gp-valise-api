# ✅ Review Checklist — GP-Valise

## 🎯 Objectif

Checklist à exécuter **avant toute PR, merge ou audit IA**.

Format volontairement simple :

- ✅ OK
- ❌ Violation
- ⚠️ À surveiller

---

## 🔁 Couches (séparation des responsabilités)

### Controller

- [ ] Appelle une seule Action par endpoint
- [ ] Aucune logique métier
- [ ] Utilise un FormRequest pour la validation
- [ ] Gère correctement les exceptions métier (HTTP 4xx)
- [ ] Policy appliquée (`$this->authorize`)

---

### FormRequest

- [ ] Validation d’entrée simple uniquement
- [ ] Aucune logique métier
- [ ] Pas de logique conditionnelle complexe

---

### Action

- [ ] Représente un seul use case métier
- [ ] Signature explicite (éviter `array $data`)
- [ ] Paramètres typés / retour typé
- [ ] Aucune dépendance implicite (`request()`, `Auth`)
- [ ] Lève une exception métier si nécessaire
- [ ] Utilise une transaction DB si multi-modèles
- [ ] Testable isolément
- [ ] Pas de duplication métier

---

### Policy

- [ ] Gère uniquement l’autorisation
- [ ] Aucune logique métier
- [ ] Aucune modification de données

---

### Enum

- [ ] Source de vérité des statuts
- [ ] Transitions centralisées (`canTransitionTo`)
- [ ] Aucun accès DB
- [ ] Aucune dépendance externe

---

### Model

- [ ] Helpers locaux uniquement
- [ ] Aucun appel à Action / Service
- [ ] Aucune orchestration multi-modèles
- [ ] Aucune API externe
- [ ] Pas de `canTriggerXxx()`

⚠️

- [ ] Méthodes avec `save()` / `update()` identifiées comme à risque

---

### Service

- [ ] Logique transverse uniquement (API, notification, etc.)
- [ ] N’appelle jamais une Action
- [ ] Ne remplace pas une Action

---

## 🔁 Qualité technique

### Sécurité

- [ ] Policy appliquée sur endpoints sensibles
- [ ] Pas de fuite de données sensibles
- [ ] Validation des entrées correcte
- [ ] Aucun bypass des règles d’accès

---

### Performance

- [ ] Pas de N+1 évident (`with()` si nécessaire)
- [ ] `lockForUpdate()` sur opérations critiques
- [ ] Transactions DB sur opérations multi-étapes

---

### Testabilité

- [ ] Action couverte : nominal + erreur + edge case
- [ ] Tests métier séparés des tests HTTP
- [ ] Aucun `dd()` / `dump()` dans le code

---

### Logging

- [ ] Logs uniquement pour événements utiles (paiement, erreur)
- [ ] Aucune donnée sensible loggée
- [ ] Pas de bruit inutile

---

## 🧪 Avant merge

- [ ] PHPStan / Larastan → 0 erreur
- [ ] Laravel Pint → OK
- [ ] Tests Pest → tous passent
- [ ] Aucune régression
- [ ] Branche atomique
- [ ] Commit clair (`type(scope): message`)
- [ ] PR documentée

---

## 🚦 Classification

| Gravité         | Cas                                                  |
| --------------- | ---------------------------------------------------- |
| 🔴 Critique     | bug métier, faille sécurité, incohérence statut      |
| 🟠 Important    | mauvaise séparation, duplication, logique mal placée |
| 🟡 Amélioration | lisibilité, naming, micro-optimisation               |

---

## 📌 Règle de décision

Avant correction :

1. Violation `.adamas` ?
2. Impact réel ?
3. Métier ou technique ?
4. Simplifie ou complexifie ?

> Si ça complexifie → ne pas corriger maintenant

---

## 🚫 Interdits

- Proposer du code sans audit
- Valider un code "parce qu’il fonctionne"
- Ignorer les Enums
- Déplacer de la logique sans justification
- Refactor incompatible roadmap v5

---

## 🔗 Références

- Architecture : `.adamas/ai/context/architecture.md`
- Method rules : `.adamas/ai/context/method-rules.md`
- Coding standards : `.adamas/ai/capabilities/coding/standards.md`
