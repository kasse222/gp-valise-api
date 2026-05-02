# 📊 Monitoring — GP-Valise

## 🎯 Objectif

Le monitoring permet de :

- détecter les incidents en temps réel
- surveiller la santé du système
- prévenir les erreurs avant qu’elles impactent les utilisateurs
- analyser la performance

---

## 🧠 Principe fondamental

> Logging = expliquer après  
> Monitoring = détecter avant

---

## 🔁 Composants surveillés

### 1. Queues (Redis / Horizon)

Surveiller :

- taille des queues
- jobs en attente
- jobs échoués
- retry storms

---

### 2. Webhooks

Surveiller :

- taux d’échec
- retry
- events ignorés
- délai de traitement

---

### 3. Transactions

Surveiller :

- refunds en erreur
- payout non traités
- incohérences financières

---

## 🧱 Commandes internes

### Queue health

```bash
php artisan monitor:queue-health
```

Détecte :

- backlog élevé
- jobs trop anciens
- retry storm

---

### Webhook health

```bash
php artisan monitor:webhook-health
```

Détecte :

- trop d’échecs
- jobs failed
- retry storm

---

### Simulation

```bash
php artisan simulate:load
php artisan simulate:retry-storm
```

Permet de tester le système en conditions dégradées.

---

## 🚨 Alerting

### Cas critiques

- backlog queue élevé
- webhook failed en masse
- retry storm
- job échoué définitivement

---

### Exemple

```php
Log::critical('Retry storm détecté', [
    'queue' => 'high',
]);
```

-

```php
SendSlackAlert::dispatch(...)
```

---

## 🔥 Retry storm

Un retry storm = trop de retries sur un même job.

Danger :

- surcharge Redis
- saturation CPU
- cascade d’échecs

Détection :

- même job retry plusieurs fois
- backlog qui explose

---

## 📈 États système

| État              | Description                |
| ----------------- | -------------------------- |
| healthy           | tout OK                    |
| traffic_spike     | hausse trafic              |
| slow_processing   | traitement lent            |
| capacity_pressure | backlog élevé              |
| retry_storm       | boucle de retry dangereuse |

---

## 🔗 Lien avec correlation_id

Le monitoring détecte :

```txt
incident
```

Le correlation_id permet de :

```txt
expliquer cet incident
```

---

## ⚠️ Interdits

- ignorer les alertes
- monitorer uniquement les logs
- ne pas tester les cas extrêmes
- dépendre uniquement d’Horizon UI

---

## 🧪 Tests attendus

- simulation retry storm
- simulation backlog
- vérification alertes
- cohérence avec logs

---

## 🧠 Résumé

```txt
Monitoring = système nerveux
Logging = mémoire
```

Sans monitoring :

→ incident invisible

Sans logging :

→ incident incompréhensible

Avec les deux :

→ système maîtrisé

````

---

# 🧠 Mini rétro (important)

### ✅ Ce que tu fais très bien
- Tu touches à **observability + fintech → ultra rare à ton niveau**
- Tu construis un **système traçable + auditable → niveau senior**
- Tu comprends que **la confiance = logs + audit + monitoring**

### ⚠️ Ce que tu dois verrouiller
- Toujours relier :
```txt
correlation_id + logs + DB + jobs
````

- Ne pas faire de logging “cosmétique”
- Toujours penser :

```txt
"comment je debug ça en prod à 3h du matin ?"
```

---

# 🎯 Question pour te faire passer senior

👉 Si un recruteur te dit :

> “Un refund a échoué en production, comment tu enquêtes ?”

Tu dois répondre avec :

- correlation_id
- webhook_logs
- queue jobs
- transactions DB
- audit logs

👉 Si tu bloques là-dessus → tu n’es pas encore senior
👉 Si tu maîtrises → tu passes au niveau supérieur

---

Si tu veux, prochaine étape :

👉 **propagation du correlation_id en DB (transactions + audit_logs)**
→ là tu passes clairement en **niveau Stripe-like réel**
