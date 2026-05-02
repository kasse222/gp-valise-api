# 🧠 Decision Log — GP-Valise

## 🎯 Objectif

Ce fichier trace les décisions techniques et métier importantes du projet.

Il permet de :

- comprendre pourquoi un choix a été fait
- éviter de re-débattre les mêmes sujets
- aligner les décisions futures avec les choix passés
- guider l'IA dans ses recommandations

> Une décision documentée vaut mieux qu'un bon code oublié.

---

## 🧾 Format d'une décision

```
## [DATE] — Titre court

### Contexte
Pourquoi la décision est nécessaire

### Décision
Choix effectué

### Alternatives considérées
Options rejetées et pourquoi

### Conséquences
Impacts techniques / métier

### Statut
- ✅ actif
- 🟡 en cours
- 🔴 à revoir
- ⬛ obsolète
```

---

# 📌 Décisions actives

---

## [2026-04] — Transaction = source de vérité financière

### Contexte

Besoin d'une cohérence financière forte avec paiements async, webhooks, retry et idempotence.

### Décision

La **Transaction** est la seule source de vérité financière.

- Payment = vue métier / workflow utilisateur
- Transaction = réalité comptable atomique

### Alternatives considérées

- Stocker l'état financier dans Booking ❌ (couplage fort, pas de traçabilité)
- Dépendre du provider comme source de vérité ❌ (fragile, async non maîtrisé)

### Conséquences

- forte traçabilité financière
- découplage total du provider
- complexité légèrement plus élevée

### Statut

✅ actif

---

## [2026-04] — Séparation FEE / PAYMENT_FEE

### Contexte

Besoin de distinguer le revenu plateforme du coût PSP pour un calcul de rentabilité réel.

### Décision

- `FEE` = revenu GP-Valise (commission)
- `PAYMENT_FEE` = coût externe (PSP, banque)

### Alternatives considérées

- Fusionner les deux ❌ (perte de visibilité financière)
- Ignorer les frais PSP en MVP ❌ (données de rentabilité faussées)

### Conséquences

- modèle financier propre et lisible
- prêt pour multi-pays / multi-PSP
- calcul réel de rentabilité (`profit_net = FEE - PAYMENT_FEE`)

### Statut

✅ actif

---

## [2026-04] — FEE dynamique via FeeCalculator

### Contexte

La commission dépend du pays, du type utilisateur (B2C / B2B) et de règles métier futures.

### Décision

Introduire un `FeeCalculator` dédié, isolé de toute Action :

```php
interface FeeCalculator
{
    public function calculate(Booking $booking): Money;
}
```

Le `FeeCalculator` résout le taux. Le `TransactionAmountCalculator` applique le calcul. Ces deux responsabilités sont strictement séparées.

### Alternatives considérées

- Hardcoder le taux dans l'Action ❌ (duplication, incohérence multi-pays)
- Config simple globale ❌ (non extensible)

### Conséquences

- extensibilité forte (pays / B2B / volume)
- centralisation de la logique de calcul
- testabilité améliorée

### Statut

🟡 en cours — interface définie, implémentation à faire

---

## [2026-04] — Montants financiers persistés à la création

### Contexte

Recalculer les montants à la volée est dangereux : si le taux change en config, les transactions passées donnent des résultats différents selon le moment du calcul.

### Décision

Les montants calculés (`fee_amount`, `payout_amount`, `payment_fee_amount`, `refund_amount`) sont **persistés en base au moment de la création de la transaction**. Ils ne sont jamais recalculés après coup.

### Alternatives considérées

- Recalcul à la volée ❌ (incohérence historique, non auditables)
- Calcul uniquement à l'affichage ❌ (même problème)

### Conséquences

- vérité historique garantie
- audit et contestation possibles à tout moment
- le `TransactionAmountCalculator` sert à créer, pas à interroger

### Statut

✅ actif

---

## [2026-04] — PAYMENT_FEE persistée dès le MVP

### Contexte

Les frais PSP impactent la rentabilité réelle. Les ignorer en MVP crée une dette de données non récupérable.

### Décision

`PAYMENT_FEE` persistée en base dès maintenant, même en estimation.

- Source idéale : webhook PSP (`charge.completed`)
- Fallback MVP : configuration locale (`payment_fee_rate = 2%`)

### Alternatives considérées

- Ignorer en MVP ❌ (données manquantes, pas récupérables)
- Calcul externe uniquement ❌ (pas de traçabilité)

### Conséquences

- meilleure visibilité financière dès le début
- prêt pour PSP réel sans migration de données

### Statut

✅ actif

---

## [2026-04] — FEE créée au moment du PAYOUT

### Contexte

Éviter de prélever une commission sur un booking finalement annulé.

### Décision

La `FEE` est créée lors du `PAYOUT`, pas à la confirmation.

### Alternatives considérées

- Créer à la CHARGE ❌ (commission prélevée avant livraison)
- Créer à la confirmation ❌ (même problème)

### Conséquences

- cohérence escrow
- simplification du refund v1
- alignement avec la réalité métier

### Statut

✅ actif

---

## [2026-04] — Refund post-livraison : Option C (admin override avec audit)

### Contexte

Un refund après livraison n'est pas un cas normal mais peut être nécessaire en cas de litige avéré (bagage perdu, détruit, fraude confirmée). Le système doit gérer ce cas sans compromettre l'invariant financier.

### Décision

