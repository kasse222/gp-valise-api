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

Pourquoi : plus simple, plus testable, aligné avec "Transaction = source de vérité", évite une agrégation prématurée.

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
| `TransactionEligibilityService` | décide si payout / refund est autorisé   |
| `CreatePayoutTransaction`       | exécute création FEE + PAYOUT            |
| `RefundTransaction`             | exécute création REFUND                  |
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

La logique de calcul est isolée dans ce service dédié — jamais dans une Action — pour garantir cohérence et évolutivité multi-pays.

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

### Conditions minimales

- `CHARGE` en statut `COMPLETED`
- absence de payout existant
- absence de refund déjà existant
- statut booking compatible

### Règles MVP

- refund **total uniquement**
- **un seul** refund par booking
- refund bloqué si payout existe
- refund après livraison → via litige uniquement

```
refund après livraison → EN_LITIGE → traitement manuel
```

### Calcul

```
refund_possible = CHARGE - FEE
```

> ⚠️ Jamais calculé depuis le payout. `PAYMENT_FEE` non remboursable en MVP.

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

---

## 🧱 Garanties système

- `CHARGE` obligatoire avant confirmation booking
- pas de double charge / payout / fee / refund
- `PAYOUT` et `REFUND` mutuellement exclusifs
- idempotence webhook via `event_id`
- audit complet via `webhook_logs`
- transaction DB sur toutes les opérations critiques

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
CHARGE      = montant brut payé (source de vérité)
FEE         = revenu plateforme (variable, FeeCalculator)
PAYMENT_FEE = coût PSP (persisté, config MVP)
PAYOUT      = CHARGE - FEE (versé au voyageur)
REFUND      = CHARGE - FEE (remboursé à l'expéditeur)

PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
PAYOUT ⊕ REFUND  (mutuellement exclusifs)
```

> GP-Valise reste simple en MVP mais pose dès maintenant
> une base financière propre, traçable, multi-pays et extensible.
