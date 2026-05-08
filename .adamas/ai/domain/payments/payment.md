# 💳 Payment Logic — GP-Valise

## 🎯 Objectif

Définir le comportement financier de GP-Valise :

- **CHARGE** : paiement expéditeur
- **PAYOUT** : versement voyageur
- **REFUND** : remboursement expéditeur
- **FEE** : commission plateforme (variable)
- **PAYMENT_FEE** : frais PSP / bancaires (persistée)

Le système doit être traçable, idempotent, compatible escrow, compatible multi-pays / multi-PSP, extensible vers un ledger complet.

---

## 🧠 Principe fondamental

> Transaction = source de vérité financière.

- **Payment** = vue métier / workflow utilisateur
- **Transaction** = mouvement financier atomique et traçable

---

## 🧩 Types de transactions

| Type          | Sens métier             |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → Plateforme |
| `PAYOUT`      | Plateforme → Voyageur   |
| `REFUND`      | Plateforme → Expéditeur |
| `FEE`         | Commission GP-Valise    |
| `PAYMENT_FEE` | Frais PSP / banque      |

---

## 🔁 Flow financier complet

```
CHARGE       = 100   expéditeur → plateforme
FEE          = 10    revenu GP-Valise (10%)
PAYMENT_FEE  = 2     coût PSP / banque (2%)
PAYOUT       = 90    plateforme → voyageur
profit_net   = 8     FEE - PAYMENT_FEE
```

---

## 🔒 Invariants de cohérence

```
PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
```

Règles absolues :

- `CHARGE` = montant brut payé, jamais modifié
- `FEE` = revenu commercial GP-Valise, calculé sur `CHARGE` brut
- `PAYMENT_FEE` = coût externe, ne réduit **pas** le payout voyageur
- `PAYOUT` et `REFUND` sont **mutuellement exclusifs** sur un même booking

---

## 🔁 Flow métier principal

### 1. Création booking

```
STATUT → EN_PAIEMENT
```

### 2. Paiement CHARGE

**Action** : `CreateTransaction`

| Résultat provider | Statut transaction |
| ----------------- | ------------------ |
| `completed`       | `COMPLETED`        |
| `pending`         | `PENDING`          |
| `failed`          | `FAILED`           |

### 3. Confirmation booking

Un booking devient `CONFIRMEE` uniquement si :

- une transaction `CHARGE` existe
- cette `CHARGE` est `COMPLETED`

### 4. Livraison → Payout + Fee

Quand le booking devient `LIVREE` :

- un `PAYOUT` est créé
- une `FEE` est créée au même moment
- aucun double payout / double fee possible (idempotence obligatoire)

**Décision MVP** : FEE créée au moment du PAYOUT.

Pourquoi : évite de prélever une commission sur un booking annulé, reste cohérent avec l'escrow, simplifie le refund v1.

---

## 🧮 TransactionAmountCalculator

### Objectif

Centraliser tous les calculs financiers. Les Actions exécutent les flux. Le Calculator calcule les montants.

> ⚠️ Aucun calcul financier ne doit être écrit directement dans une Action.

### Source de calcul

Le calculator travaille sur une `Transaction $charge` (pas sur un `Booking`).

Pré-requis : `$charge->type === CHARGE` et `$charge->status === COMPLETED`.

### Formules MVP

```
FEE         = CHARGE * fee_percentage
PAYOUT      = CHARGE - FEE
REFUND      = CHARGE - FEE
PAYMENT_FEE = CHARGE * payment_fee_percentage
profit_net  = FEE - PAYMENT_FEE
```

### Règles importantes

- `PAYMENT_FEE` ne réduit pas le payout voyageur
- `PAYMENT_FEE` ne réduit pas le refund MVP
- `PAYMENT_FEE` réduit uniquement le profit net plateforme
- La FEE est calculée sur le montant brut `CHARGE`, jamais sur le payout ou le net
- Les montants calculés sont **persistés** au moment de la création de la transaction (jamais recalculés après coup)

