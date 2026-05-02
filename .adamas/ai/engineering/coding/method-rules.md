# 🧠 Method Rules — GP-Valise

## 🎯 Objectif

Définir où placer les méthodes et la logique dans le projet GP-Valise.

Ce fichier répond à une question simple :

> Cette méthode doit-elle être dans un Model, une Action, un Service, un Enum ou ailleurs ?

---

## 🧱 Principe fondamental

> Une méthode doit vivre dans la couche qui porte sa responsabilité réelle.

Le placement d’une méthode doit préserver :

- la lisibilité ;
- la testabilité ;
- la séparation des responsabilités ;
- la cohérence métier ;
- la sécurité des flux critiques.

---

## ✅ Model

Un Model peut contenir des méthodes locales, simples et liées à ses propres données.

### Autorisé

- getters métier simples ;
- scopes ;
- relations ;
- helpers locaux ;
- vérifications sur ses propres attributs.

Exemples :

```php
$booking->isFinal();
$booking->isAwaitingPayment();
$transaction->isSucceeded();
$trip->isPast();
```

### Interdit

- orchestrer plusieurs modèles ;
- déclencher un workflow complet ;
- appeler une Action ;
- appeler un Service externe ;
- effectuer un calcul financier critique ;
- prendre une décision métier globale.

### Règle

Si la méthode a besoin de plusieurs agrégats ou produit un effet métier important, elle ne doit pas être dans le Model.

---

## ✅ Enum

Un Enum peut contenir les règles liées aux statuts et transitions.

### Autorisé

- `label()` ;
- `color()` ;
- `isFinal()` ;
- `canTransitionTo()` ;
- règles pures basées sur la valeur de l’Enum.

Exemples :

```php
BookingStatusEnum::EN_PAIEMENT->canTransitionTo(BookingStatusEnum::CONFIRMEE);
TransactionStatusEnum::COMPLETED->isFinal();
```

### Interdit

- accès DB ;
- appel à Model ;
- appel à Service ;
- logique multi-entités ;
- logique dépendant d’un utilisateur ou d’un contexte externe.

---

## ✅ Action

Une Action porte un use case métier complet.

### Autorisé

- orchestration métier ;
- transaction DB ;
- verrouillage `lockForUpdate()` ;
- appel à Service transverse ;
- dispatch Event ;
- création/modification de plusieurs modèles ;
- levée d’exception métier.

Exemples :

```php
ReserveBooking
ConfirmBooking
CreatePayoutTransaction
AdminRefundTransaction
HandlePaymentWebhook
```

### Règle

Si une méthode modifie l’état du système ou représente un use case, elle doit probablement être une Action.

---

## ✅ Service

Un Service porte une logique transverse, réutilisable et non spécifique à un seul endpoint.

### Autorisé

- calcul financier transversal ;
- intégration externe ;
- observabilité ;
- audit integrity ;
- notification ;
- health monitoring.

Exemples :

```php
TransactionAmountCalculator
TransactionEligibilityService
AuditLogIntegrityService
QueueHealthService
SlackNotifier
```

### Interdit

- remplacer une Action ;
- contenir un use case métier complet ;
- appeler une Action ;
- devenir un fourre-tout.

### Règle

Un Service répond à “comment calculer / vérifier / intégrer”.
Une Action répond à “quoi faire dans ce use case”.

---

## ✅ Policy

Une Policy décide uniquement si un utilisateur a le droit d’accéder à une action.

### Autorisé

- rôle ;
- ownership simple ;
- permissions simples.

### Interdit

- transition métier ;
- calcul financier ;
- modification de données ;
- appel à Action ou Service métier.

---

## ✅ FormRequest

Un FormRequest valide la forme de l’entrée HTTP.

### Autorisé

- required ;
- string ;
- integer ;
- exists ;
- enum ;
- taille ;
- format.

### Interdit

- vérifier une capacité métier ;
- vérifier un payout/refund existant ;
- contrôler une transition complexe ;
- faire une orchestration.

---

## ✅ Job

Un Job transporte et exécute un traitement asynchrone.

### Autorisé

- appeler une Action ;
- porter un `correlation_id` ;
- gérer retry/backoff ;
- logger les erreurs ;
- dispatcher une alerte.

### Interdit

- contenir une logique métier complexe ;
- contourner une Action ;
- dépendre directement de `request()`.

---

## ✅ Resource

Une Resource transforme les données pour l’API.

### Autorisé

- formatage de sortie ;
- regroupement de champs ;
- exposition contrôlée ;
- `whenLoaded`.

### Interdit

- requêtes DB cachées ;
- logique métier ;
- calcul financier ;
- décisions d’autorisation.

---

## 🔥 Règles de décision rapide

### Question 1

La méthode ne lit que l’état local d’un objet ?

→ Model ou Enum.

### Question 2

La méthode change l’état du système ?

→ Action.

### Question 3

La méthode est utilisée par plusieurs Actions ?

→ Service.

### Question 4

La méthode décide si un user a le droit ?

→ Policy.

### Question 5

La méthode transforme une réponse API ?

→ Resource.

### Question 6

La méthode traite de l’async ?

→ Job + Action.

---

## ⚠️ Cas sensibles GP-Valise

### Booking

- `isFinal()` → Model
- `canTransitionTo()` → Enum
- `confirm booking` → Action
- `expire booking` → Action
- `calculate available capacity` → Action ou query dédiée si multi-modèles

### Transaction

- `isSucceeded()` → Model
- `canCreatePayout()` → Service d’éligibilité
- `calculateRefundAmount()` → Calculator
- `create refund` → Action

### Audit

- `verifyLog()` → Service
- `seal()` → Service
- `create audit log` → Action ou dans l’Action critique concernée

### Observability

- `correlation_id` HTTP → Middleware
- `correlation_id` Job → propriété du Job
- `correlation_id` DB → propagé explicitement depuis Action/Job

---

## 🚫 Anti-patterns interdits

- Model obèse ;
- Service fourre-tout ;
- Action qui fait tout sans découpage ;
- Policy qui décide du métier ;
- FormRequest qui interroge trop le domaine ;
- Resource qui calcule ;
- Job qui remplace une Action ;
- Enum qui accède à la DB.

---

## 🧠 Principe clé

> Le bon emplacement d’une méthode se déduit de sa responsabilité, pas de sa facilité d’accès.

Si le placement d’une méthode rend les tests difficiles, c’est souvent qu’elle est au mauvais endroit.

```

```
