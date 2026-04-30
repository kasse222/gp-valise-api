# 💳 Payment Logic — GP-Valise

## 🎯 Objectif

Définir le comportement financier de GP-Valise :

- **CHARGE** : paiement expéditeur
- **PAYOUT** : versement voyageur
- **REFUND** : remboursement expéditeur
- **FEE** : commission plateforme (variable)
- **PAYMENT_FEE** : frais PSP / bancaires (persistée)

Le système doit être :

- traçable
- idempotent
- compatible escrow
- compatible multi-pays / multi-PSP
- extensible vers un ledger complet

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

Exemple : l'expéditeur paie 100, commission plateforme 10 %, frais PSP 2.

```
CHARGE       = 100   expéditeur → plateforme
FEE          = 10    revenu GP-Valise
PAYMENT_FEE  = 2     coût PSP / banque
PAYOUT       = 90    plateforme → voyageur
```

Profit réel plateforme :

```
profit_net = FEE - PAYMENT_FEE
           = 10 - 2 = 8
```

---

## 🔒 Invariants de cohérence

```
PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
```

Règles :

- `CHARGE` représente toujours le montant brut payé
- `FEE` représente le revenu commercial GP-Valise
- `PAYMENT_FEE` représente un coût externe
- `PAYMENT_FEE` ne réduit **pas** le payout voyageur
- `PAYMENT_FEE` réduit uniquement la marge nette plateforme

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

- un `PAYOUT` peut être créé
- une `FEE` peut être créée
- aucun double payout ne doit être possible
- aucune double fee ne doit être possible

**Décision MVP** :

```
FEE créée au moment du PAYOUT
```

Pourquoi :

- évite de prélever une commission sur un booking annulé
- reste cohérent avec l'escrow
- simplifie le refund v1

---

## 💸 FEE — Commission plateforme

### Principe

La `FEE` représente le revenu GP-Valise. Elle est modélisée comme une transaction indépendante.

### ⚙️ Calcul dynamique — décision architecture

La FEE est **variable** selon :

- **pays** (devise / marché : MAD, EUR, XOF…)
- **type utilisateur** (B2C / B2B)
- **règles métier futures** (volume, plan, partenaire)

> ⚠️ La FEE ne doit jamais être hardcodée dans une Action.
> Elle doit être calculée par un `FeeCalculator` dédié.

```php
interface FeeCalculator
{
    public function calculate(Booking $booking): Money;
}
```

Pourquoi isoler dans un service dédié : la règle de calcul est transverse,
réutilisable et variable — la centraliser évite toute dispersion et incohérence multi-pays.

### Règles

- calculée à partir de la `CHARGE`
- créée **une seule fois** par booking (idempotence obligatoire)
- ne doit jamais dépasser la `CHARGE`
- indépendante du provider
- liée au même booking
- créée au moment du payout dans le MVP

### Base de calcul FEE

La FEE est calculée sur :

- le montant brut de la CHARGE (avant PAYMENT_FEE)

👉 Elle ne doit jamais être calculée sur :

- le payout
- le montant net

### Exemple

```
CHARGE = 100
FEE    = 10   (10% B2C France)
PAYOUT = 90  ❌ incohérent
```

### Règle critique

Un booking ne peut pas avoir simultanément :

- un PAYOUT
- et un REFUND

👉 Ces deux états sont mutuellement exclusifs.

### Structure `FeeRule` (extensible)

| Paramètre      | MVP         | Long terme                           |
| -------------- | ----------- | ------------------------------------ |
| Type de calcul | Pourcentage | Fixe / % / hybride (fixe + %)        |
| Scope          | Global      | Par pays / devise / type utilisateur |
| Source         | Config      | Table `fee_rules` dynamique          |

---

## 🏦 PAYMENT_FEE — Frais PSP / bancaires

### Principe

La `PAYMENT_FEE` représente le coût réel facturé par banque, Stripe, PSP local ou mobile money.
Elle est modélisée comme une transaction indépendante et **persistée obligatoirement** en base.

### Source

- **Idéalement** : fournie par le PSP via webhook (`charge.completed`)
- **Fallback MVP** : calculée via configuration locale (ex : `payment_fee_rate = 2%`)

### Règles

- créée seulement si une `CHARGE` existe
- **persistée en base** (non optionnelle)
- correspond à un **coût plateforme**, pas un coût utilisateur
- ne réduit **pas** le payout voyageur
- réduit uniquement le profit net GP-Valise

### Moment de création

```
Idéal    : webhook charge.completed (montant réel PSP)
Fallback : CreateTransaction MVP (montant estimé via config)
```

---

## 🔁 Refund

### Principe

Un `REFUND` est toujours une transaction explicite.
Il ne doit jamais être implicite ni seulement déduit d'un statut Booking.

### Conditions minimales

- `CHARGE` en statut `COMPLETED`
- absence de payout existant
- absence de refund déjà existant
- statut booking compatible

### Règles MVP

- refund **total uniquement**
- **un seul** refund par booking
- pas de refund partiel
- refund bloqué si payout existe
- refund après livraison → via litige uniquement

### Refund après livraison

```
refund après livraison → EN_LITIGE → traitement manuel
```

### Calcul du montant remboursable

```
refund_possible = CHARGE - FEE
```

