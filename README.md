# GP-Valise API — Backend Marketplace Logistique (Laravel)

Backend API Laravel pour une plateforme logistique entre voyageurs et expéditeurs.

GP-Valise modélise un cas réel de marketplace transactionnelle avec gestion complète des réservations, des flux financiers, des paiements asynchrones et de la supervision des queues.

---

[![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Tests](https://img.shields.io/badge/tests-189%20passing-brightgreen)](#tests)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)

---

## Contexte produit

GP-Valise met en relation trois types d'acteurs :

- **Voyageur** — publie un trajet avec une capacité de transport disponible
- **Expéditeur** — réserve des kilogrammes pour transporter un colis
- **Admin** — supervise les flux, les litiges et la sécurité

Le système repose sur trois piliers : logistique (trajets, capacité, livraison), confiance (rôles, KYC, signalements) et finance (transactions, payout, refund).

---

## Architecture

Pattern principal :

```
Controller → Action → Model / Enum / Validator
```

Traitements asynchrones :

```
WebhookController → Job → Action → Model
```

Répartition des responsabilités :

- **Controller** — orchestration HTTP uniquement
- **Action** — logique métier isolée et testable
- **Policy** — contrôle d'accès par ressource
- **FormRequest** — validation des entrées
- **Enum** — règles métier et state machine
- **Job** — traitement asynchrone
- **Service** — logique transverse (utilisé avec parcimonie)

---

## Flows métier

### Booking lifecycle

```
EN_ATTENTE → EN_PAIEMENT → CONFIRMEE
                    |
                 EXPIREE

CONFIRMEE → LIVREE → TERMINE
LIVREE → EN_LITIGE → REMBOURSEE
```

### Paiement et refund (asynchrone)

```
CHARGE → provider → COMPLETED / FAILED

REFUND → PENDING → webhook → COMPLETED (booking REMBOURSEE)
                           → FAILED    (booking EN_LITIGE)
```

Idempotence garantie via `event_id`. Traitement via queue `high`.

### Supervision des queues

```
Scheduler → QueueHealthService → classification → alerte Slack async
```

Classifications possibles : `healthy`, `traffic_spike`, `slow_processing`, `retry_storm_pressure`, `capacity_pressure`.

---

## Points techniques notables

- Concurrence maîtrisée via `lockForUpdate` sur les réservations et paiements
- Idempotence complète sur les webhooks, batch et actions métier
- `ShouldDispatchAfterCommit` sur tous les events pour éviter les dispatches avant commit DB
- Trois tiers de queues (`high`, `default`, `low`) avec superviseurs configurés par environnement
- Détection de retry storm via `QueueProtectionService`
- Transactions comme source de vérité financière (charge / payout / refund / fee)
- State machine métier dans les Enums avec matrice de transitions et audit trail

---

## Tests

```bash
make test
```

- 189 tests, 537 assertions
- Runtime : ~2s en local (SQLite in-memory via Docker), ~3.8s en CI
- Base : SQLite in-memory — isolation complète par test
- Redis réel utilisé pour les tests de monitoring des queues

La configuration est centralisée dans `phpunit.xml` avec `force="true"` pour garantir l'isolation même lorsque les variables Docker écrasent l'environnement.

---

## CI (GitHub Actions)

Pipeline déclenché sur chaque push et pull request :

1. Installation des dépendances (Composer avec cache)
2. Bootstrap Laravel via `.env.ci`
3. Migrations SQLite
4. Exécution Pest

Redis est disponible comme service dans la CI pour les tests de monitoring. Aucune dépendance MySQL en CI.

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

- API : http://localhost:8000
- Horizon : http://localhost:8000/horizon

Stack Docker : PHP-FPM, Nginx, MySQL, Redis, Horizon, Scheduler.

---

## Transactions

Types supportés : `CHARGE`, `PAYOUT`, `REFUND`, `FEE`.

Règles métier :

- Le payout est déclenché uniquement après livraison confirmée
- Le refund est conditionné par le statut métier du booking
- Blocage si un payout existe déjà pour le booking
- Idempotence garantie sur toutes les opérations financières

---

## Sécurité

- Authentification via Laravel Sanctum
- Policies par ressource
- Vérification KYC sur les opérations sensibles
- Rate limiting sur les endpoints financiers
- Validation de signature webhook (HMAC SHA-256)
- Aucun secret hardcodé en fallback de configuration

---

## Compromis techniques assumés

- SQLite en CI et en test local — écart minimal avec MySQL pour les cas couverts
- `FakePaymentProvider` en place — interface définie, provider réel à brancher
- `Payment` model maintenu en lecture seule — `Transaction` est la source de vérité financière
- Commission hardcodée à 15% — `Plan::getCommissionPercent()` existe, migration à faire

---

## Documentation

- `docs/ARCHITECTURE.md` — architecture complète et décisions techniques
- `docs/AUDIT.md` — évolution du projet et choix de design

---

## Roadmap

Court terme : observabilité (Pulse, logs corrélés), alerting Slack complet.

Moyen terme : intégration Stripe réelle, payout asynchrone complet, coverage CI.

---

## Auteur

Backend Developer — Laravel / API / Systèmes transactionnels

Casablanca, Maroc — disponible immédiatement en CDI.

[laminekasse.dev@gmail.com](mailto:laminekasse.dev@gmail.com)
