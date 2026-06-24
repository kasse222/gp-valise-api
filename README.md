# SafeMove API — Backend Fintech Escrow & Marketplace Logistique

Backend API Laravel pour une marketplace de transport de colis par des voyageurs (GP — Grand Porteur).

SafeMove sécurise les transactions via escrow : les fonds sont bloqués à la charge, libérés au voyageur uniquement après livraison confirmée.

Architecture fintech-grade : PSP multi-corridor, canonical webhook mapper, ledger double-entry, dispute system, audit trail, KYC, observabilité.

---

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Tests](https://img.shields.io/badge/tests-571%20passing-brightgreen)](#tests)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)

---

## Objectif produit

SafeMove permet :

- à un voyageur (GP) de publier un trajet avec capacité disponible et prix/kg ;
- à un expéditeur de réserver de l'espace pour transporter un colis ;
- à la plateforme de sécuriser paiement, escrow, livraison, refund, payout et litige.

Corridors cibles : Sénégal ↔ France, Maroc, Côte d'Ivoire.

---

## Stack technique

- Laravel 12 / PHP 8.2+
- PostgreSQL 16 Alpine
- Redis / Horizon
- Docker / Docker Compose
- PestPHP — **571 tests / 1312 assertions**
- GitHub Actions CI
- Filament 3.3 (admin panel)
- Sanctum
- Queues async (high / default / low)
- Webhooks HMAC (SHA-256 / SHA-512)

---

## Architecture

Pattern principal :

```
Controller → FormRequest / Policy → Action → Model / Enum / Service → Resource
```

Traitements asynchrones :

```
WebhookController → WebhookProcessor → Job → Action → Transaction / Booking / WebhookLog
```

| Composant   | Rôle                                          |
| ----------- | --------------------------------------------- |
| Controller  | Orchestration HTTP uniquement                 |
| Action      | Use case métier isolé                         |
| Policy      | Autorisation                                  |
| FormRequest | Validation d'entrée                           |
| Enum        | Source de vérité des statuts/transitions      |
| Service     | Logique transverse (Ledger, PSP, Eligibility) |
| Mapper      | Normalisation payload PSP → domaine           |
| Job         | Traitement async                              |
| Resource    | Réponse API                                   |

---

## Modules principaux

- Auth / Users / Rôles (SENDER, TRAVELER, ADMIN, SUPER_ADMIN, MODERATOR)
- Trips / Luggages / BookingItems
- Bookings — instant booking, escrow 48h, dispute
- Transactions (CHARGE, PAYOUT, REFUND, FEE, PAYMENT_FEE)
- **PSP multi-corridor** — PayDunya, Naboopay, Kkiapay, Stripe
- **Canonical webhook mapper** — 4 providers normalisés vers PaymentEventData
- **AfricaAggregatorDriver** — PayDunya + Naboopay avec failover automatique
- Webhooks — signature HMAC, idempotence eventId, retry backoff
- Escrow 48h — scheduler + ReleaseEscrowBatch
- **Ledger double-entry complet** — 7 flows + 2 reversals
- Devise canonique — XOF/EUR/MAD avec règles de sous-unité
- Dispute system v2 — workflow arbitrage + messages + preuves
- KYC — upload CNI, streaming admin sécurisé
- Audit Logs — chaîne d'intégrité SHA-256
- Queue monitoring + retry storm detection
- Correlation ID — observabilité end-to-end
- Filament Admin Dashboard

---

## Booking lifecycle

```
EN_PAIEMENT → CONFIRMEE → EN_TRANSIT → LIVREE → TERMINE
```

Cas alternatifs :

```
EN_PAIEMENT     → EXPIREE | ANNULE | PAIEMENT_ECHOUE
ANNULE          → RefundTransaction si charge COMPLETED + refundRate > 0
CONFIRMEE       → REMBOURSEE (webhook refund.completed)
CONFIRMEE/LIVREE → EN_LITIGE → REMBOURSEE | TERMINE
```

Règles :

- instant booking — paiement PSP vaut confirmation implicite
- aucune confirmation sans `CHARGE COMPLETED`
- escrow 48h avant payout (`GPVALISE_ESCROW_DELAY_HOURS=48`)
- `disputed_at !== null` → escrow bloqué indéfiniment
- transitions centralisées dans `BookingStatusEnum::allowedTransitions()`
- `DB::transaction()` + `lockForUpdate()` sur toutes les opérations critiques

---

## PSP Routing

```
SN (XOF) → africa_aggregator → PayDunya (primaire) + Naboopay (fallback)
MA (MAD) → Stripe
FR/BE/DE → Stripe
BJ/CI    → Kkiapay
fallback → FakeProvider (interdit en production — double guard)
```

Feature flags : `PAYDUNYA_ENABLED`, `NABOOPAY_ENABLED`
Health check avant charge — jamais post-charge (risque double intent).

---

## Canonical Webhook Mapper

```
Provider → payload brut
→ Mapper (PayDunyaStatusMapper / NaboopayStatusMapper / KkiapayStatusMapper / StripeStatusMapper)
→ PaymentEventData canonique
→ HandlePaymentWebhook (agnostique PSP)
```

- Statuts inconnus → `PaymentStatusEnum::INCONNU = 99` + `Log::warning`
- Jamais d'exception traversant la state machine
- `eventId = provider_txId_rawStatus` — unique par événement (F-019)
- Webhooks Africa : `resolveByKey('paydunya')` direct, pas l'agrégateur

---

## Ledger double-entry

Comptes actifs (EUR + XOF) :

```
ASSET     : external_psp_clearing, escrow
LIABILITY : payable_voyageur
REVENUE   : revenue_fees
EXPENSE   : expense_psp
```

7 flows couverts :

```
writeCharge()                    DEBIT  external_psp_clearing / CREDIT escrow
writePayoutRelease()             DEBIT  escrow / CREDIT payable_voyageur + revenue_fees
writePayoutPaid()                DEBIT  payable_voyageur / CREDIT external_psp_clearing
writePaymentFee()                DEBIT  expense_psp / CREDIT external_psp_clearing
writeRefund()                    DEBIT  escrow / CREDIT external_psp_clearing
writeRefundAfterPayoutRelease()  DEBIT  payable_voyageur + revenue_fees / CREDIT external_psp_clearing
writePayoutReversal()            DEBIT  payable_voyageur + revenue_fees / CREDIT escrow
```

Tous les flows : idempotents + `isBalanced()` vérifié.

---

## Devise canonique

```
XOF : unité entière — hasSubunit = false — jamais de ×100
EUR, MAD, GBP, USD : centimes — hasSubunit = true — ×100
CurrencyEnum::forCountry('SN') = XOF
CurrencyEnum::forCountry('MA') = MAD
CurrencyEnum::forCountry('FR') = EUR
17 pays mappés — fallback EUR
```

---

## Tests

```bash
make test
```

```
Tests:    571 passed (1312 assertions) — 0 failures — 13 skipped
Duration: ~8.5s
```

Couverture :

- actions métier (booking, transaction, refund, payout, annulation)
- PSP routing + canonical webhook mapper (4 providers)
- AfricaAggregatorDriver failover
- escrow lifecycle + dispute system v2
- ledger double-entry — 7 flows + 2 reversals
- devise canonique XOF/EUR/MAD + currency resolution
- KYC streaming admin
- audit integrity chain (seal + verify)
- queue monitoring + retry storm detection
- webhook idempotence + HMAC

---

## Sécurité

- Auth Sanctum
- Policies par ressource
- Rôles stricts — ADMIN/SUPER_ADMIN non disponibles à l'inscription publique
- `plan_id` et `role` bloqués dans `UpdateUserRequest`
- HMAC webhook SHA-256/SHA-512
- `FakePaymentProvider` interdit en production (double guard)
- KYC streaming — whitelist champs, auth admin
- Audit log obligatoire sur toutes les actions financières admin
- `lockForUpdate()` sur toutes les opérations concurrentes

---

## Admin Dashboard (Filament)

```
https://admin.safemove.tech/admin
```

- Bookings — statuts, escrow, litige, transactions
- Transactions — badges type/status, montants
- Ledger Accounts — balances EUR/XOF + `isBalanced()`
- Ledger Entries — écritures double-entry
- KYC Requests — review, approve/reject, streaming fichiers
- Dispute system — messages, résolution admin

---

## Installation locale

```bash
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

cp .env.docker.example .env.docker
make up
make key
make migrate
make seed
```

```
API   : http://localhost:8080/api/v1
Admin : http://localhost:8080/admin
```

```
sender@gpvalise.com   / password  (SENDER)
traveler@gpvalise.com / password  (TRAVELER)
admin@gpvalise.com    / password  (ADMIN)
```

---

## Roadmap

```
Phase 1 — MVP démontrable                        ✅
Phase 2 — PSP routing Kkiapay/Stripe             ✅
Phase 3 — platform_accounts + PostgreSQL         ✅
Phase 4 — Escrow 48h + OpenDispute               ✅
Phase 5 — Ledger double-entry                    ✅
Phase 6 — Dispute system v2                      ✅
Phase 7 — PayDunya sandbox production            ✅
Phase 8 — Instant booking + KYC + Waitlist       ✅
           Canonical webhook mapper              ✅
           AfricaAggregatorDriver                ✅
           Ledger reversals                      ✅
           Devise canonique XOF/EUR/MAD          ✅
           CancelBooking → RefundTransaction     ✅
Phase 9 — Après registre de commerce
           PSP payout réel                       ⏳ bloqué RC
           PSP refund réel                       ⏳ bloqué RC
           KYC selfie niveau 2                   ⏳
           Email bienvenue inscription            ⏳
           Dashboard earnings GP                 ⏳
           Notation voyageur                     ⏳
```

---

## 🚀 Démo live

| Service        | URL                                          |
| -------------- | -------------------------------------------- |
| Frontend       | https://safemove.tech                        |
| Admin Filament | https://admin.safemove.tech/admin            |
| API            | `https://safemove.tech/api/v1`               |

---

## Auteur

**Lamine Kasse**
Backend / Fullstack Engineer — Laravel / API / systèmes transactionnels fintech

Compétences démontrées :

- paiements asynchrones multi-PSP (webhooks, idempotence, retry, failover)
- canonical webhook mapper — normalisation PSP → domaine agnostique
- ledger comptable double-entry (7 flows + 2 reversals, `isBalanced()`)
- escrow multi-devise XOF/EUR/MAD avec règles de sous-unité
- gestion de concurrence (`lockForUpdate`, `DB::transaction`)
- audit trail append-only (chaîne SHA-256)
- observabilité (correlation_id, logs structurés, Horizon)
- architecture Action-driven testée à 571 tests / 1312 assertions

📍 Casablanca, Maroc
📧 kasse.lamine.dev@icloud.com
🔗 [LinkedIn](https://www.linkedin.com/in/lamine-kasse-05742536a)
🔗 [GitHub](https://github.com/kasse222/gp-valise-api)