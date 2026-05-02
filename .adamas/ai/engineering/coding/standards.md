# 🧠 1. Audit rapide de ton fichier

### 👍 Points solides

- PSR-12
- Typage strict
- Naming clair
- Priorité aux tests métier
- Interdits pertinents

---

### ❌ Ce qui manque (important)

Tu n’as pas encore :

1. **Transactions DB obligatoires**
2. **Concurrence (`lockForUpdate`)**
3. **Idempotence (critique fintech)**
4. **Enums obligatoires (anti string)**
5. **Observabilité (correlation_id)**
6. **Structure des dossiers**
7. **Gestion des exceptions**
8. **Règles sur async / jobs**
9. **Règles sur calculs financiers**

👉 En l’état, ton fichier ne protège pas ton système.

---

# 🔥 2. Version corrigée (niveau senior)

👉 Remplace ton fichier par ça :

````md
# 🧠 Coding Standards — GP-Valise

---

## 🎯 Objectif

Définir les règles de développement pour garantir :

- cohérence métier
- lisibilité
- sécurité
- performance
- testabilité
- scalabilité

---

## 🧼 Style de code

- PSR-12 obligatoire
- `declare(strict_types=1);`
- types stricts partout (params + return)
- early return (éviter `else`)
- code lisible > code intelligent

---

## 📁 Nommage

- Controller → `XxxController`
- Action → `VerbeNom` (ConfirmBooking)
- FormRequest → `XxxRequest`
- Policy → `XxxPolicy`
- Enum → `XxxEnum`
- Service → `XxxService` (rare et explicite)
- Job → `VerbeNomJob`

---

## 🧱 Structure

```txt
app/
  Actions/
  Models/
  Enums/
  Services/
  Jobs/
  Events/
  Policies/
```
````

---

## 🧪 Tests

### Priorité

1. Actions (logique métier)
2. Services critiques
3. HTTP (secondaire)

---

### À couvrir obligatoirement

- cas nominal
- erreurs
- edge cases
- idempotence
- concurrence (si applicable)

---

### Bonnes pratiques

- factories uniquement (pas de données hardcodées)
- tests isolés
- tests déterministes

---

## 🔒 Transactions DB

### Obligatoire si :

- modification multi-modèles
- opération financière
- opération critique

```php
DB::transaction(fn () => ...);
```

---

## ⚠️ Concurrence

Utiliser :

```php
->lockForUpdate()
```

Cas obligatoires :

- réservation
- paiement
- refund
- payout

---

## 🔁 Idempotence

Obligatoire pour :

- webhook
- paiement
- payout
- refund

Pattern :

```php
if ($alreadyProcessed) {
    return;
}
```

---

## 🧠 Enums (règle critique)

❌ Interdit :

```php
if ($booking->status === 'CONFIRMEE')
```

✅ Obligatoire :

```php
if ($booking->status === BookingStatusEnum::CONFIRMEE)
```

---

## 💰 Calculs financiers

### Règle absolue

Aucun calcul dans :

- Controller
- Model
- Policy

👉 Uniquement dans :

```txt
TransactionAmountCalculator
```

---

## 🚫 Interdits critiques

- `dd()`, `dump()` en prod
- accès `request()` dans Action
- accès `Auth` dans Action
- statuts en string
- logique métier dans Controller
- logique métier dans Policy
- Service fourre-tout
- duplication logique
- absence de transaction DB
- absence d’idempotence

---

## 🎯 Actions (bonnes pratiques)

- signature explicite
- paramètres typés
- retour typé
- aucune dépendance globale

---

## ⚠️ Exceptions

- toujours métier (ValidationException ou DomainException)
- jamais silencieuses
- jamais catch sans raison

---

## 📜 Logging

Logger uniquement :

- erreurs métier
- événements critiques (paiement, refund)
- retry / échec

❌ Ne jamais logger :

- données sensibles
- informations inutiles

---

## 🔍 Observabilité

### Obligatoire

- `correlation_id` présent dans :
    - requêtes HTTP
    - logs
    - jobs
    - webhooks

Objectif :

```txt
tracer une requête de bout en bout
```

---

## ⚙️ Jobs

### Règles

- un Job appelle une Action
- jamais de logique métier complexe dans Job
- retry + backoff obligatoires
- gestion des erreurs + alerting

---

## 📦 Resources

- formatage uniquement
- aucun accès DB
- aucune logique métier

---

## 🧠 Principe clé

> Le code doit refléter le métier

Si ton code ne correspond pas aux règles `.adamas`, il est incorrect.

---

## 📌 Références

- method-rules.md
- architecture.md
- business-logic.md

```

```
