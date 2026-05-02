Oui. Ton README actuel est bon, mais il est **en retard** sur ton vrai niveau actuel.

Il faut le mettre à jour avec :

- `.adamas`
- audit log integrity
- correlation_id
- 226 tests / 594 assertions
- observabilité HTTP → Job → logs
- backend SaaS transactionnel plus assumé

Voici une version refactorée propre de `README.md` :

````md
# GP-Valise API — Backend SaaS Logistique & Transactionnel

Backend API Laravel 12 pour une marketplace logistique entre voyageurs et expéditeurs.

GP-Valise modélise un système SaaS réel avec réservation de capacité, paiements asynchrones, refunds, payouts, audit trail, observabilité et supervision des queues.

---

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Tests](https://img.shields.io/badge/tests-226%20passing-brightgreen)](#tests)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)

---

## Objectif produit

GP-Valise permet :

- à un voyageur de publier un trajet avec une capacité disponible ;
- à un expéditeur de réserver de l’espace pour transporter un colis ou une valise ;
- à la plateforme de sécuriser paiement, livraison, refund, payout et litige.

Le projet vise un backend SaaS crédible, traçable et testable, avec une architecture proche d’un système transactionnel fintech.

---

## Stack technique

- Laravel 12
- PHP 8.2+
- MySQL en local
- PostgreSQL en cible future
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

```txt
Controller → FormRequest / Policy → Action → Model / Enum / Service → Resource
```
````

Traitements asynchrones :

```txt
WebhookController → Job → Action → Transaction / Booking / WebhookLog
```

Responsabilités :

- Controller : orchestration HTTP uniquement
- Action : use case métier
- Policy : autorisation
- FormRequest : validation d’entrée
- Enum : source de vérité des statuts/transitions
- Service : logique transverse
- Job : traitement async
- Resource : réponse API

---

## Modules principaux

- Auth / Users / KYC
- Trips
- Luggages
- Bookings
- BookingItems
- Transactions
- Payments
- Refund / Payout / Fee
- Webhooks
- Audit Logs
- Queue monitoring
- Observability / Correlation ID

---

## Booking lifecycle

```txt
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ LIVREE
→ TERMINE
```

Cas alternatifs :

```txt
EN_PAIEMENT → EXPIREE
CONFIRMEE / LIVREE → EN_LITIGE
EN_LITIGE → REMBOURSEE
```

Règles importantes :

- aucune confirmation sans `CHARGE COMPLETED`
- capacité protégée contre l’overbooking
- transitions centralisées dans les Enums
- historique des statuts
- opérations critiques protégées par transaction DB

---

## Système transactionnel

`Transaction` est la source de vérité financière.

Types supportés :

- `CHARGE`
- `PAYOUT`
- `REFUND`
- `FEE`
- `PAYMENT_FEE`

Invariants :

```txt
PAYOUT ⊕ REFUND
PAYOUT + FEE + REFUND <= CHARGE
profit_net = FEE - PAYMENT_FEE
```

Règles :

- pas de double charge
- pas de double payout
- pas de double refund
- pas de double fee
- montants persistés à la création
- aucun recalcul historique
- refund admin uniquement avec audit strict

---

## Webhooks & async

Flow webhook :

```txt
Provider
→ WebhookController
→ vérification HMAC
→ ProcessPaymentWebhook Job
→ HandlePaymentWebhook Action
→ Transaction / Booking / WebhookLog
```

Garanties :

- signature HMAC SHA-256
- idempotence via `event_id`
- retry contrôlé
- queue `high`
- logs d’échec
- alerting possible
- traitement async avec HTTP `202 Accepted`

---

## Audit trail

Le projet intègre un système d’audit admin :

- `AuditLog` append-only
- update/delete interdits
- lecture admin uniquement
- `integrity_hash`
- `previous_hash`
- chaîne d’intégrité vérifiable
- tests de détection de corruption

Objectif :

```txt
prouver qui a fait quoi, quand, pourquoi, et sur quelle ressource
```

---

## Observabilité

Le système utilise un `correlation_id` pour tracer les flux :

```txt
HTTP request
→ response header
→ logs Laravel
→ queued job
→ webhook flow
```

Header :

```txt
X-Correlation-ID
```

Actuellement couvert :

- génération si absent
- conservation si fourni
- présence même sur erreur 401
- propagation HTTP → Job
- contexte logs Laravel

Prochaine étape :

```txt
propagation DB → transactions / audit_logs / webhook_logs
```

---

## Monitoring

Le projet contient des commandes de supervision :

- queue health
- webhook health
- retry storm detection
- load simulation
- retry storm simulation

Objectifs :

- détecter backlog
- détecter slow processing
- détecter retry storm
- protéger Horizon / Redis
- alerter sur incidents critiques

---

## Tests

```bash
make test
```

État actuel :

```txt
226 tests passed
594 assertions
runtime ~2.5s
```

Couverture :

- actions métier
- controllers API
- policies
- transactions
- refunds
- payouts
- webhooks
- jobs
- monitoring
- audit integrity
- correlation_id

---

## CI/CD

GitHub Actions exécute :

- installation Composer
- bootstrap Laravel
- migrations
- Pest tests
- Redis service pour tests queue/monitoring

Objectif : pipeline rapide, reproductible et fiable.

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

```txt
API     : http://localhost:8000
Horizon : http://localhost:8000/horizon
```

---

## .adamas

Le dossier `.adamas/` documente les règles d’ingénierie du projet :

```txt
.adamas/
├── ai/core
├── ai/domain
├── ai/engineering
├── ai/governance
├── ai/observability
└── ai/security
```

Il contient :

- règles d’architecture
- règles métier
- contraintes financières
- méthodologie d’audit
- checklist de review
- règles Git
- observabilité
- sécurité webhook
- access control

Objectif :

```txt
Code + règles + audit + observabilité = système fiable
```

---

## Sécurité

- Auth Sanctum
- Policies par ressource
- KYC sur opérations sensibles
- Rate limiting financier
- HMAC webhook signature
- audit admin obligatoire
- logs sans données sensibles
- contrôle strict des rôles

---

## Roadmap

Court terme :

- propagation DB du `correlation_id`
- amélioration README / docs publiques
- scénarios de démo recruteur
- durcissement audit signature

Moyen terme :

- Stripe / PSP réel
- platform_accounts
- escrow avancé
- litiges structurés
- ledger interne
- multi-pays / multi-devise

---

---

## 👨‍💻 Auteur

**Lamine Kasse**  
Backend Engineer — Laravel / API / systèmes transactionnels

Je conçois des backends SaaS robustes avec :

- gestion de concurrence (lockForUpdate)
- paiements asynchrones (webhooks, idempotence)
- audit trail (AuditLog chain)
- observabilité (correlation_id, logs, queues)
- architecture Action-driven

📍 Casablanca, Maroc  
📧 kasse.lamine.dev@icloud.com  
🔗 LinkedIn : https://www.linkedin.com/in/lamine-kasse-05742536a

---
