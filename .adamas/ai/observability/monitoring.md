# 📊 Monitoring — GP-Valise

> Logging = expliquer après · Monitoring = détecter avant

---

## 🎯 Objectif

- Détecter les incidents **avant** qu'ils impactent les utilisateurs
- Surveiller la santé des composants critiques
- Alerter sur les anomalies financières et opérationnelles

---

## 🔁 Composants surveillés

### Queues (Redis / Horizon)

| Signal               | Seuil                    | Niveau     |
| -------------------- | ------------------------ | ---------- |
| Backlog queue `high` | > seuil config           | `warning`  |
| Job trop ancien      | > seuil âge              | `warning`  |
| Failed jobs récents  | > seuil config           | `critical` |
| Retry storm          | Même job retry en boucle | `critical` |

### Webhooks

| Signal                      | Niveau     |
| --------------------------- | ---------- |
| Taux d'échec élevé          | `warning`  |
| Jobs webhook en `failed`    | `critical` |
| Retry storm webhook         | `critical` |
| Délai de traitement anormal | `warning`  |

### Finance & Transactions

| Signal                                   | Niveau     |
| ---------------------------------------- | ---------- |
| Refund en erreur définitive              | `critical` |
| Payout non traité après escrow           | `critical` |
| Incohérence `SUM(debits) ≠ SUM(credits)` | `critical` |

### Escrow

| Signal                                                          | Niveau     |
| --------------------------------------------------------------- | ---------- |
| Bookings LIVREE avec `escrow_releasable_at` dépassé non traités | `warning`  |
| `ReleaseEscrowBatch` en échec                                   | `critical` |
| Escrow bloqué par dispute non assignée depuis > X heures        | `warning`  |

### Dispute

| Signal                                     | Niveau     |
| ------------------------------------------ | ---------- |
| Dispute OPEN non assignée depuis > SLA     | `warning`  |
| Dispute ESCALATED sans réponse admin       | `critical` |
| Résolution échouée (admin refund / payout) | `critical` |

---

## 🧱 Commandes internes

```bash
# Santé des queues
php artisan monitor:queue-health

# Santé des webhooks
php artisan monitor:webhook-health

# Simulation charge / retry storm (test uniquement)
php artisan simulate:load
php artisan simulate:retry-storm
```

---

## 📈 États système

| État                | Description                | Action                    |
| ------------------- | -------------------------- | ------------------------- |
| `healthy`           | Tout nominal               | Aucune                    |
| `traffic_spike`     | Hausse de trafic           | Surveiller                |
| `slow_processing`   | Traitement lent            | Investiguer               |
| `capacity_pressure` | Backlog élevé              | Scaler                    |
| `retry_storm`       | Boucle de retry dangereuse | **Alerter immédiatement** |

---

## 🚨 Alerting

```php
// Slack alert sur incident critique
Log::critical('Retry storm détecté', ['queue' => 'high']);
SendSlackAlert::dispatch([...]);
```

**Cas déclencheurs obligatoires :**

- Backlog queue `high` > seuil
- Webhook failed en masse
- Retry storm détecté
- Job échoué définitivement
- Escrow non libéré après délai
- Incohérence ledger détectée

---

## 🔥 Retry storm

**Définition :** Même job retenté de nombreuses fois en boucle.

**Danger :**

- Surcharge Redis
- Saturation CPU / workers
- Cascade d'échecs

**Détection :** Même job type avec attempts > seuil + backlog qui explose.

**Règle :** Simuler avant prod avec `php artisan simulate:retry-storm`.

---

## 🔗 Lien avec observabilité

```
Monitoring  →  détecte l'incident
correlation_id  →  explique et trace l'incident
audit_logs  →  prouve ce qui s'est passé
```

---

## ⚠️ Interdits

```
Ignorer les alertes
Monitorer uniquement les logs
Ne pas tester les cas extrêmes (retry storm, backlog)
Dépendre uniquement de l'UI Horizon
```

---

## 🧪 Tests attendus

| Scénario                   | Résultat attendu  |
| -------------------------- | ----------------- |
| Simulation retry storm     | Alerte déclenchée |
| Simulation backlog élevé   | Alerte déclenchée |
| Escrow non libéré          | Warning détecté   |
| Dispute non assignée > SLA | Warning détecté   |

---

## 🧠 Résumé

```
Monitoring = système nerveux
Logging    = mémoire
Audit      = preuve

Sans monitoring  →  incident invisible
Sans logging     →  incident incompréhensible
Sans audit       →  incident indéfendable
```