> ⚠️ Le refund ne doit jamais être calculé depuis le payout.
> La `PAYMENT_FEE` n'est **pas** remboursable dans le MVP.

---

### Évolution future (ledger)

Chaque transaction pourra être liée à une autre via :

- parent_transaction_id

Exemples :

- PAYMENT_FEE → liée à CHARGE
- FEE → liée à CHARGE
- REFUND → liée à CHARGE

👉 Permet audit financier complet et reconstruction des flux.

---

## 🔗 Relation entre transactions

Toutes les transactions d'un même flux financier sont liées par :

- `booking_id` (obligatoire)
- `provider_transaction_id` (si disponible)

Option future :

- `parent_transaction_id` (ledger avancé)

Objectif : audit complet, debug, reconstruction du flux financier.

---

## ⚠️ Concurrence et cohérence

- `lockForUpdate()` sur toutes les opérations critiques
- transaction DB obligatoire pour toute opération multi-modèles
- statut final (`COMPLETED`, `FAILED`) = blocage de toute modification ultérieure

---

## 🔁 Webhook async

### Flow

```
Provider
→ WebhookController
→ vérification signature HMAC
→ dispatch Job
→ HandlePaymentWebhook
→ Transaction + Booking update
→ WebhookLog
```

### Sécurité

- signature HMAC obligatoire
- comparaison via `hash_equals`
- rejet immédiat si signature invalide → `HTTP 403`

### Payload minimal attendu

```json
{
    "event_id": "evt_123",
    "event": "refund.completed",
    "provider_transaction_id": "txn_abc"
}
```

Payload incomplet → ignoré sans créer de log.

### Idempotence webhook

Clé d'idempotence : `event_id`

- `event_id` unique dans `webhook_logs`
- vérification avant traitement
- `lockForUpdate()` sur la transaction
- transaction déjà finalisée → `ignored`

### Statuts WebhookLog

| Statut      | Signification        |
| ----------- | -------------------- |
| `received`  | reçu                 |
| `processed` | traité               |
| `ignored`   | ignoré (idempotence) |
| `failed`    | erreur de traitement |

### Events webhook MVP

| Event              | Effets                                                               |
| ------------------ | -------------------------------------------------------------------- |
| `refund.completed` | `REFUND → COMPLETED`, `processed_at = now()`, `Booking → REMBOURSEE` |
| `refund.failed`    | `REFUND → FAILED`, `Booking` inchangé (souvent `EN_LITIGE`)          |
| autres events      | `ignored`                                                            |

---

## 🧱 Garanties système

- `CHARGE` obligatoire avant confirmation booking
- pas de double charge
- pas de double payout
- pas de double fee
- pas de refund après payout
- idempotence webhook via `event_id`
- audit complet via `webhook_logs`
- transaction DB sur toutes les opérations critiques

---

## 🏗️ Roadmap multi-comptes (Maroc / Sénégal / Stripe)

### Modèle cible : `platform_accounts`

| Colonne        | Description                         |
| -------------- | ----------------------------------- |
| `id`           | identifiant                         |
| `name`         | ex : "Plateforme Maroc EUR"         |
| `currency`     | EUR, XOF, MAD…                      |
| `country_code` | MA, SN…                             |
| `is_active`    | booléen                             |
| `metadata`     | JSON (infos bancaires, PSP associé) |

### Stratégie de migration

1. **MVP** : user technique "Plateforme" par devise via `getPlatformAccountId(string $currency)`
2. **Maintenant** : table `platform_accounts` créée avec `user_id` nullable (lien temporaire)
3. **Futur** : `transactions.user_id` → `platform_account_id` sans refacto lourde

### Routing automatique via FeeCalculator

Le `FeeCalculator` détermine :

- le montant de la commission (règle variable)
- le `platform_account_id` de destination selon la devise du booking

---

## 🚫 À ne pas faire maintenant

- ledger complet
- multi-refunds
- refund partiel
- compensation après payout
- logique multi-comptes complète
- `platform_account_id` obligatoire immédiatement
- refonte globale de Payment / Transaction
- `PAYMENT_FEE` réelle fournie par provider (MVP = estimation)

---

## 🧭 Cible long terme

- `platform_account_id` obligatoire sur transactions FEE
- ledger interne complet
- comptes plateforme par pays / devise
- `FeeRule` dynamique par pays / B2B / volume
- refund partiel
- compensation après payout
- arbitrage litige avancé
- intégration PSP réel (Stripe, CMI, Wave)
- `PAYMENT_FEE` réelle fournie par webhook provider

---

## 🧠 Résumé exécutif

```
CHARGE      = montant brut payé par l'expéditeur
FEE         = revenu plateforme (variable, calculé par FeeCalculator)
PAYMENT_FEE = coût PSP (persisté, supporté par la plateforme)
PAYOUT      = montant net versé au voyageur
REFUND      = remboursement expéditeur (si annulation)
```

## 🧠 Contrainte produit

Le système privilégie :

- la cohérence financière
- la traçabilité
- la simplicité MVP

👉 plutôt que la complexité prématurée (ledger complet, multi-refund, etc.)

...

Invariant économique :

```
PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
```

> GP-Valise doit rester simple dans le MVP, mais poser dès maintenant
> une base financière propre, traçable, multi-pays et extensible.
