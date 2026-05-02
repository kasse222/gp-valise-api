Voici une version propre pour :

```txt
.adamas/ai/observability/correlation-id.md
```

````md
# 🔍 Correlation ID — GP-Valise

## 🎯 Objectif

Le `correlation_id` permet de suivre une opération de bout en bout dans GP-Valise.

Il sert à relier :

- requête HTTP ;
- réponse API ;
- logs Laravel ;
- jobs async ;
- webhooks ;
- transactions ;
- audit logs ;
- erreurs et retries.

Objectif principal :

```txt
retrouver l’histoire complète d’une opération sensible
```
````

---

## 🧠 Principe fondamental

> Le `correlation_id` est le fil conducteur d’un flux métier.

Sans `correlation_id`, les logs et événements restent isolés.
Avec lui, on peut reconstruire un scénario complet :

```txt
API → Controller → Job → Action → Transaction → WebhookLog → AuditLog
```

---

## 🔁 Cycle de vie

### 1. Requête entrante

Le middleware lit :

```txt
X-Correlation-ID
```

Si le header existe, il est conservé.
Sinon, un UUID est généré.

---

### 2. Réponse HTTP

Chaque réponse API doit contenir :

```txt
X-Correlation-ID: <uuid>
```

Même en cas d’erreur :

- 401 ;
- 403 ;
- 422 ;
-   500.

---

### 3. Logs Laravel

Le middleware ajoute le contexte global :

```php
Log::withContext([
    'correlation_id' => $correlationId,
]);
```

Tout log produit durant la requête doit être corrélé.

---

### 4. Jobs async

Le `correlation_id` doit être transmis explicitement au Job :

```php
ProcessPaymentWebhook::dispatch(
    payload: $payload,
    correlationId: $request->header('X-Correlation-ID'),
);
```

Le Job doit réinjecter le contexte :

```php
Log::withContext([
    'correlation_id' => $this->correlationId,
]);
```

---

### 5. Base de données

Le `correlation_id` doit être propagé dans les tables critiques :

- `webhook_logs` ;
- `transactions` ;
- `audit_logs`.

Objectif :

```txt
logs applicatifs ↔ données métier ↔ audit
```

---

## 🧱 Règles d’implémentation

### Middleware

Responsabilités :

- lire ou générer le correlation_id ;
- l’ajouter dans la request ;
- l’ajouter dans la response ;
- l’ajouter au contexte de logs.

---

### Job

Responsabilités :

- recevoir le correlation_id au constructeur ;
- le conserver pendant les retries ;
- l’injecter dans les logs ;
- le transmettre aux Actions si nécessaire.

---

### Action

Responsabilités :

- ne jamais lire directement `request()` ;
- recevoir le correlation_id explicitement si elle doit écrire en DB ;
- ne pas générer de nouveau correlation_id.

---

## 🚫 Interdits

- générer un nouveau correlation_id dans une Action ;
- lire `request()` dans une Action ;
- perdre le correlation_id dans un Job ;
- logger sans contexte sur un flux critique ;
- stocker un correlation_id différent entre webhook_log, transaction et audit_log ;
- utiliser le correlation_id comme clé métier ou clé d’idempotence.

---

## ⚠️ Clarification importante

Le `correlation_id` n’est pas :

- une clé d’idempotence ;
- une preuve d’intégrité ;
- une clé métier ;
- un identifiant public de transaction.

Il sert uniquement à la traçabilité.

---

## 🔐 Sécurité

Le `correlation_id` ne doit jamais contenir de données sensibles.

Accepté :

```txt
UUID v4
```

À éviter :

```txt
email utilisateur
id transaction externe
token
numéro de téléphone
```

---

## 🔍 Cas d’usage principal

Un utilisateur dit :

```txt
Mon refund n’a jamais été traité.
```

Avec le `correlation_id`, on doit pouvoir retrouver :

1. la requête API initiale ;
2. le webhook reçu ;
3. le job dispatché ;
4. la transaction impactée ;
5. l’audit log éventuel ;
6. les logs d’erreur ou retry.

---

## 🧪 Tests attendus

Doit être testé :

- header généré si absent ;
- header conservé si fourni ;
- header présent même sur 401 ;
- propagation HTTP → Job ;
- conservation pendant retry ;
- future propagation DB.

---

## 🧠 Résumé exécutif

```txt
correlation_id = GPS d’un flux métier
```

Il ne protège pas la donnée.
Il permet de la retrouver, l’expliquer et la diagnostiquer.

---

## 🧠 Design intention

L’objectif n’est pas d’ajouter un header décoratif.

L’objectif est de construire une base d’observabilité utilisable en production, notamment sur les flux financiers async :

```txt
refund → webhook → job → transaction → audit
```

> Sans corrélation, un système async devient vite opaque.

```

```
