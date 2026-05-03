# GP-Valise API — Backend SaaS Logistique & Transactionnel

Backend API Laravel 12 pour une marketplace logistique entre voyageurs et expéditeurs.

GP-Valise modélise un système SaaS réel avec réservation de capacité, paiements asynchrones, refunds, payouts, audit trail, observabilité et supervision des queues.

---

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Tests](https://img.shields.io/badge/tests-260%20passing-brightgreen)](#tests)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)

---

## Objectif produit

GP-Valise permet :

- à un voyageur de publier un trajet avec une capacité disponible ;
- à un expéditeur de réserver de l'espace pour transporter un colis ou une valise ;
- à la plateforme de sécuriser paiement, livraison, refund, payout et litige.

Le projet vise un backend SaaS crédible, traçable et testable, avec une architecture proche d'un système transactionnel fintech.

---

## Stack technique

- Laravel 12
- PHP 8.2+
- MySQL
- Redis / Horizon
- Docker / Docker Compose
- PestPHP
- GitHub Actions
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
WebhookController → Job → Action → Transaction / Booking / WebhookLog
```

Responsabilités :

| Composant   | Rôle                                     |
| ----------- | ---------------------------------------- |
| Controller  | Orchestration HTTP uniquement            |
| Action      | Use case métier                          |
| Policy      | Autorisation                             |
| FormRequest | Validation d'entrée                      |
| Enum        | Source de vérité des statuts/transitions |
| Service     | Logique transverse                       |
| Job         | Traitement async                         |
| Resource    | Réponse API                              |

---

## Modules principaux

- Auth / Users / KYC
- Trips
- Luggages
- Bookings / BookingItems
- Transactions (CHARGE, PAYOUT, REFUND, FEE, PAYMENT_FEE)
- Payments
- Webhooks
- Audit Logs
- Queue monitoring
- Observability / Correlation ID

---

## Booking lifecycle

```
EN_ATTENTE → EN_PAIEMENT → CONFIRMEE → LIVREE → TERMINE
```

Cas alternatifs :

```
EN_PAIEMENT  → EXPIREE
CONFIRMEE    → REMBOURSEE (via webhook refund)
CONFIRMEE / LIVREE → EN_LITIGE → REMBOURSEE
```

Règles :

- aucune confirmation sans `CHARGE COMPLETED`
- capacité protégée contre l'overbooking
- transitions centralisées dans les Enums
- historique des statuts complet
- toutes les opérations critiques protégées par `DB::transaction()` + `lockForUpdate()`

---

## Système transactionnel

`Transaction` est la source de vérité financière.

Types supportés :

| Type          | Sens                    |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → Plateforme |
| `PAYOUT`      | Plateforme → Voyageur   |
| `REFUND`      | Plateforme → Expéditeur |
| `FEE`         | Commission GP-Valise    |
| `PAYMENT_FEE` | Frais PSP / banque      |

Invariants :

```
PAYOUT ⊕ REFUND                         (mutuellement exclusifs)
PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
```

Règles :

- pas de double charge / payout / refund / fee
- montants persistés à la création, jamais recalculés
- refund admin uniquement avec audit log sellé obligatoire

---

## Webhooks & async

Flow :

```
Provider
→ WebhookController (HMAC verification)
→ ProcessPaymentWebhook Job (queue: high)
→ HandlePaymentWebhook Action
→ Transaction / Booking / WebhookLog
```

Garanties :

- signature HMAC SHA-256
- idempotence via `event_id` + `lockForUpdate()`
- retry avec backoff exponentiel
- alerting Slack sur échec définitif
- traitement async avec HTTP `202 Accepted`
- `correlation_id` propagé API → Job → DB

---

## Audit trail

- `AuditLog` append-only (update/delete interdits au niveau Model)
- `integrity_hash` + `previous_hash` — chaîne SHA-256
- `seal()` appelé à chaque création via `AuditLogIntegrityService`
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

Traçabilité complète API → Job → DB — un seul UUID retrouvable dans les 3 tables critiques.

---

## Protection des queues

Commandes de supervision :

```bash
php artisan monitoring:queues   # santé des queues Redis
php artisan monitoring:webhooks # santé des webhooks
php artisan simulate:load       # simulation de charge
php artisan simulate:retry-storm # simulation retry storm
```

En cas de retry storm détecté, le système bloque automatiquement tout nouveau dispatch :

```
⛔ Dispatch bloqué sur la queue high.
Reason: Retry storm détecté sur la fenêtre récente.
Dominant job: App\Jobs\SimulateRetryStormJob (10 occurrences)
Recommended action: Suspendre temporairement les nouveaux dispatchs...
```

---

## Tests

```bash
make test
```

```
Tests:    260 passed (679 assertions)
Duration: 2.9s
```

Couverture :

- actions métier (booking, transaction, refund, payout, webhook)
- controllers API + policies
- invariants financiers (PAYOUT ⊕ REFUND, double charge, double fee)
- audit integrity chain
- correlation_id propagation
- queue monitoring + retry storm detection
- webhook idempotence + HMAC

---

## Sécurité

- Auth Sanctum
- Policies par ressource
- Rôles stricts — `ADMIN/SUPER_ADMIN` non disponibles à l'inscription publique
- KYC sur opérations sensibles
- Rate limiting financier
- HMAC webhook signature
- Audit log obligatoire sur refund admin
- `lockForUpdate()` sur toutes les opérations concurrentes

---

## CI/CD

GitHub Actions :

- installation Composer
- migrations
- PestPHP tests
- Redis service pour tests queue/monitoring

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)

---

## Installation locale

```bash
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

make up
make key
make migrate
make seed
```

Accès :

```
API        : http://localhost:8000/api/v1
Horizon    : http://localhost:8000/horizon
phpMyAdmin : http://localhost:8080
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
└── ai/security      → webhook, access control, finance sensible
```

Chaque décision technique est documentée dans `decision-log.md` avant d'être codée.

---

## Roadmap

Court terme :

- scénarios de démo recruteur enrichis
- durcissement audit signature

Moyen terme :

- Stripe / PSP réel
- `platform_accounts` multi-pays
- escrow avancé
- litiges structurés
- ledger interne complet
- multi-devise

---

## Auteur

**Lamine Kasse**
Backend Engineer — Laravel / API / systèmes transactionnels

Je conçois des backends SaaS robustes avec :

- gestion de concurrence (`lockForUpdate`, `DB::transaction()`)
- paiements asynchrones (webhooks, idempotence, retry)
- audit trail (AuditLog chain SHA-256)
- observabilité (correlation_id, logs structurés, queues)
- architecture Action-driven

📍 Casablanca, Maroc
📧 kasse.lamine.dev@icloud.com
🔗 [LinkedIn](https://www.linkedin.com/in/lamine-kasse-05742536a)
🔗 [GitHub](https://github.com/kasse222/gp-valise-api)