### Configuration MVP

```env
GPVALISE_FEE_PERCENTAGE=10
GPVALISE_PAYMENT_FEE_PERCENTAGE=2
```

```php
// config/gpvalise.php
return [
    'fee_percentage'         => env('GPVALISE_FEE_PERCENTAGE', 10),
    'payment_fee_percentage' => env('GPVALISE_PAYMENT_FEE_PERCENTAGE', 2),
];
```

### Responsabilités par composant

| Composant                       | Responsabilité                           |
| ------------------------------- | ---------------------------------------- |
| `TransactionAmountCalculator`   | calcule fee, payout, refund, payment_fee |
| `FeeCalculator`                 | résout le taux applicable (pays, B2B…)   |
| `TransactionEligibilityService` | décide si payout / refund est autorisé   |
| `CreatePayoutTransaction`       | exécute création FEE + PAYOUT            |
| `RefundTransaction`             | exécute création REFUND standard         |
| `AdminRefundTransaction`        | exécute refund admin override avec audit |
| `PaymentProvider`               | exécute charge / refund / payout PSP     |

---

## 💸 FEE — Commission plateforme

### Règles

- calculée à partir de la `CHARGE` brute (jamais du payout)
- créée **une seule fois** par booking (idempotence obligatoire)
- ne doit jamais dépasser la `CHARGE`
- indépendante du provider
- créée au moment du PAYOUT dans le MVP

### FeeCalculator — architecture

La FEE est **variable** selon pays, type utilisateur (B2C / B2B), règles futures (volume, plan).

```php
interface FeeCalculator
{
    public function calculate(Booking $booking): Money;
}
```

Le `FeeCalculator` résout le taux. Le `TransactionAmountCalculator` applique le calcul. Ces deux responsabilités sont strictement séparées.

### Structure `FeeRule` (extensible)

| Paramètre      | MVP         | Long terme                           |
| -------------- | ----------- | ------------------------------------ |
| Type de calcul | Pourcentage | Fixe / % / hybride (fixe + %)        |
| Scope          | Global      | Par pays / devise / type utilisateur |
| Source         | Config      | Table `fee_rules` dynamique          |

---

## 🏦 PAYMENT_FEE — Frais PSP / bancaires

### Règles

- créée seulement si une `CHARGE` existe
- **persistée en base** (non optionnelle dès le MVP)
- coût plateforme uniquement, pas un coût utilisateur
- ne réduit pas le payout voyageur
- réduit uniquement le profit net GP-Valise

### Moment de création

```
Idéal    : webhook charge.completed (montant réel PSP)
Fallback : CreateTransaction MVP (montant estimé via config)
```

---

## 🔁 Refund

### Principe

Un `REFUND` est toujours une transaction explicite. Il ne doit jamais être implicite ni seulement déduit d'un statut Booking.

Il existe deux chemins de refund, avec des contraintes distinctes.

---

### Chemin 1 — Refund standard (avant livraison)

**Conditions** :

- statut booking : `CONFIRMEE` ou `EN_LITIGE`
- `CHARGE` en statut `COMPLETED`
- aucun `REFUND` existant
- aucun `PAYOUT` existant

**Calcul** :

```
refund_possible = CHARGE - FEE
```

**Règles** :

- `PAYMENT_FEE` non remboursable en MVP
- jamais calculé depuis le payout

---

### Chemin 2 — Refund admin override (après livraison)

**Décision** : Option C — override admin avec audit obligatoire, interdit si payout existe.

**Pourquoi pas le blocage total (Option A)** : un litige avéré peut nécessiter un remboursement même après livraison (bagage perdu, détruit, fraude confirmée). Bloquer tout refund post-livraison serait une faiblesse produit.

**Pourquoi pas un simple endpoint admin (Option B)** : sans contraintes, un endpoint admin expose à des erreurs catastrophiques — rembourser un voyageur déjà payé, casser l'invariant financier.

**Conditions strictes** :

