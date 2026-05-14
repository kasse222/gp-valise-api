# 🔐 Webhook Security — GP-Valise

> Un webhook compromis = perte financière directe 🔴

---

## 🧠 Principe fondamental

```
NEVER TRUST EXTERNAL INPUT
```

Un webhook est une **entrée externe non fiable** : elle peut être falsifiée, rejouée, dupliquée ou tronquée.

---

## 🔁 Flow sécurisé complet

```
Provider (Kkiapay / Stripe / Fake)
  │
  ▼
WebhookController
  │
  ▼
WebhookProcessor
  ├─► verifyWebhook()
  │     └─► Signature invalide → HTTP 403 (immédiat)
  │
  └─► normalizeWebhook()
        └─► PaymentEventData (canonique)
              │
              ▼
        Dispatch Job async
              │
              ▼
        ProcessPaymentWebhook
              │
              ▼
        HandlePaymentWebhook (Action)
              ├─► Idempotence (event_id UNIQUE + lockForUpdate)
              ├─► Vérification métier (Transaction existe, statut valide)
              ├─► Transaction update
              ├─► Booking transition
              └─► WebhookLog (received / processed / ignored / failed)
```

> Le Controller reste **extrêmement fin** — il délègue tout à `WebhookProcessor`.
> Le domaine ne reçoit **jamais** un payload PSP brut.

---

## 🔒 Étape 1 — Vérification signature

### Objectif

Authentifier que le webhook provient bien du provider.

### Implémentation générique

```php
$expected = hash_hmac('sha256', $payloadJson, $secret);

if (! hash_equals($expected, $signature)) {
    abort(403);
}
```

### Règles

| Règle                       | Raison                          |
| --------------------------- | ------------------------------- |
| `hash_equals()` obligatoire | Protection contre timing attack |
| Secret stocké en `.env`     | Jamais en dur dans le code      |
| Jamais logger le secret     | Sécurité                        |
| Rejet immédiat si invalide  | Pas de fallback silencieux      |

### Multi-PSP

Chaque provider a sa propre logique de vérification.
`verifyWebhook()` **appartient au provider** — la logique est encapsulée dans `KkiapayProvider`, `StripeProvider`, etc.

| Provider | Mécanisme                                  |
| -------- | ------------------------------------------ |
| Stripe   | HMAC-SHA256 sur `X-Stripe-Signature`       |
| Kkiapay  | Hash secret + possibilité API verification |
| Fake     | Toujours valide (test/dev uniquement)      |

---

## 🔁 Étape 2 — Normalisation → PaymentEventData

### Problème

Chaque PSP a son propre vocabulaire :

| Provider | Champ événement | Statut succès |
| -------- | --------------- | ------------- |
| Stripe   | `type`          | `succeeded`   |
| Kkiapay  | `event`         | `success`     |
| Fake     | `event`         | `completed`   |

### Solution

`WebhookProcessor::normalizeWebhook()` produit un `PaymentEventData` canonique :

```
eventId               → clé d'idempotence
eventType             → type d'événement normalisé
providerTransactionId → identifiant PSP
providerStatus        → statut brut conservé (debug)
amount                → montant (integer minor units)
currency              → devise
metadata              → données complémentaires
```

> Le domaine ne dépend **jamais** du format d'un PSP spécifique.

---

## 🔁 Étape 3 — Idempotence

### Problème

Le provider peut envoyer plusieurs fois le même event.

### Solution

```
webhook_logs.event_id UNIQUE (contrainte DB)
```

```php
$existing = WebhookLog::where('event_id', $eventId)
    ->lockForUpdate()
    ->first();

if ($existing) {
    return; // ignoré silencieusement
}
```

**Garantie :** `UN EVENT = UN TRAITEMENT`

---

## 🔒 Étape 4 — Lock + transaction DB

```php
DB::transaction(function () {
    $transaction = Transaction::where(...)->lockForUpdate()->first();
    // vérification métier + mise à jour
});
```

**Protège contre :**

