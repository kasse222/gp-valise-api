# ✈️ GP-Valise API — Backend Marketplace Logistique (Laravel)

Backend API Laravel pour une plateforme logistique entre voyageurs et expéditeurs.

GP-Valise modélise un **cas réel de marketplace transactionnelle**, avec gestion complète :

- réservations (booking lifecycle)
- flux financiers (charge, payout, refund)
- paiements asynchrones (webhooks type Stripe)
- supervision des queues et alerting

> 🎯 Objectif : construire un backend **cohérent, robuste et prêt pour un environnement SaaS réel**

---

![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/containerized-Docker-blue)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/tests-184%20passing-brightgreen)](#tests)

---

## 🚀 Vision produit

GP-Valise est une API backend pour une plateforme logistique de confiance entre :

- **Voyageur** → publie un trajet avec capacité disponible
- **Expéditeur** → réserve des kg pour transporter un colis
- **Admin** → supervise flux, litiges et sécurité

Le système repose sur trois piliers :

- **Logistique** → trajets, capacité, livraison
- **Confiance** → rôles, KYC, signalements
- **Finance** → transactions, payout, refund

---

## 🧠 Core Capabilities

- Booking lifecycle robuste (state machine métier)
- Gestion de capacité avec concurrence (lockForUpdate)
- Paiements asynchrones (webhook-driven)
- Transactions traçables (charge / payout / refund)
- Idempotence complète (webhook, batch, actions)
- Supervision des queues (Redis + Horizon)
- Alerting critique (Slack async)
- Architecture orientée cas d’usage (Action-first)

---

## 🏗️ Architecture

### Pattern principal

```text
Controller → Action → Model / Enum / Validator
```

### Traitements async

```text
WebhookController → Job → Action → Model
```

### Responsabilités

- Controller → orchestration HTTP
- Action → logique métier
- Policy → contrôle d’accès
- FormRequest → validation
- Enum → règles métier (state machine)
- Job → traitement async
- Service → logique transverse (rare)

---

## 🔄 Flows métier clés

### 📦 Booking lifecycle

```text
EN_ATTENTE → EN_PAIEMENT → CONFIRMEE
                     ↓
                  EXPIREE

CONFIRMEE → LIVREE → TERMINE

LIVREE → EN_LITIGE → REMBOURSEE
```

---

### 💳 Paiement & refund (async)

```text
CHARGE → provider → COMPLETED / PENDING / FAILED

REFUND → PENDING
           ↓
        webhook
           ↓
COMPLETED → booking REMBOURSEE
FAILED    → booking reste EN_LITIGE
```

✔️ Idempotence via `event_id`
✔️ Traitement async via queue `high`

---

### 📡 Supervision des queues

```text
Scheduler / Command
   → QueueHealthService
      → analyse backlog / age / failed / retry storm
         → classification intelligente
            → alert Slack async
```

Statuts possibles :

- `healthy`
- `traffic_spike`
- `slow_processing`
- `retry_storm_pressure`
- `capacity_pressure`

---

## ⚙️ Observability & Queue Strategy

### Queues

- `high` → webhooks paiement, jobs critiques
- `default` → logique métier standard
- `low` → alerting, tâches secondaires

### Principes

- isolation des charges
- priorisation métier
- détection multi-signaux :
    - backlog
    - âge du job
    - failed_jobs
    - retry storm

### Alerting

- Slack async (queue low)
- fallback logs + email
- contexte structuré pour diagnostic rapide

---

## 🧱 Runtime (Docker)

Architecture containerisée :

- `app` → PHP-FPM (API)
- `horizon` → workers Redis
- `scheduler` → tâches planifiées
- `nginx` → reverse proxy
- `redis` → queues / cache
- `mysql` → base de données

---

## 💸 Transactions

Types supportés :

- `CHARGE` → paiement expéditeur
- `PAYOUT` → paiement voyageur
- `REFUND` → remboursement
- `FEE` → commission plateforme

### Règles clés

- payout déclenché uniquement après livraison
- refund uniquement si autorisé métier
- blocage si payout déjà effectué
- idempotence garantie

---

## 🧪 Tests

- ✅ **184 tests**
- ✅ **524 assertions**
- ⚡ ~6.6s en local

### Couverture

- booking lifecycle
- expiration batch
- concurrence
- webhook async
- refund
- queue monitoring
- sécurité Horizon

---

## 🔐 Sécurité

- Sanctum (auth API)
- Policies par ressource
- KYC / verified user
- throttling opérations sensibles
- signature webhook

---

## 📚 Documentation

- `ARCHITECTURE.md` → architecture complète
- `AUDIT.md` → évolution technique & décisions
- `BOOKING_FLOW.md` → concurrence & idempotence
- `QUEUE_MONITORING_FLOW.md` → supervision & alerting (à venir)

---

## 🚀 Installation locale

```bash
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

make copy-env
make up
make key
make migrate
make seed
```

### Accès

- API → [http://localhost:8000](http://localhost:8000)
- Swagger → [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)

---

## 🛣️ Roadmap

### Court terme

- finalisation Slack webhook
- observabilité avancée
- métriques persistées

### Moyen terme

- payout async complet
- refund partiel
- intégration Stripe réelle
- auto-scaling workers

---

## 👨‍💻 Auteur

Backend Developer (Laravel / API / SaaS)

- Laravel
- architecture backend
- systèmes transactionnels
- Docker / CI/CD

📧 [laminekasse.dev@gmail.com](mailto:laminekasse.dev@gmail.com)
🌍 Objectif court terme : Remote / Maroc
🌍 Objectif moyen terme : Remote international / Europe

---

```

```