**Option C — override admin avec audit obligatoire, interdit si payout existe.**

Deux chemins de refund distincts :

**Refund standard** (avant livraison) :

- statut booking : `CONFIRMEE` ou `EN_LITIGE`
- charge completed, pas de refund, pas de payout

**Refund admin override** (après livraison) :

- admin uniquement (rôle vérifié)
- statut booking : `LIVREE` ou `EN_LITIGE`
- charge completed, pas de refund existant
- **pas de payout existant** (invariant absolu)
- raison explicite obligatoire
- audit log créé atomiquement avec le refund

Action dédiée : `AdminRefundTransaction`

### Alternatives considérées

- **Option A — Blocage total** ❌ : un litige avéré peut nécessiter un remboursement post-livraison. Bloquer serait une faiblesse produit.
- **Option B — Endpoint admin sans contrainte** ❌ : ouvre la porte à des erreurs humaines catastrophiques (rembourser un voyageur déjà payé, casser l'invariant financier).

### Conséquences

- l'invariant `PAYOUT ⊕ REFUND` est maintenu sans exception
- chaque opération admin est tracée avec : admin_id, booking_id, reason, montant, timestamp
- le système peut répondre à toute contestation avec un audit trail complet

### Statut

✅ actif

---

## [2026-04] — Refund limité en MVP

### Contexte

Le refund complet (partiel, multi-refunds, compensation post-payout) est complexe et risqué.

### Décision

MVP simplifié :

- refund total uniquement
- un seul refund par booking
- `PAYMENT_FEE` non remboursable
- `refund_possible = CHARGE - FEE`

### Alternatives considérées

- Refund partiel ❌ (trop complexe pour le MVP)
- Refund automatique post-livraison ❌ (trop risqué)

### Conséquences

- système stable et prévisible
- base propre pour les cas complexes en v5

### Statut

✅ actif

---

## [2026-04] — Webhook idempotent via event_id

### Contexte

Les providers peuvent envoyer plusieurs fois le même event.

### Décision

Idempotence basée sur `event_id` :

- stocké dans `webhook_logs`
- vérifié avant tout traitement
- combiné avec `lockForUpdate()` sur la transaction

### Alternatives considérées

- Pas d'idempotence ❌ (double paiement / double refund possible)
- Idempotence faible ❌ (insuffisant)

### Conséquences

- robustesse élevée sur les flows async
- sécurité financière garantie

### Statut

✅ actif

---

## [2026-04] — Booking indépendant du provider PSP

### Contexte

Les providers peuvent changer. Le Booking ne doit pas en dépendre.

### Décision

Le Booking ne référence jamais directement un provider. Le lien se fait uniquement via Transaction.

### Alternatives considérées

- Lier directement Booking au provider ❌ (couplage fort, migration impossible)

### Conséquences

- découplage total
- multi-PSP ready sans refacto Booking
- meilleure testabilité

### Statut

✅ actif

---

## [2026-04] — Architecture .adamas modulaire

### Contexte

Un fichier unique de contexte IA devient ingérable rapidement.

### Décision

```
.adamas/ai/
├── core/         → règles IA (system prompt, contraintes)
├── capabilities/ → compétences (audit, coding, review)
├── context/      → projet (architecture, business, payment)
└── memory/       → décisions (decision-log)
```

### Alternatives considérées

- Fichier unique ❌ (non maintenable)
- Mélange logique / règles IA ❌ (confusion, hallucinations)

### Conséquences

- IA plus fiable et prévisible
- maintenance facilitée
- évolutivité forte

### Statut

✅ actif

---

## [2026-04] — platform_accounts : migration progressive

### Contexte

Multi-comptes bancaires : Maroc (MAD/EUR), Sénégal (XOF), Stripe ailleurs.

### Décision

Migration en 3 étapes :

1. MVP : user technique "Plateforme" par devise via `getPlatformAccountId(string $currency)`
2. Maintenant : table `platform_accounts` créée avec `user_id` nullable
3. Futur : `transactions.user_id` → `platform_account_id` sans refacto lourde

### Alternatives considérées

- Migration directe immédiate ❌ (trop risqué)
- Ignorer le multi-comptes ❌ (incompatible avec la réalité bancaire)

### Conséquences

- zéro risque de régression immédiate
- base multi-pays posée dès maintenant

### Statut

🟡 en cours — table à créer

---

# 🔮 Décisions à venir

---

## Escrow avancé (v5)

- retenue de fonds conditionnelle
- libération en plusieurs étapes
- compatible dispute system

## Dispute system (v5)

- workflow litige structuré
- arbitrage manuel / automatique
- compensation post-payout

## Multi-platform accounts (v5)

- comptes par pays / devise
- routing financier automatique
- intégration PSP réels (Stripe, CMI, Wave)

## Ledger interne (v5+)

- journal financier complet avec `parent_transaction_id`
- audit avancé et reporting

---

## [2026-05] — AuditLog append-only

### Contexte

Les actions sensibles admin, notamment refund override, doivent être consultables et défendables en cas de litige.

### Décision

AuditLog est une table append-only :

- pas d'updated_at
- update/delete interdits
- suppression d'un actor/user ne supprime pas les logs
- les logs sont consultables uniquement par ADMIN

### Conséquences

Traçabilité forte, meilleure conformité, historique durable.

# 🧠 Principe clé

> Une décision non documentée est une dette future.
> Une décision documentée est un accélérateur.
