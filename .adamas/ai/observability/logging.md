# 🧾 Logging — GP-Valise

## 🎯 Objectif

Le logging permet de :

- comprendre ce qui se passe dans le système
- diagnostiquer un incident
- tracer les actions critiques
- corréler les événements via `correlation_id`

> Un bon log explique un bug sans debugger.

---

## 🧠 Principe fondamental

> Logguer ce qui est utile, pas tout.

Un bon log :

- apporte de la valeur
- est exploitable
- est corrélé (`correlation_id`)
- est structuré

---

## 🔁 Types de logs

### 1. Logs métier

Exemples :

- création d’un booking
- refund déclenché
- payout généré
- override admin

```php
Log::info('Booking confirmed', [
    'booking_id' => $booking->id,
    'user_id' => $booking->user_id,
]);
```

````

---

### 2. Logs techniques

Exemples :

- erreur DB
- exception
- timeout
- retry

```php
Log::error('Transaction introuvable', [
    'provider_transaction_id' => $providerId,
]);
```

---

### 3. Logs critiques

Exemples :

- webhook échoué définitivement
- incohérence financière
- violation d’invariant

```php
Log::critical('WEBHOOK DEFINITIVEMENT ECHOUE', [...]);
```

---

## 🔗 Correlation ID

Tous les logs doivent contenir :

```php
Log::withContext([
    'correlation_id' => $correlationId,
]);
```

Objectif :

```txt
reconstruire un flux complet
```

---

## 🧱 Structure recommandée

Toujours utiliser un format structuré :

```php
Log::info('Refund processed', [
    'booking_id' => $booking->id,
    'transaction_id' => $transaction->id,
    'amount' => $transaction->amount,
]);
```

❌ Mauvais :

```php
Log::info("Refund OK");
```

---

## 📊 Niveaux de logs

| Niveau     | Usage                                |
| ---------- | ------------------------------------ |
| `debug`    | local uniquement                     |
| `info`     | événement normal                     |
| `warning`  | anomalie non bloquante               |
| `error`    | erreur métier / technique            |
| `critical` | incident grave (financier / système) |

---

## 🚫 Interdits

- logger des données sensibles :
    - email
    - téléphone
    - KYC
    - token
    - carte bancaire

- logger sans contexte

- logger du bruit inutile

- logger dans les boucles intensives

---

## ⚠️ Cas critiques à logger

### Finance

- création transaction
- refund
- payout
- erreur webhook

---

### Webhook

- received
- processed
- ignored
- failed

---

### Admin actions

- refund override
- modification critique

---

## 🧪 Tests attendus

- logs présents sur erreurs critiques
- correlation_id présent
- logs cohérents avec le flow métier

---

## 🧠 Résumé

```txt
Logs = trace explicable du système
```

Sans logs :

→ système aveugle

Avec mauvais logs :

→ système bruyant

Avec bons logs :

→ système compréhensible

```


```
````
