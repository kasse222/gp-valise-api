# ✈️ GP-Valise API — Backend Marketplace Logistique (Laravel)

Backend API Laravel pour une plateforme logistique entre voyageurs et expéditeurs.

GP-Valise modélise un **cas réel de marketplace transactionnelle**, avec gestion complète :

- réservations (booking lifecycle)
- flux financiers (charge, payout, refund)
- paiements asynchrones (webhooks type Stripe)
- supervision des queues et alerting

> 🎯 Objectif : construire un backend **cohérent, robuste et prêt pour un environnement SaaS réel**

---

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Tests](https://img.shields.io/badge/tests-185%20passing-brightgreen)](#tests)

⚡ Runtime des tests :

- ~8s en local (SQLite in-memory)
- ~3.2s en CI (GitHub Actions)

- ✅ **185 tests**
- ✅ **531 assertions**

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
- Gestion de capacité avec concurrence (`lockForUpdate`)
- Paiements asynchrones (webhook-driven)
- Transactions traçables (charge / payout / refund)
- Idempotence complète (webhook, batch, actions)
- Supervision des queues (Redis + Horizon)
- Alerting critique (Slack async)
- Architecture orientée cas d’usage (Action-first)

---

## 🏗️ Architecture

### Pattern principal

```

Controller → Action → Model / Enum / Validator

```

### Traitements async

```

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

```

EN_ATTENTE → EN_PAIEMENT → CONFIRMEE
↓
EXPIREE

CONFIRMEE → LIVREE → TERMINE

LIVREE → EN_LITIGE → REMBOURSEE

```

---

### 💳 Paiement & refund (async)

```

CHARGE → provider → COMPLETED / FAILED

REFUND → PENDING
↓
webhook
↓
COMPLETED → booking REMBOURSEE
FAILED → booking reste EN_LITIGE

```

✔️ Idempotence via `event_id`
✔️ Traitement async via queue `high`

---

## 📡 Monitoring & Queue Strategy

- Détection multi-signaux :
    - backlog
    - âge des jobs
    - failed jobs
    - retry storm
- Classification :
    - healthy
    - traffic_spike
    - slow_processing
    - retry_storm_pressure
    - capacity_pressure

---

## ⚙️ Environnement

### Local (Docker)

- PHP-FPM (app)
- Nginx
- Redis (queues)
- MySQL
- Horizon (workers)
- Scheduler

---

## 🧪 Tests

### Commande standard

```bash
php artisan optimize:clear
php artisan migrate:fresh
./vendor/bin/pest
```

### Particularités

- DB = **SQLite in-memory**
- Redis utilisé uniquement pour certains tests (monitoring)
- isolation complète (cache, queue, session en mémoire)

👉 Configuration centralisée dans `phpunit.xml`

---

## ⚙️ CI (GitHub Actions)

Pipeline minimal :

- install dependencies
- bootstrap Laravel
- migrate
- run Pest

### Points clés

- SQLite pour rapidité
- Redis service pour tests monitoring
- aucune dépendance MySQL en CI

---

## 💸 Transactions

Types supportés :

- `CHARGE` → paiement expéditeur
- `PAYOUT` → paiement voyageur
- `REFUND` → remboursement
- `FEE` → commission plateforme

### Règles métier

- payout après livraison uniquement
- refund conditionné métier
- blocage si payout déjà effectué
- idempotence garantie

---

## 🔐 Sécurité

- Auth API → Laravel Sanctum
- Policies par ressource
- KYC / utilisateurs vérifiés
- throttling opérations sensibles
- signature webhook

---

## 📚 Documentation

- `ARCHITECTURE.md`
- `AUDIT.md`

---

## 🛣️ Roadmap

### Court terme

- observabilité avancée
- alerting Slack
- métriques persistées

### Moyen terme

- Stripe réel
- payout async complet
- auto-scaling workers

---

## 👨‍💻 Auteur

Backend Developer (Laravel / API / SaaS)

- Laravel
- architecture backend
- systèmes transactionnels
- Docker / CI/CD

📧 [laminekasse.dev@gmail.com](mailto:laminekasse.dev@gmail.com)
🌍 Objectif : Remote / Europe / Freelance

```

```
