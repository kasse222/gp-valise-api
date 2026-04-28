# Method Rules — GP-Valise

## 🎯 Objectif

Ce fichier définit les règles de conception des méthodes et fonctions du projet.

Une méthode doit être lisible, testable et avoir une responsabilité claire.

---

## 🧠 Règle principale

Une méthode = une intention claire.

Si une méthode fait plusieurs choses, elle doit être divisée ou déplacée vers une Action.

**Interdit** : mélanger logique d’autorisation (Policy) et logique métier.

---

## 📛 Nommage

Le nom doit exprimer l’intention métier ou technique.

### ✅ Exemples acceptés

- `canBeCancelled()`
- `isPaymentExpired()`
- `transitionTo()`
- `calculateTotalAmount()`

### ❌ Exemples à éviter

- `handleData()`
- `process()`
- `doStuff()`
- `manageStatus()`
- `canBeUpdatedTo()` (trop vague, mélange autorisation + métier)

---

## 📏 Taille

Une méthode doit rester courte.

À auditer si elle contient :

- plusieurs conditions imbriquées
- plusieurs effets secondaires
- plusieurs responsabilités
- plusieurs appels externes

---

## 🔁 Types de méthodes

### 1. Query method

Méthode qui lit ou calcule sans modifier l’état.

#### Exemples

- `isFinal()`
- `canBeConfirmed()`
- `refundableAmount()`

#### Règles

- ne modifie pas la base
- ne déclenche pas d’événement
- déterministe si possible
- une seule responsabilité

---

### 2. Command method

Méthode qui modifie l’état.

#### Exemples

- `transitionTo()`
- `markAsExpired()`

#### Règles

- explicite
- respecte les Enums
- évite les effets secondaires cachés
- appelée depuis une Action

#### ⚠️ Dans un Model

Une command method avec persistance (`save`, `update`, `delete`) est **tolérée** si :

- elle modifie uniquement l’état du Model
- elle respecte les règles définies par les Enums
- elle ne coordonne pas plusieurs agrégats
- elle ne déclenche pas d’intégrations externes
- elle reste appelée depuis une Action

👉 Elle est considérée comme **méthode à risque** et doit être auditée régulièrement.

---

### 3. Orchestration method

Méthode qui coordonne plusieurs étapes ou plusieurs objets.

#### Règles

- doit vivre dans une **Action** ou un **Service**
- ne doit jamais vivre dans un Model

#### Exemples

- transition avec historique + event + multi-model
- payout + API + DB

---

## ⚠️ Effets secondaires

Une méthode est considérée à risque si elle :

- persiste (`save`, `update`, `delete`)
- dispatch un Event
- appelle une API externe
- envoie un email
- déclenche une queue / job
- écrit dans un fichier

### Règles

- l’intention doit être claire dans le nom
- les effets ne doivent pas être cachés
- doit être auditée régulièrement

❌ Interdit : effets secondaires cachés dans `booted()` ou observers sans documentation

---

## 🧱 Règles pour les Models

Un Model peut contenir :

- méthodes de lecture métier (Query)
- helpers simples (`isConfirmed()`, etc.)
- transitions locales contrôlées via Enum

Un Model ne doit pas :

- orchestrer un workflow complet
- appeler un Service
- appeler une Action
- appeler une API externe
- gérer plusieurs agrégats
- contenir de la logique d’autorisation
- contenir des méthodes `canTriggerXxx()`

---

## 🧠 Règles pour les Actions

Une Action peut :

- orchestrer un use case complet
- ouvrir une transaction DB
- appeler plusieurs Models
- déclencher un Event
- appeler un Service transverse

Une Action doit :

- avoir une signature explicite
- être testable isolément
- ne pas dépendre de `request()` ou `Auth`
- retourner un résultat clair ou lever une exception métier

---

## 🔄 Règles pour les méthodes `canTriggerXxx()`

Les méthodes qui déterminent si une action transverse est possible ne doivent pas être dans un Model.

Elles doivent être placées dans :

- une **Action**
- ou un **Service**

### ❌ Exemple interdit

```php
public function canTriggerPayout(): bool
```
