# 🔍 Correlation ID — GP-Valise

> Le `correlation_id` est le **fil conducteur** d'un flux métier de bout en bout.

---

## 🎯 Objectif

Relier tous les événements d'une même opération :

```
HTTP request
  → Controller
  → Job async
  → Action
  → Transaction
  → LedgerEntry
  → WebhookLog
  → AuditLog
  → Logs Laravel
```

**Objectif principal :**

```
Retrouver l'histoire complète d'une opération sensible
```

---

## 🔁 Cycle de vie

### 1. Requête entrante

```
Header X-Correlation-ID présent  →  conservé
Header absent                    →  UUID v4 généré par le middleware
```

### 2. Réponse HTTP

`X-Correlation-ID` présent **dans toutes les réponses**, y compris les erreurs :

```
200 · 201 · 401 · 403 · 422 · 500
```

### 3. Logs Laravel

```php
Log::withContext([
    'correlation_id' => $correlationId,
]);
```

Tous les logs produits durant la requête sont automatiquement corrélés.

### 4. Jobs async

Transmis **explicitement** au constructeur du Job :

```php
ProcessPaymentWebhook::dispatch(
    payload: $payload,
    correlationId: $request->header('X-Correlation-ID'),
);
```

Le Job réinjecte le contexte au démarrage :

```php
Log::withContext([
    'correlation_id' => $this->correlationId,
]);
```

**Conservé pendant les retries.**

### 5. Base de données

Propagé dans les tables critiques :

| Table          | Colonne          |
| -------------- | ---------------- |
| `webhook_logs` | `correlation_id` |
| `transactions` | `correlation_id` |
| `audit_logs`   | `correlation_id` |

---

## 🧱 Responsabilités par composant

| Composant        | Responsabilité                                                          |
| ---------------- | ----------------------------------------------------------------------- |
| Middleware       | Lire/générer · ajouter dans request · response · contexte logs          |
| Job              | Recevoir au constructeur · conserver pendant retry · injecter dans logs |
| Action           | Recevoir explicitement si écriture DB · ne jamais générer un nouveau    |
| WebhookProcessor | Transmettre le correlation_id au Job dispatché                          |

---

## ✅ Cas d'usage — debugging production

**Scénario :** Un utilisateur signale que son remboursement n'a jamais abouti.

Avec le `correlation_id`, on reconstitue :

```
1. Requête API initiale (logs + response header)
2. Webhook refund.completed reçu (webhook_logs)
3. Job ProcessPaymentWebhook dispatché (Horizon)
4. Action HandlePaymentWebhook exécutée (logs)
5. Transaction REFUND mise à jour (transactions)
6. Booking → REMBOURSEE (logs + booking_status_histories)
7. AuditLog si admin impliqué (audit_logs)
```

**Scénario Phase 6 — dispute :**

```
1. OpenDispute (logs + dispute_status_histories)
2. ResolveDispute → décision financière (audit_logs + transactions)
3. webhook refund.completed → Booking REMBOURSEE (webhook_logs)
```

---

## 🚫 Interdits

```
Générer un nouveau correlation_id dans une Action
Lire request() dans une Action
Perdre le correlation_id entre Job et retries
Logger sans contexte sur un flux critique
Stocker des correlation_ids différents entre webhook_log, transaction et audit_log
Utiliser le correlation_id comme clé d'idempotence ou clé métier
```

---

## ⚠️ Ce que le correlation_id N'EST PAS

| Confusion          | Réalité                                    |
| ------------------ | ------------------------------------------ |
| Clé d'idempotence  | Non — c'est `event_id`                     |
| Preuve d'intégrité | Non — c'est `integrity_hash`               |
| Clé métier         | Non — c'est `booking_id`, `transaction_id` |
| Identifiant public | Non — usage interne uniquement             |

---

## 🔐 Sécurité

| ✅ Accepté | ❌ À éviter            |
| ---------- | ---------------------- |
| UUID v4    | Email utilisateur      |
|            | ID transaction externe |
|            | Token                  |
|            | Numéro de téléphone    |

---

## 🧪 Tests attendus

| Scénario       | Résultat attendu          |
| -------------- | ------------------------- |
| Header absent  | UUID généré               |
| Header présent | Conservé tel quel         |
| Réponse 401    | Header présent quand même |
| Réponse 500    | Header présent quand même |
| Job dispatché  | correlation_id transmis   |
| Retry Job      | correlation_id conservé   |

---

## 🧠 Résumé exécutif

```
correlation_id = GPS d'un flux métier

Il ne protège pas la donnée.
Il permet de la retrouver, l'expliquer et la diagnostiquer.
```

> Sans corrélation, un système async devient opaque en production.