- **admin uniquement** (rôle vérifié obligatoirement)
- statut booking : `LIVREE` ou `EN_LITIGE`
- `CHARGE` en statut `COMPLETED`
- aucun `REFUND` existant
- **aucun `PAYOUT` existant** (invariant absolu — jamais remboursable si payout effectué)
- raison explicite obligatoire (`reason` non vide)
- audit log obligatoire créé atomiquement avec le refund

**Garanties** :

- l'invariant `PAYOUT ⊕ REFUND` est maintenu sans exception
- toute opération est tracée avec : admin_id, booking_id, reason, montant, timestamp
- le refund admin est créé dans la même transaction DB que l'audit log

**Action dédiée** : `AdminRefundTransaction`

```
AdminRefundTransaction::execute(
    booking: Booking,
    charge: Transaction,
    admin: User,
    reason: string
): Transaction
```

---

### Calcul commun

```
refund_possible = CHARGE - FEE
```

> ⚠️ `PAYMENT_FEE` non remboursable dans les deux chemins.
> Le refund ne doit jamais être calculé depuis le payout.

---

## 🔍 Traçabilité financière

> Est-ce que ton système peut expliquer exactement ce qui s'est passé sur une transaction contestée ?

**Oui**, grâce à :

- chaque `Transaction` liée à un `booking_id` et un `provider_transaction_id`
- chaque refund admin lié à un audit log avec raison et auteur
- chaque webhook loggé dans `webhook_logs` avec statut et `event_id`
- les montants persistés au moment de la création (jamais recalculés)
- le statut final bloquant toute modification ultérieure

En cas de contestation, la reconstruction complète du flux financier est possible depuis la DB sans aucune ambiguïté.

---

## 🔗 Relation entre transactions

Toutes les transactions d'un même flux sont liées par :

- `booking_id` (obligatoire)
- `provider_transaction_id` (si disponible)

Option future (ledger) :

- `parent_transaction_id` : PAYMENT_FEE → CHARGE, FEE → CHARGE, REFUND → CHARGE

---

## ⚠️ Concurrence et cohérence

- `lockForUpdate()` sur toutes les opérations critiques
- transaction DB obligatoire pour toute opération multi-modèles
- statut final (`COMPLETED`, `FAILED`) = blocage de toute modification ultérieure

---

## 🔁 Webhook async

### Flow

```
Provider → WebhookController → vérification HMAC → dispatch Job
→ HandlePaymentWebhook → Transaction + Booking update → WebhookLog
```

### Sécurité

- signature HMAC obligatoire, comparaison via `hash_equals`
- rejet immédiat si invalide → `HTTP 403`
- payload incomplet → ignoré sans créer de log

### Payload minimal

```json
{
    "event_id": "evt_123",
    "event": "refund.completed",
    "provider_transaction_id": "txn_abc"
}
```

### Idempotence

Clé : `event_id` unique dans `webhook_logs` + `lockForUpdate()` + vérification statut final.

### Statuts WebhookLog

| Statut      | Signification        |
| ----------- | -------------------- |
| `received`  | reçu                 |
| `processed` | traité               |
| `ignored`   | ignoré (idempotence) |
| `failed`    | erreur de traitement |

### Events MVP

| Event              | Effets                                                               |
| ------------------ | -------------------------------------------------------------------- |
| `refund.completed` | `REFUND → COMPLETED`, `processed_at = now()`, `Booking → REMBOURSEE` |
| `refund.failed`    | `REFUND → FAILED`, `Booking` inchangé (souvent `EN_LITIGE`)          |
| autres events      | `ignored`                                                            |

             | Event                  | Effets                                                                |

> **Important** : `HandlePaymentWebhook::handleSuccess()` est appelé quelle que soit
> la source du refund (standard via `RefundTransaction` ou admin via `AdminRefundTransaction`).
> La transition `Booking → REMBOURSEE` doit donc être valide depuis `CONFIRMEE` ET depuis `EN_LITIGE`.
> Voir bug C3 dans `booking.md` — l'absence de `CONFIRMEE → REMBOURSEE` dans `allowedTransitions()`
> provoquait un webhook `FAILED` définitif malgré un `REFUND COMPLETED` en base.

