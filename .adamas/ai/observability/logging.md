# 🧾 Logging — GP-Valise

> Un bon log explique un bug sans debugger.

---

## 🧠 Principe fondamental

```
Logguer ce qui est utile, pas tout.
```

Un bon log est : **structuré · corrélé · exploitable · sans bruit**

---

## 📊 Niveaux de logs

| Niveau     | Usage                                   |
| ---------- | --------------------------------------- |
| `debug`    | Local uniquement — jamais en production |
| `info`     | Événement normal attendu                |
| `warning`  | Anomalie non bloquante                  |
| `error`    | Erreur métier ou technique              |
| `critical` | Incident grave (financier, système)     |

---

## 🔁 Événements à logger

### Finance & Transactions

| Événement                       | Niveau     | Données                            |
| ------------------------------- | ---------- | ---------------------------------- |
| CHARGE créée                    | `info`     | `booking_id`, `amount`, `provider` |
| CHARGE COMPLETED                | `info`     | `booking_id`, `transaction_id`     |
| PAYOUT créé                     | `info`     | `booking_id`, `amount`             |
| PAYOUT COMPLETED                | `info`     | `booking_id`, `transaction_id`     |
| REFUND créé                     | `info`     | `booking_id`, `amount`, `reason`   |
| REFUND COMPLETED                | `info`     | `booking_id`, `transaction_id`     |
| Incohérence financière détectée | `critical` | Tous les champs                    |

### Escrow

| Événement                      | Niveau    | Données                              |
| ------------------------------ | --------- | ------------------------------------ |
| Booking LIVREE (escrow start)  | `info`    | `booking_id`, `escrow_releasable_at` |
| Escrow release déclenché       | `info`    | `booking_id`, `delay_hours`          |
| Escrow bloqué (dispute active) | `warning` | `booking_id`, `disputed_at`          |
| Escrow release échoué          | `error`   | `booking_id`, erreur                 |

### Dispute

| Événement             | Niveau | Données                                  |
| --------------------- | ------ | ---------------------------------------- |
| Dispute ouverte       | `info` | `dispute_id`, `booking_id`, `reason`     |
| Statut dispute changé | `info` | `dispute_id`, `old_status`, `new_status` |
| Dispute résolue       | `info` | `dispute_id`, `decision`, `resolved_by`  |

### Webhooks

| Événement             | Niveau     | Données                      |
| --------------------- | ---------- | ---------------------------- |
| Reçu                  | `info`     | `event_id`, `event_type`     |
| Traité                | `info`     | `event_id`, `transaction_id` |
| Ignoré                | `info`     | `event_id`, raison           |
| Échoué                | `error`    | `event_id`, erreur           |
| Échoué définitivement | `critical` | `event_id`, tentatives       |

### Admin actions

| Événement             | Niveau    | Données                              |
| --------------------- | --------- | ------------------------------------ |
| Refund admin override | `warning` | `booking_id`, `admin_id`, `reason`   |
| Résolution dispute    | `warning` | `dispute_id`, `decision`, `admin_id` |

---

## 🧱 Structure recommandée

```php
// ✅ Correct — structuré avec contexte
Log::info('Refund processed', [
    'booking_id'     => $booking->id,
    'transaction_id' => $transaction->id,
    'amount'         => $transaction->amount,
    'correlation_id' => $correlationId,
]);

// ❌ Mauvais — inutilisable
Log::info("Refund OK");
```

---

## 🔗 Correlation ID

**Tous les logs doivent contenir le `correlation_id` via :**

```php
Log::withContext([
    'correlation_id' => $correlationId,
]);
```

Permet de reconstruire un flux complet depuis les logs.

---

## 🚫 Interdits

**Ne jamais logger :**

```
email · téléphone · données KYC
token d'authentification · numéro de carte bancaire
secret webhook / clé API
```

**Ne jamais faire :**

```
Log::debug() en production
Logger sans contexte sur flux critique
Logger dans des boucles intensives
Logs de bruit (chaque requête HTTP standard)
```

---

## 🧠 Résumé

```
Sans logs    →  système aveugle
Mauvais logs →  système bruyant
Bons logs    →  incident diagnostiquable en production à 3h du matin
```
