# ✈️ GP-Valise API

# ✈️ GP-Valise API

Backend API Laravel pour une plateforme logistique entre voyageurs et expéditeurs.

Ce projet implémente un système de réservation de capacité (kg) avec :

- gestion de concurrence (lockForUpdate)
- logique idempotente (safe retry)
- expiration automatique via scheduler
- architecture event-driven

🎯 Objectif : construire une API robuste, scalable et proche des contraintes réelles de production.

![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/containerized-Docker-blue)](https://www.docker.com/)
[![Made by Lamine Kasse](https://img.shields.io/badge/made%20by-Lamine%20Kasse-%23ff69b4)](mailto:laminekasse.dev@gmail.com)

API Laravel **API-only** pour une plateforme logistique entre voyageurs et expéditeurs : gestion des trajets, réservations, valises/colis, paiements/transactions, invitations, plans, signalements, etc.

> 📚 Swagger (local) : `http://localhost:8000/api/documentation`

---

## 🧭 Sommaire

- [🚀 Stack technique](#-stack-technique)
- [📦 Modèles implémentés](#-modèles-implémentés)
- [🔐 Authentification](#-authentification)
- [📦 Réservations & Valises](#-réservations--valises)
- [💳 Paiements & Transactions](#-paiements--transactions)
- [🧪 Tests automatisés](#-tests-automatisés)
- [🧱 Sécurité & Accès](#-sécurité--accès)
- [🧬 Données de test (seeders)](#-données-de-test-seeders)
- [⚙️ Installation locale (Docker)](#️-installation-locale-docker)
- [🛣️ Roadmap](#️-roadmap)
- [👨‍💻 À propos](#-à-propos)

---

## 🚀 Stack technique

- **Laravel 12** (API-only)
- **Sanctum** (auth tokens)
- **PestPHP** (tests)
- **MySQL 8** (dev) + **SQLite in-memory** (tests/CI)
- **Docker** (environnement isolé)
- **Swagger (l5-swagger)** (documentation interactive)
- **GitHub Actions** (CI)
- **Enums métiers** (statuts, rôles, types…)
- **Actions Laravel** (logique métier isolée)
- **Policies** (contrôle d’accès centralisé)
- **FormRequests** (validation)

---

## 📦 Modèles implémentés

| Modèle                 | Description                                    |
| ---------------------- | ---------------------------------------------- |
| `User`                 | Voyageur, Expéditeur, Admin                    |
| `Trip`                 | Trajets proposés                               |
| `Booking`              | Réservation (peut contenir plusieurs items)    |
| `BookingItem`          | Détail des objets/valises réservés             |
| `Luggage`              | Valise/colis à envoyer (statut, volume, poids) |
| `BookingStatusHistory` | Historique des statuts                         |
| `Payment`              | Paiements utilisateurs                         |
| `Transaction`          | Écritures financières + remboursement (refund) |
| `Report`               | Signalements                                   |
| `Location`             | Points GPS (départ/étape/arrivée)              |
| `Plan`                 | Abonnements freemium / premium                 |
| `Invitation`           | Invitations / parrainage                       |

---

## 🧱 Architecture

Le projet suit une architecture orientée cas d’usage :

- Controller → orchestration HTTP
- Action → logique métier
- Policy → contrôle d’accès
- FormRequest → validation
- Enum → règles métier (state machine)

👉 Cette approche permet une séparation claire des responsabilités et une meilleure maintenabilité.

## 🧠 Core Business Logic (Booking Lifecycle)

Le cœur du projet repose sur une gestion avancée des réservations (booking lifecycle) :

1. Création → `EN_PAIEMENT`
    - bloque temporairement les ressources (kg)
    - définit `payment_expires_at`

2. Paiement réussi → `CONFIRMEE`
    - réservation validée
    - capacité définitivement utilisée

3. Expiration automatique
    - booking → `EXPIREE`
    - ressources libérées (valises → `EN_ATTENTE`)
    - capacité restaurée

4. Livraison → `LIVREE` puis `TERMINE`

👉 Ce flow garantit la cohérence métier et évite les conflits de capacité.

## 🔐 Authentification

> Tous les endpoints API sont préfixés par `/api/v1`.

| Méthode | Endpoint             | Description         |
| ------- | -------------------- | ------------------- |
| POST    | `/api/v1/register`   | Inscription         |
| POST    | `/api/v1/login`      | Connexion           |
| GET     | `/api/v1/me`         | Profil connecté     |
| POST    | `/api/v1/logout`     | Déconnexion         |
| POST    | `/api/v1/logout-all` | Déconnexion globale |

- Tokens via **Sanctum**
- Validation via **FormRequests**
- Accès via **Policies** + middleware de rôle

---

## 📦 Réservations & Valises

### Booking – Endpoints

| Méthode | Route                                 | Description            |
| ------- | ------------------------------------- | ---------------------- |
| GET     | `/api/v1/bookings`                    | Liste des réservations |
| POST    | `/api/v1/bookings`                    | Créer une réservation  |
| PUT     | `/api/v1/bookings/{booking}`          | Modifier               |
| DELETE  | `/api/v1/bookings/{booking}`          | Supprimer              |
| POST    | `/api/v1/bookings/{booking}/confirm`  | Confirmer              |
| POST    | `/api/v1/bookings/{booking}/cancel`   | Annuler                |
| POST    | `/api/v1/bookings/{booking}/complete` | Marquer livrée         |

- Statuts via `BookingStatusEnum`
- Transitions historisées (`BookingStatusHistory`)
- Autorisation via `BookingPolicy`

### Luggage – Endpoints

| Méthode | Route                        | Description       |
| ------- | ---------------------------- | ----------------- |
| GET     | `/api/v1/luggages`           | Liste des valises |
| POST    | `/api/v1/luggages`           | Créer             |
| PUT     | `/api/v1/luggages/{luggage}` | Modifier          |
| DELETE  | `/api/v1/luggages/{luggage}` | Supprimer         |

- Sécurité : `LuggagePolicy`
- Validation : FormRequests
- Enum : `LuggageStatusEnum`

---

## 💳 Paiements & Transactions

### Payments (CRUD)

| Méthode   | Route                        | Description |
| --------- | ---------------------------- | ----------- |
| GET       | `/api/v1/payments`           | Lister      |
| POST      | `/api/v1/payments`           | Créer       |
| GET       | `/api/v1/payments/{payment}` | Voir        |
| PATCH/PUT | `/api/v1/payments/{payment}` | Modifier    |
| DELETE    | `/api/v1/payments/{payment}` | Supprimer   |

### Transactions (index/show/store + refund dédié)

| Méthode | Route                                       | Description |
| ------- | ------------------------------------------- | ----------- |
| GET     | `/api/v1/transactions`                      | Lister      |
| POST    | `/api/v1/transactions`                      | Créer       |
| GET     | `/api/v1/transactions/{transaction}`        | Voir        |
| POST    | `/api/v1/transactions/{transaction}/refund` | Rembourser  |

✅ Le endpoint **refund** est protégé par :

- `verified_user`
- `kyc`
- `throttle.sensitive:finance,5,1`

---

## 🧪 Tests automatisés

- **Environnement :** SQLite in-memory (CI), MySQL local optionnel
- **Statut actuel :**
    - 136 tests / 353 assertions
    - Durée : **~6.44s**

---

## 🧱 Sécurité & Accès

- `auth:sanctum` obligatoire (routes protégées)
- Policies actives :
    - `BookingPolicy`, `LuggagePolicy`, `PaymentPolicy`, `TransactionPolicy`, etc.
- Middlewares personnalisés :
    - `EnsureRole`
    - `verified_user`
    - `kyc`
    - `throttle.sensitive`
    - `force.json` (API JSON par défaut)
- Enums = source de vérité métier (statuts, transitions, badges)

---

## 🧬 Données de test (seeders)

| Élément      |     Quantité |
| ------------ | -----------: |
| Users        | 15 (3 rôles) |
| Trips        |           30 |
| Bookings     |           20 |
| Luggages     |           40 |
| BookingItems | auto-générés |
| Payments     |           20 |
| Reports      |           10 |
| Locations    |          150 |
| Transactions |           10 |
| Invitations  |            5 |

---

## ⏱️ Batch & Scheduler

Le projet intègre un système de traitement automatique des réservations expirées :

- Commande : `bookings:expire-pending`
- Exécution via **Laravel Scheduler**
- Traitement par batch (`chunkById`)
- Gestion des erreurs + logs

### Fonctionnement

Toutes les X minutes :

- scan des bookings `EN_PAIEMENT`
- si `payment_expires_at < now()`
- passage en `EXPIREE`
- libération des ressources

👉 Ce mécanisme simule un comportement réel de système SaaS.

## ⚙️ Installation locale (Docker)

```bash
# Cloner le projet
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

# Copier l’environnement
make copy-env

# Lancer Docker
make up

# Générer la clé + migrations + seeds
make key
make migrate
make seed

# Interfaces :
# API        → http://localhost:8000
# Swagger    → http://localhost:8000/api/documentation
# PhpMyAdmin → http://localhost:8080
```

🛣️ Roadmap

Tests d’intégration avancés : pagination, filtres, eager loading, withCount

Sécurité OWASP avancée : rate limiting global + durcissement endpoints sensibles

Observabilité : logs structurés + événements métiers (optionnel)

Durcissement DevOps : Docker prod (multi-stage), healthchecks, stratégie backup/rotation

👨‍💻 À propos

Projet open-source développé par Lamine Kasse dans le cadre d’une montée en compétence Back-End Laravel / API / DevOps.

GitHub : kasse222

Email : laminekasse.dev@gmail.com
