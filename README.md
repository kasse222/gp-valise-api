# GP-Valise API — Backend SaaS Logistique & Transactionnel

Backend API Laravel pour une marketplace logistique entre voyageurs et expéditeurs.

GP-Valise modélise un système SaaS réel avec réservation de capacité, paiements asynchrones, escrow, ledger double-entry, dispute system, audit trail, observabilité et supervision des queues.

---

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Tests](https://img.shields.io/badge/tests-415%20passing-brightgreen)](#tests)
[![Laravel](https://img.shields.io/badge/Laravel-8.2-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)

---

## Objectif produit

GP-Valise permet :

- à un voyageur de publier un trajet avec une capacité disponible ;
- à un expéditeur de réserver de l'espace pour transporter un colis ou une valise ;
- à la plateforme de sécuriser paiement, escrow, livraison, refund, payout et litige.

Le projet vise un backend SaaS crédible, traçable et testable, avec une architecture proche d'un système transactionnel fintech.

---

## Stack technique

- Laravel 8.2 / PHP 8.2+
- PostgreSQL 16 Alpine
- Redis / Horizon
- Docker / Docker Compose
- PestPHP (415 tests / 985 assertions)
- GitHub Actions CI
- Filament 3.3 (admin panel)
- Sanctum
- Queues async
- Webhooks HMAC

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

Responsabilités :

| Composant   | Rôle                                          |
| ----------- | --------------------------------------------- |
| Controller  | Orchestration HTTP uniquement                 |
| Action      | Use case métier                               |
| Policy      | Autorisation                                  |
| FormRequest | Validation d'entrée                           |
| Enum        | Source de vérité des statuts/transitions      |
| Service     | Logique transverse (Ledger, PSP, Eligibility) |
| Job         | Traitement async                              |
| Resource    | Réponse API                                   |

---

## Modules principaux

- Auth / Users / Rôles
- Trips / Luggages
- Bookings / BookingItems
- Transactions (CHARGE, PAYOUT, REFUND, FEE, PAYMENT_FEE)
- Payments / PSP routing (Kkiapay, Stripe, FakeProvider)
- Webhooks (normalisation + idempotence)
- Escrow 48h (ReleaseEscrowBatch)
- Ledger interne double-entry
- Dispute system v2
- Audit Logs (chain hash SHA-256)
- Queue monitoring / retry storm detection
- Observability / Correlation ID
- Filament Admin Dashboard

---

## Booking lifecycle

```
EN_PAIEMENT → CONFIRMEE → LIVREE → TERMINE
```

Cas alternatifs :

```
EN_PAIEMENT     → EXPIREE | ANNULE | PAIEMENT_ECHOUE
CONFIRMEE       → REMBOURSEE (via webhook refund.completed)
CONFIRMEE/LIVREE → EN_LITIGE → REMBOURSEE | TERMINE
```

Règles :

- aucune confirmation sans `CHARGE COMPLETED`
- escrow 48h avant payout (configurable via `GPVALISE_ESCROW_DELAY_HOURS`)
- `disputed_at !== null` → escrow bloqué indéfiniment
- transitions centralisées dans les Enums avec `allowedTransitions()`
- historique complet des statuts (`booking_status_histories`)
- toutes les opérations critiques : `DB::transaction()` + `lockForUpdate()`

---

## Système transactionnel

`Transaction` est la source de vérité financière.
`LedgerEntry` est la source de vérité comptable.

Types supportés :

| Type          | Sens                    |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → Plateforme |
| `PAYOUT`      | Plateforme → Voyageur   |
| `REFUND`      | Plateforme → Expéditeur |
| `FEE`         | Commission GP-Valise    |
| `PAYMENT_FEE` | Frais PSP / banque      |

Invariants financiers :

```
PAYOUT ⊕ REFUND                    (mutuellement exclusifs)
PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
SUM(debits) = SUM(credits)         (ledger double-entry)
```

---

## Ledger double-entry

Comptes actifs (EUR + XOF) :

```
ASSET     : external_psp_clearing, escrow
LIABILITY : payable_voyageur
REVENUE   : revenue_fees
EXPENSE   : expense_psp
```

Flows :

```
CHARGE         → DEBIT external_psp_clearing / CREDIT escrow
PAYOUT RELEASE → DEBIT escrow / CREDIT payable_voyageur + revenue_fees
PAYOUT PAID    → DEBIT payable_voyageur / CREDIT external_psp_clearing
PAYMENT_FEE    → DEBIT expense_psp / CREDIT external_psp_clearing
REFUND         → DEBIT escrow / CREDIT external_psp_clearing
```

Tous les flows sont idempotents via `hasExistingEntries()`.

---

## Dispute system v2

```
booking.status = EN_LITIGE    ← signal financier (escrow bloqué)
dispute.status                ← workflow arbitrage interne
```

Workflow :

```
OPEN → UNDER_REVIEW → WAITING_CUSTOMER → RESOLVED
                    → WAITING_TRAVELER → RESOLVED
                    → ESCALATED       → RESOLVED
     → ESCALATED   → UNDER_REVIEW    → RESOLVED
```

Résolution :

```
DECISION_REFUND → AdminRefundTransaction → REMBOURSEE
DECISION_PAYOUT → writePayoutPaid → TERMINE
```

---

## Webhooks & async

Flow :

```
Provider
→ WebhookController (HMAC verification)
→ WebhookProcessor (normalisation payload)
→ ProcessPaymentWebhook Job (queue: high)
→ HandlePaymentWebhook Action
→ Transaction / Booking / WebhookLog / LedgerEntry
```

Garanties :

- signature HMAC SHA-256
- normalisation avant dispatch (format domaine agnostique)
- idempotence via `event_id` + `lockForUpdate()`
- retry avec backoff exponentiel
- alerting Slack sur échec définitif
- `correlation_id` propagé API → Job → DB

---

## Audit trail

- `AuditLog` append-only (update/delete interdits)
- `integrity_hash` + `previous_hash` — chaîne SHA-256
- `seal()` dans la même `DB::transaction()` que l'action
- `verifyChainFrom()` — vérification cryptographique complète
- `correlation_id` persisté sur chaque log

```bash
php artisan tinker
>>> app(\App\Services\AuditLogIntegrityService::class)->verifyChainFrom(0)
= true
```

---

## Observabilité

Le système propage un `correlation_id` sur tout le flow :

```
HTTP request
→ X-Correlation-ID (header réponse)
→ logs Laravel
→ ProcessPaymentWebhook Job
→ webhook_logs.correlation_id
→ transactions.correlation_id
→ audit_logs.correlation_id
```

---

## Protection des queues

```bash
php artisan monitoring:queues    # santé des queues Redis
php artisan monitoring:webhooks  # santé des webhooks
php artisan simulate:load        # simulation de charge
php artisan simulate:retry-storm # simulation retry storm
php artisan ledger:backfill      # rétro-alimentation ledger
```

En cas de retry storm détecté :

```
⛔ Dispatch bloqué sur la queue high.
Reason: Retry storm détecté sur la fenêtre récente.
Dominant job: App\Jobs\SimulateRetryStormJob (10 occurrences)
```

---

## Admin Dashboard (Filament)

```
http://localhost:8000/admin
```

Ressources :

- Bookings — liste, filtres statuts, vue détail escrow/litige/transactions
- Transactions — badges type/status, montants
- Ledger Accounts — balances EUR/XOF calculées
- Ledger Entries — écritures double-entry

Widgets dashboard :

- Escrow EUR/XOF + `isBalanced()` ✓
- Bookings par statut (EN_PAIEMENT / CONFIRMEE / LIVREE / EN_LITIGE)
- Revenue EUR/XOF + profit net

Action admin :

- Résoudre le litige (modal décision + raison, audit log automatique)

---

## Tests

```bash
make test
```

```
Tests:    415 passed (985 assertions)
Duration: ~5.5s
```

Couverture :

- actions métier (booking, transaction, refund, payout, webhook)
- escrow lifecycle + dispute system v2
- ledger double-entry (LedgerWriter + LedgerReader)
- controllers API + policies
- invariants financiers
- audit integrity chain (seal + verify)
- correlation_id propagation
- queue monitoring + retry storm detection
- webhook idempotence + HMAC

Types de tests :

- Feature tests
- Domain workflow tests
- Ledger invariants tests
- Webhook idempotence tests
- Queue monitoring tests
- Audit integrity tests
- Concurrency tests

## Sécurité

- Auth Sanctum
- Policies par ressource
- Rôles stricts — `ADMIN/SUPER_ADMIN` non disponibles à l'inscription publique
- HMAC webhook signature
- `FakePaymentProvider` interdit en production (double guard)
- Audit log obligatoire sur toute action financière admin
- `lockForUpdate()` sur toutes les opérations concurrentes

---

## CI/CD

GitHub Actions :

- installation Composer
- migrations PostgreSQL
- PestPHP (415 tests)
- Redis service

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

Accès :

```
API        : http://localhost:8000/api/v1
Admin      : http://localhost:8000/admin
Horizon    : http://localhost:8000/horizon
```

Credentials démo :

```
admin@gpvalise.demo      / Demo1234!  (ADMIN)
voyageur@gpvalise.demo   / Demo1234!  (TRAVELER)
expediteur@gpvalise.demo / Demo1234!  (SENDER)
```

---

## .adamas

Le dossier `.adamas/` documente les règles d'ingénierie du projet :

```
.adamas/
├── ai/core          → prompt système, contraintes IA
├── ai/domain        → vérité métier (payment, trip, booking)
├── ai/engineering   → règles de code, review, git
├── ai/governance    → decision-log, méthodologie
├── ai/observability → correlation_id, logging, monitoring
├── ai/security      → webhook, access control, finance sensible
└── domain/
    ├── booking.md
    ├── dispute-strategy.md
    ├── escrow-lifecycle.md
    ├── ledger-strategy.md
    └── psp-routing/
```

---

## Roadmap

```
Phase 1 — MVP                              ✅
Phase 2 — PSP routing Kkiapay/Stripe       ✅
Phase 3 — platform_accounts + PostgreSQL   ✅
Phase 4 — Escrow 48h + OpenDispute         ✅
Phase 5 — Ledger double-entry              ✅
Phase 6 — Dispute system v2               ✅
Phase 7 — API publique dispute             ⏳
           Notifications email/websocket   ⏳
           Upload pièces jointes S3        ⏳
           PSP réel (Kkiapay sandbox)      ⏳
```

---

## Auteur

**Lamine Kasse**
Backend Engineer — Laravel / API / systèmes transactionnels

Je conçois des backends SaaS robustes avec :

- gestion de concurrence (`lockForUpdate`, `DB::transaction()`)
- paiements asynchrones (webhooks, idempotence, retry)
- ledger comptable double-entry
- audit trail (AuditLog chain SHA-256)
- observabilité (correlation_id, logs structurés, queues)
- architecture Action-driven

📍 Casablanca, Maroc
📧 kasse.lamine.dev@icloud.com
🔗 [LinkedIn](https://www.linkedin.com/in/lamine-kasse-05742536a)
🔗 [GitHub](https://github.com/kasse222/gp-valise-api)

```

---
```
