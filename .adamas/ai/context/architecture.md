# 🧠 Architecture Rules — GP-Valise

---

## 🎯 Objectif

Définir les responsabilités des couches du projet afin de garantir :

- une séparation claire des responsabilités
- une logique métier centralisée et testable
- une architecture stable et scalable

---

## 🧱 Responsabilités des couches

### Controller

- Orchestration HTTP uniquement
- Appelle une seule Action par endpoint
- Peut appeler Policy et Resource
- Ne contient aucune logique métier

#### Gestion des exceptions

Les exceptions métier levées par les Actions doivent être converties en réponses HTTP adaptées (4xx) via :

- le handler global Laravel (recommandé)
- ou, si nécessaire, au niveau Controller

❌ Interdit :

- logique métier
- manipulation directe des statuts
- logique conditionnelle complexe
- accès Model pour logique métier

---

### FormRequest

- Validation des données entrantes uniquement
- Contraintes simples (format, présence, règles basiques)
- Aucune logique métier

⚠️

Toute validation dépendant de l’état métier (ex: disponibilité, réservation existante)  
→ doit être gérée dans l’Action

---

### Policy

- Gère uniquement les droits d’accès

❌ Interdit :

- logique métier
- transitions de statut
- modification de données

---

### Action

- Représente un cas d’usage métier complet
- Point central de la logique métier

Peut orchestrer :

- Model
- Enum
- Event
- Transaction DB
- Service (si logique transverse)

Doit :

- être testable isolément
- avoir une signature explicite
- lever des exceptions métier si nécessaire

⚠️ Idempotence recommandée pour les opérations critiques

---

### Enum

- Source de vérité des statuts et transitions

Peut contenir :

- transitions (`canTransitionTo`)
- règles métier simples

❌ Interdit :

- accès base de données
- dépendances externes
- logique multi-modèles

---

### Model

- Représente les données
- Contient des helpers métier locaux uniquement

Autorisé :

- getters métier simples
- calculs locaux

❌ Interdit :

- orchestration de workflow
- appels à plusieurs agrégats
- effets de bord complexes
- dépendances externes (Service, API)

⚠️ Cas limites (à surveiller) :

- `transitionTo()` → acceptable si purement local (sans persistance ni effets secondaires)
- autres méthodes de décision métier → à challenger (voir `method-rules.md`)

---

### Service

- Utilisé uniquement pour la logique transverse :
    - intégration externe (Stripe, API, etc.)
    - logique réutilisée entre plusieurs Actions

Règles :

- une Action peut appeler un Service
- un Service ne doit jamais appeler une Action

❌ Interdit :

- remplacer une Action
- contenir un use case métier complet

---

### Event / Listener (optionnel)

- Les événements sont déclenchés depuis les Actions
- Les Listeners gèrent la logique transverse (email, cache, etc.)

❌ Interdit :

- logique métier critique dans les Listeners

---

## ❌ Interdictions globales

- logique métier dans Controller ou Policy
- accès DB dans Enum
- duplication Action / Service
- statuts hardcodés (bypass Enum)
- Service appelant une Action

---

## 🔍 Audit (référence)

L’audit doit vérifier :

- séparation des responsabilités
- localisation de la logique métier
- respect des Enums
- sécurité (Policy)
- testabilité (Action)
- performance (requêtes)

👉 Voir : `.adamas/ai/capabilities/audit/methodology.md`

---

## 🧪 Standards de qualité

Chaque modification doit garantir :

- cohérence métier
- couverture test
- absence de duplication
- requêtes optimisées
- idempotence si nécessaire
- code lisible

---

## ⚙️ Interaction avec l’IA

L’IA doit :

- commencer par un audit
- identifier les violations
- proposer des corrections avant code
- justifier chaque décision

❌ Interdit :

- générer du code directement
- ignorer ces règles

---

## 🧠 Principe clé

> Un système mal structuré devient incontrôlable à mesure qu’il grandit.

---

## 📌 Règles critiques

- Une Action = un use case métier
- Un Service ≠ un fourre-tout
- Un Model ≠ un orchestrateur
- Un Controller ≠ un cerveau métier
- Validation complexe → Action, pas FormRequest
- Exception métier → levée dans l’Action, transformée en HTTP
- Un Service n’appelle jamais une Action

---

## 🔗 Références

- Méthodes : `.adamas/ai/context/method-rules.md`
- Coding : `.adamas/ai/capabilities/coding/standards.md`
- Review : `.adamas/ai/capabilities/review/checklist.md`
