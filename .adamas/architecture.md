# 🧠 Architecture Rules — GP-Valise

---

## 🧱 Responsabilités des couches

### Controller

- Orchestration HTTP uniquement
- Appelle une Action
- Ne contient aucune logique métier
- Peut appeler Policy et Resource
- **Gestion des exceptions** :- Les exceptions métier levées par les Actions doivent être converties en réponses HTTP 4xx via une gestion centralisée Laravel ou, si nécessaire, au niveau Controller.

❌ Interdit :

- accès direct au Model pour logique métier
- manipulation de statuts
- logique conditionnelle complexe

---

### FormRequest

- Validation des données entrantes uniquement (présence, format, contraintes basiques)
- Aucune logique métier
- **Règle supplémentaire** : toute validation complexe qui nécessite de vérifier l’état d’une ressource (ex: « cette place de parking est-elle déjà réservée ? ») doit être déléguée à l’Action, pas faite dans le FormRequest.

---

### Policy

- Gère uniquement les droits d’accès
- Ne contient aucune logique métier

❌ Interdit :

- vérifier des transitions métier
- modifier des données

---

### Action

- Représente un cas d’usage métier complet
- Peut orchestrer :
    - Model
    - Enum
    - Event
    - Transaction DB
- Doit être testable isolément
- **Exceptions métier** : peut lever une `DomainException` (ou exception personnalisée) quand une règle métier est violée.
- **Idempotence** : recommandée si le cas d’usage le permet.

✅ Exemple :

- ReserveBooking
- ConfirmBooking
- RefundTransaction

---

### Enum

- Source de vérité des règles métier liées aux statuts

Peut contenir :

- transitions (canTransitionTo)
- règles métier simples (canBeCancelled, etc.)

❌ Interdit :

- accès base de données
- logique dépendant d’autres modèles

---

### Model

- Représente les données
- Peut contenir des helpers métier locaux

✅ Autorisé :

- getters métier simples
- calculs locaux (ex: volume, kg total)

❌ Interdit :

- orchestration de workflow
- appels à plusieurs agrégats
- effets de bord complexes

⚠️ Exemple limite (à surveiller) :

- transitionTo() → acceptable si purement local
- canBeUpdatedTo() → à challenger

---

### Service

- Utilisé uniquement si :
    - logique transverse réutilisée (ex: envoi de notification, appel API externe)
    - intégration externe (Stripe, API, etc.)
- **Règle supplémentaire** : un Service ne doit pas appeler une Action (sinon on casse l’orchestration métier). En revanche, une Action peut appeler un Service.

❌ Interdit :

- remplacer une Action
- contenir un use case unique

---

### Event / Listener (optionnel)

- Un événement peut être déclenché depuis une Action pour signaler un fait accompli.
- La logique transverse (ex: envoyer un email, mettre à jour un cache) est placée dans des Listeners.
- Les Listeners ne doivent pas contenir de logique métier critique (elle doit rester dans l’Action).

---

## ❌ Interdictions globales

- logique métier dans Controllers
- logique métier dans Policies
- accès DB dans Enum
- duplication Action / Service
- statuts hardcodés (bypass Enum)
- Un Service appelant une Action

---

## 🔍 Règles d’audit (OBLIGATOIRES)

Chaque module doit être vérifié sur :

1. Séparation des responsabilités
2. Localisation de la logique métier
3. Respect des Enums
4. Sécurité (Policy)
5. Testabilité (Action testable)
6. Performance (requêtes optimisées)

---

## 🧪 Standards de qualité

Chaque modification doit garantir :

- Cohérence métier
- Couverture test
- Absence de duplication
- Requêtes optimisées (pas de N+1)
- Idempotence si nécessaire
- Code lisible sans commentaire excessif

---

## ⚙️ Interaction avec l’IA

L’IA doit :

- commencer par un audit
- identifier les violations
- proposer des corrections AVANT code
- justifier chaque décision

❌ Interdit :

- générer du code directement
- ignorer les règles définies ici

---

## 🧠 Principe clé

> Un système mal structuré devient incontrôlable à mesure qu’il grandit.

Ce fichier garantit que :

→ toute évolution respecte une architecture stable
→ toute optimisation repose sur une base saine

---

## 📌 Règles critiques (à retenir)

- Une Action = un use case
- Un Service ≠ un fourre-tout
- Un Model ≠ un orchestrateur
- Un Controller ≠ un cerveau métier
- Une validation complexe → Action, pas FormRequest
- Une exception métier → levée dans l’Action, capturée dans le Controller
- Un Service n’appelle jamais une Action
