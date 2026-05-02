# 🔐 Webhook Security — GP-Valise

## 🎯 Objectif

Sécuriser le traitement des webhooks provenant des providers de paiement.

Garantir :

- authenticité des requêtes
- intégrité des données
- idempotence
- protection contre les attaques
- cohérence financière

> Un webhook compromis = perte financière directe 🔴

---

## 🧠 Principe fondamental

```txt
NEVER TRUST EXTERNAL INPUT
```

Un webhook est une **entrée externe non fiable**.

Chaque payload doit être :

1. authentifié
2. validé
3. contrôlé
4. traité de manière idempotente

---

## 🔁 Flow sécurisé

```txt
Provider
  ↓
WebhookController
  ↓ (verify signature)
Reject ❌ / Accept ✅
  ↓
Dispatch Job
  ↓
HandlePaymentWebhook
  ↓
Transaction + Booking update
  ↓
WebhookLog
```

---

## 🔒 Étape 1 — Signature HMAC

### Objectif

Vérifier que le webhook vient bien du provider.

---

### Implémentation

```php
$expected = hash_hmac('sha256', $payloadJson, $secret);

if (! hash_equals($expected, $signature)) {
    abort(403);
}
```

---

### Règles

- utiliser `hash_equals()` (protection timing attack)
- secret stocké en `.env`
- jamais logger le secret

---

### Header attendu

```txt
X-Signature
```

---

## 🚫 Interdits

- comparer avec `===`
- accepter sans signature
- fallback silencieux

---

## 🔁 Étape 2 — Validation payload

### Champs obligatoires

```txt
event_id
event
provider_transaction_id
```

---

### Règles

- payload incomplet → ignoré
- payload invalide → rejeté
- aucun traitement si champs manquants

---

## 🔁 Étape 3 — Idempotence

### Problème

Le provider peut envoyer plusieurs fois le même event.

---

### Solution

Table :

```txt
webhook_logs.event_id UNIQUE
```

---

### Implémentation

```php
$existing = WebhookLog::where('event_id', $eventId)
    ->lockForUpdate()
    ->first();

if ($existing) {
    return;
}
```

---

### Garantie

```txt
UN EVENT = UN TRAITEMENT
```

---

## 🔒 Étape 4 — Lock transaction

### Objectif

Éviter :

- double refund
- incohérence financière

---

### Implémentation

```php
DB::transaction(function () {
    Transaction::lockForUpdate()->first();
});
```

---

## 🔁 Étape 5 — Vérification métier

Avant toute action :

- transaction existe
- type correct (`REFUND`)
- statut non finalisé

---

### Exemple

```php
if ($transaction->isSucceeded() || $transaction->isFailed()) {
    markIgnored();
    return;
}
```

---

## 🔁 Étape 6 — Traitement contrôlé

### Events supportés

| Event              | Action                              |
| ------------------ | ----------------------------------- |
| `refund.completed` | REFUND → COMPLETED + booking update |
| `refund.failed`    | REFUND → FAILED                     |

---

### Events inconnus

```txt
IGNORED
```

---

## 🔁 Étape 7 — Logging

Chaque webhook doit être tracé :

```txt
received
processed
ignored
failed
```

---

## 🔗 Structure WebhookLog

| Champ                   | Description          |
| ----------------------- | -------------------- |
| event_id                | id unique            |
| event                   | type                 |
| provider_transaction_id | lien PSP             |
| status                  | état traitement      |
| payload                 | JSON brut            |
| error_message           | erreur éventuelle    |
| processed_at            | timestamp            |
| correlation_id (future) | traçabilité complète |

---

## ⚠️ Cas critiques

### Transaction introuvable

```php
throw new RetryableWebhookException();
```

👉 permet retry

---

### Payload incomplet

```txt
IGNORED (pas de log)
```

---

### Signature invalide

```txt
403 immédiat
```

---

## 🔁 Retry & robustesse

### Retryable exception

- relance le job
- limite les pertes de données async

---

### Retry limit

- éviter boucle infinie
- log après seuil

---

### Exemple

```php
if ($this->attempts() < 3) {
    throw $e;
}
```

---

## 🚨 Attaques possibles

### Fake webhook

→ bloqué par signature

---

### Replay attack

→ bloqué par `event_id`

---

### Race condition

→ bloqué par `lockForUpdate()`

---

### Flood webhook

→ détecté via monitoring

---

## 🔐 Bonnes pratiques

- toujours traiter via Job async
- jamais traiter directement en Controller
- toujours logguer
- toujours être idempotent

---

## 🚫 Interdits absolus

- modifier Booking sans vérifier Transaction
- traiter sans signature
- créer une transaction depuis webhook sans validation
- ignorer idempotence
- mélanger logique métier et controller

---

## 🧪 Tests obligatoires

- signature valide → OK
- signature invalide → 403
- double event → ignoré
- transaction inexistante → retry
- event non supporté → ignored
- transaction déjà finalisée → ignored

---

## 🧠 Résumé exécutif

```txt
Signature → Authentifier
Validation → Vérifier
Idempotence → Protéger
Lock → Synchroniser
Logs → Tracer
```

---

## 🧠 Design intention

Le webhook est un point d’entrée critique.

Le système doit être :

- robuste
- idempotent
- sécurisé
- explicable

---

## 🧠 Niveau attendu

Tu dois pouvoir répondre :

👉 “Que se passe-t-il si Stripe envoie 5 fois le même event ?”

👉 “Que se passe-t-il si quelqu’un falsifie un webhook ?”

---

## 🧠 Principe clé

> Le webhook est une frontière de sécurité.
> Tout ce qui entre doit être contrôlé strictement.

```

```