---

| Event                 | Effets                                                               |
| --------------------- | -------------------------------------------------------------------- |
| `transaction.success` | `CHARGE → COMPLETED`, `processed_at = now()`, `Booking → CONFIRMEE`  |
| `transaction.failed`  | `CHARGE → FAILED`, `Booking` inchangé (`EN_PAIEMENT`)                |
| `refund.completed`    | `REFUND → COMPLETED`, `processed_at = now()`, `Booking → REMBOURSEE` |
| `refund.failed`       | `REFUND → FAILED`, `Booking` inchangé (souvent `EN_LITIGE`)          |
| autres events         | `ignored`                                                            |

> **Deux chemins de confirmation** :
>
> - `ConfirmBooking` = confirmation manuelle par le voyageur (guards utilisateur + capacité)
> - `HandlePaymentWebhook::handleChargeSuccess()` = confirmation automatique par preuve PSP
>
> Le webhook bypass les guards utilisateur délibérément :
> `transaction.success` est une preuve externe de paiement, pas une action humaine.
> La transition `EN_PAIEMENT → CONFIRMEE` reste valide dans les deux cas.

## 🧱 Garanties système

- `CHARGE` obligatoire avant confirmation booking
- pas de double charge / payout / fee / refund
- `PAYOUT` et `REFUND` mutuellement exclusifs **sans exception**
- idempotence webhook via `event_id`
- audit complet via `webhook_logs` et audit log refund admin
- transaction DB sur toutes les opérations critiques
- montants persistés à la création, jamais recalculés

---

## 🏗️ Roadmap multi-comptes (Maroc / Sénégal / Stripe)

### Table `platform_accounts`

| Colonne        | Description                         |
| -------------- | ----------------------------------- |
| `id`           | identifiant                         |
| `name`         | ex : "Plateforme Maroc EUR"         |
| `currency`     | EUR, XOF, MAD…                      |
| `country_code` | MA, SN…                             |
| `is_active`    | booléen                             |
| `metadata`     | JSON (infos bancaires, PSP associé) |

### Stratégie de migration

1. MVP : user technique "Plateforme" par devise via `getPlatformAccountId(string $currency)`
2. Maintenant : table `platform_accounts` créée avec `user_id` nullable
3. Futur : `transactions.user_id` → `platform_account_id` sans refacto lourde

---

## 🚫 À ne pas faire maintenant

- ledger complet / `parent_transaction_id` actif
- multi-refunds / refund partiel
- compensation après payout
- `platform_account_id` obligatoire immédiatement
- `PAYMENT_FEE` réelle fournie par provider (MVP = estimation config)
- refonte globale de Payment / Transaction

---

## 🧭 Cible long terme

- `FeeRule` dynamique par pays / B2B / volume
- `platform_account_id` obligatoire sur transactions FEE
- ledger interne complet avec `parent_transaction_id`
- refund partiel + compensation post-payout
- arbitrage litige avancé
- intégration PSP réel (Stripe, CMI, Wave)
- `PAYMENT_FEE` réelle via webhook provider

---

## 🧠 Résumé exécutif

```
CHARGE      = montant brut payé (source de vérité, persisté)
FEE         = revenu plateforme (variable, FeeCalculator, persisté)
PAYMENT_FEE = coût PSP (persisté, config MVP)
PAYOUT      = CHARGE - FEE (versé au voyageur, persisté)
REFUND      = CHARGE - FEE (remboursé à l'expéditeur, persisté)

PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
PAYOUT ⊕ REFUND  (mutuellement exclusifs, sans exception)

Refund post-livraison : admin override uniquement
  → raison obligatoire + audit log + pas de payout existant
```

> GP-Valise reste simple en MVP mais pose dès maintenant
> une base financière propre, traçable, auditée et extensible.
