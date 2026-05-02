# 🧠 Architecture Rules — GP-Valise

---

## 🎯 Objectif

Définir les responsabilités des couches du projet afin de garantir :

- séparation stricte des responsabilités ;
- centralisation de la logique métier ;
- testabilité maximale ;
- cohérence métier et financière ;
- compatibilité async (webhooks, jobs) ;
- évolutivité vers une architecture fintech (ledger, multi-PSP).

---

## 🧱 Responsabilités des couches

---

### Controller

Responsabilités :

- orchestration HTTP uniquement ;
- appelle une seule Action par endpoint ;
- applique Policy ;
- retourne Resource.

Peut gérer :

- mapping HTTP (request → action → response) ;
- conversion des erreurs en réponses HTTP.

❌ Interdit :

- logique métier ;
- manipulation directe des statuts ;
- accès Model pour logique métier ;
- conditions métier complexes.

---

### FormRequest

Responsabilités :

- validation des données entrantes (syntaxe, format).

Autorisé :

- required / nullable ;
- format (email, uuid…) ;
- règles simples.

❌ Interdit :

- validation métier (ex : capacité, statut, ownership dynamique) ;
- accès base de données complexe.

👉 Toute validation dépendant du métier → Action.

---

### Policy

Responsabilités :

- gestion des droits d’accès.

Autorisé :

- vérifier rôle ;
- vérifier ownership simple.

❌ Interdit :

- logique métier ;
- modification de données ;
- décisions basées sur des transitions complexes.

---

### Action (cœur du système)

Responsabilités :

- représente un use case métier complet ;
- point central de la logique métier.

Peut orchestrer :

- Model ;
- Enum ;
- Transaction DB ;
- Event ;
- Service.

Doit :

- être testable isolément ;
- avoir une signature explicite ;
- lever des exceptions métier ;
- garantir la cohérence métier.

---

### ⚠️ Règles critiques pour les Actions

- une Action = un use case métier ;
- aucune logique financière hors Action ou Calculator ;
- opérations critiques sous transaction DB ;
- idempotence obligatoire si risque de double exécution ;
- usage de `lockForUpdate()` si concurrence possible.

---

### Enum

Responsabilités :

- source de vérité des statuts et transitions.

Autorisé :

- règles de transition ;
- helpers métier simples (`isFinal`, `canBeCancelled`, etc.).

❌ Interdit :

- accès DB ;
- dépendances externes ;
- logique multi-modèles.

---

### Model

Responsabilités :

- représenter les données ;
- exposer des helpers métier locaux.

Autorisé :

- getters métier ;
- calculs simples ;
- scopes.

❌ Interdit :

- orchestration métier ;
- appels multi-entités ;
- logique financière ;
- effets de bord.

---

### Service

Responsabilités :

- logique transverse uniquement.

Exemples :

- PaymentProvider ;
- SlackNotifier ;
- AuditLogIntegrityService ;
- QueueHealthService.

Règles :

- appelé par Action uniquement ;
- jamais source de vérité métier.

❌ Interdit :

- remplacer une Action ;
- contenir un use case complet ;
- appeler une Action.

---

### Job (Async)

Responsabilités :

- exécuter un traitement asynchrone ;
- déléguer à une Action.

Exemple :

```php
ProcessPaymentWebhook → HandlePaymentWebhook
```

Doit :

- être idempotent ;
- gérer retry ;
- gérer timeout ;
- logger les erreurs.

❌ Interdit :

- logique métier complexe dans le Job.

---

### Event / Listener

Responsabilités :

- propagation d’événements métier ;
- effets secondaires non critiques.

Exemples :

- notifications ;
- logs ;
- analytics.

❌ Interdit :

- logique métier critique ;
- modification d’état central.

---

## 🔒 Règles financières (critique GP-Valise)

- Transaction = source de vérité ;
- aucune décision basée uniquement sur Booking ;
- aucune modification après statut final ;
- `PAYOUT ⊕ REFUND` obligatoire ;
- aucun calcul financier hors Calculator ;
- toutes opérations financières dans une transaction DB.

---

## 🔄 Concurrence

Toutes les opérations critiques doivent :

- utiliser transaction DB ;
- utiliser `lockForUpdate()` ;
- être idempotentes ;
- vérifier les invariants avant écriture.

Cas critiques :

- réservation capacité ;
- confirmation booking ;
- payout ;
- refund ;
- webhook.

---

## 🔍 Observabilité (nouveau niveau)

Chaque flow doit être traçable via :

- correlation_id ;
- logs Laravel ;
- audit logs ;
- webhook logs.

Objectif :

```txt
retracer une requête de bout en bout (API → Job → DB)
```

---

## 🧾 Audit obligatoire

Toute modification doit suivre :

👉 `.adamas/ai/governance/methodology.md`

---

## ❌ Interdictions globales

- logique métier dans Controller ou Policy ;
- accès DB dans Enum ;
- duplication Action / Service ;
- statuts hardcodés ;
- Service appelant une Action ;
- calcul financier hors Calculator ;
- absence d’idempotence sur flux critique ;
- modification d’une transaction finalisée.

---

## 🧪 Standards de qualité

Chaque modification doit garantir :

- cohérence métier ;
- cohérence financière ;
- testabilité ;
- absence de duplication ;
- performance correcte ;
- logs utiles ;
- idempotence si nécessaire.

---

## ⚙️ Interaction avec l’IA

L’IA doit :

1. commencer par un audit ;
2. identifier les violations ;
3. proposer une solution ;
4. justifier les choix ;
5. seulement ensuite proposer du code.

❌ Interdit :

- générer du code directement ;
- ignorer `.adamas`.

---

## 🧠 Principe clé

> Une architecture mal définie devient incontrôlable à l’échelle.

---

## 📌 Règles fondamentales

- Une Action = un use case ;
- Un Service = logique transverse ;
- Un Model ≠ orchestrateur ;
- Un Controller ≠ logique métier ;
- Validation complexe → Action ;
- Finance → Transaction uniquement ;
- Async → Job → Action ;
- Audit obligatoire pour actions sensibles.

---

## 🔗 Références

- Méthodologie : `.adamas/ai/governance/methodology.md`
- Coding : `.adamas/ai/engineering/coding/standards.md`
- Review : `.adamas/ai/engineering/review/checklist.md`
- Observabilité : `.adamas/ai/observability/*`

```

```