- Race condition (double traitement concurrent)
- Double refund / double payout

---

## 🔁 Étape 5 — Vérification métier

Avant toute action financière :

```
transaction existe            → sinon RetryableWebhookException
type correct (REFUND, CHARGE) → sinon ignored
statut non finalisé           → sinon ignored
```

```php
if ($transaction->isSucceeded() || $transaction->isFailed()) {
    markIgnored();
    return;
}
```

---

## 🔁 Étape 6 — Events supportés

| Event                 | Action                                    |
| --------------------- | ----------------------------------------- |
| `transaction.success` | CHARGE → COMPLETED · Booking → CONFIRMEE  |
| `transaction.failed`  | CHARGE → FAILED                           |
| `refund.completed`    | REFUND → COMPLETED · Booking → REMBOURSEE |
| `refund.failed`       | REFUND → FAILED · Booking inchangé        |
| Autres                | `IGNORED`                                 |

---

## 🔁 Étape 7 — Logging

| Statut      | Condition                         |
| ----------- | --------------------------------- |
| `received`  | Webhook reçu et authentifié       |
| `processed` | Traitement réussi                 |
| `ignored`   | Event déjà traité ou non supporté |
| `failed`    | Erreur de traitement              |

### Structure WebhookLog

| Champ                     | Description                |
| ------------------------- | -------------------------- |
| `event_id`                | Clé d'idempotence (UNIQUE) |
| `event`                   | Type d'événement           |
| `provider_transaction_id` | Lien PSP                   |
| `status`                  | État du traitement         |
| `payload`                 | JSON brut (debug)          |
| `error_message`           | Erreur éventuelle          |
| `processed_at`            | Timestamp                  |
| `correlation_id`          | Traçabilité bout en bout   |

---

## ⚠️ Cas critiques

| Cas                        | Comportement                        |
| -------------------------- | ----------------------------------- |
| Signature invalide         | HTTP 403 immédiat                   |
| Payload incomplet          | Ignoré sans log                     |
| Transaction introuvable    | `RetryableWebhookException` → retry |
| Transaction déjà finalisée | Ignoré avec log                     |
| Event non supporté         | Ignoré avec log                     |
| `event_id` déjà traité     | Ignoré (idempotence)                |

---

## 🔁 Retry & robustesse

```php
if ($this->attempts() < 3) {
    throw $e; // retry
}
// seuil atteint → log critical + Slack alert
```

**Protections contre les attaques :**

| Attaque        | Protection            |
| -------------- | --------------------- |
| Fake webhook   | Signature HMAC        |
| Replay attack  | `event_id` UNIQUE     |
| Race condition | `lockForUpdate()`     |
| Flood webhook  | Monitoring + alerting |

---

## 🚫 Interdits absolus

```
Modifier Booking sans vérifier Transaction
Traiter sans vérification signature
Créer une transaction depuis webhook sans validation métier
Ignorer l'idempotence
Utiliser un payload PSP brut dans le domaine
Logique métier dans WebhookController
```

---

## 🧪 Tests obligatoires

| Scénario                   | Résultat attendu     |
| -------------------------- | -------------------- |
| Signature valide           | Traitement OK        |
| Signature invalide         | HTTP 403             |
| Double event_id            | Ignoré (idempotence) |
| Transaction introuvable    | Retry                |
| Event non supporté         | Ignored              |
| Transaction déjà finalisée | Ignored              |
| Payload incomplet          | Ignoré sans log      |

---

## 🧠 Résumé exécutif

```
1. Signature    → Authentifier (WebhookProcessor)
2. Normalise    → PaymentEventData (WebhookProcessor)
3. Idempotence  → event_id UNIQUE (HandlePaymentWebhook)
4. Lock         → lockForUpdate() (HandlePaymentWebhook)
5. Vérification → invariants métier (HandlePaymentWebhook)
6. Logs         → WebhookLog (toujours)
```

> Le webhook est une frontière de sécurité.
> Tout ce qui entre doit être contrôlé, normalisé et idempotent.
