# ✈️ GP Valise API

[![Tests](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)](https://github.com/kasse222/gp-valise-api/actions)
[![Swagger](https://img.shields.io/badge/docs-swagger-blue.svg)](http://localhost:8000/api/documentation)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/containerized-Docker-blue)](https://www.docker.com/)
[![Made with ❤️ by Lamine Kasse](https://img.shields.io/badge/made%20by-Lamine%20Kasse-%23ff69b4)](mailto:laminekasse.dev@gmail.com)

---

## 🧭 Sommaire

-   [🚀 Stack technique](#-stack-technique)
-   [📦 Modèles implémentés](#-modèles-implémentés)
-   [🔐 Authentification](#-authentification)
-   [📦 Réservations (Bookings)](#-réservations-bookings)
-   [🧪 Tests automatisés](#-tests-automatisés)
-   [🧱 Sécurité & Accès](#-sécurité--accès)
-   [🧬 Données de test (seeders)](#-données-de-test-seeders)
-   [⚙️ Installation locale (Docker)](#️-installation-locale-docker)
-   [🛠️ Roadmap fonctionnelle](#️-roadmap-fonctionnelle)
-   [🔗 Liens utiles](#-liens-utiles)
-   [👨‍💻 À propos](#-à-propos)

---

## 🚀 Stack technique

-   **Laravel 12**
-   **Sanctum** – Authentification via tokens
-   **PestPHP** – Framework de tests modernes
-   **MySQL** – Base de données relationnelle
-   **Docker** – Environnement dev/test/prod
-   **GitHub Actions** – CI/CD automatisé (tests, linting)
-   **Swagger (l5-swagger)** – Documentation interactive

---

## 📦 Modèles implémentés

| Modèle        | Description                                                              |
| ------------- | ------------------------------------------------------------------------ |
| `User`        | Utilisateur avec rôle : `voyageur`, `expediteur`, `admin`                |
| `Trip`        | Trajet proposé par un voyageur (lieux, capacité, date, n° vol)           |
| `Luggage`     | Valise, colis ou document à envoyer par un expéditeur                    |
| `Booking`     | Réservation d’un trajet pour un ou plusieurs bagages                     |
| `BookingItem` | Association entre réservation et bagages (kg, prix, suivi)               |
| `Payment`     | Paiement associé à une réservation                                       |
| `Report`      | Signalement sur un utilisateur, un trajet ou une réservation (morphable) |
| `Location`    | Coordonnées GPS pour suivi en temps réel                                 |

---

## 🔐 Authentification

> Token-based Auth via Laravel Sanctum

-   `POST /api/v1/register`
-   `POST /api/v1/login`
-   `GET  /api/v1/me`
-   `POST /api/v1/logout`

---

## 📦 Réservations (Bookings)

### Endpoints REST

-   `GET /api/v1/bookings`
-   `POST /api/v1/bookings`
-   `GET /api/v1/bookings/{id}`
-   `PUT /api/v1/bookings/{id}`
-   `DELETE /api/v1/bookings/{id}`

### Actions métier

-   `POST /api/v1/bookings/{id}/confirm`
-   `POST /api/v1/bookings/{id}/cancel`
-   `POST /api/v1/bookings/{id}/complete`

### Services utilisés

-   `App\Actions\Booking\ReserveBooking`
-   `App\Actions\Booking\ConfirmBooking`
-   `App\Actions\Booking\CancelBooking`
-   `App\Actions\Booking\CompleteBooking`

---

## 🧪 Tests automatisés

> TDD avec PestPHP

### Couverture :

-   Authentification
-   Réservations : création, statuts, edge-cases
-   Règles métier
-   Accès interdits → 403

---

## 🧱 Sécurité & Accès

-   `auth:sanctum` + FormRequest
-   Enum `BookingStatusEnum`
-   Validation des rôles, poids, capacité
-   Plans à venir :
    -   Policies, Gates
    -   Gestion des rôles avec `spatie/laravel-permission`
    -   KYC simplifié
    -   OWASP API Checklist

---

## 🧬 Données de test (seeders)

-   15 utilisateurs (`5 voyageurs`, `5 expéditeurs`, `5 admins`)
-   30 `Trips`
-   40 `Luggages`
-   20 `Bookings` avec items & paiements simulés
-   10 `Reports` (signalements)
-   150 coordonnées GPS (`Locations`)

---

## ⚙️ Installation locale (Docker)

```bash
# 1. Cloner le projet
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

# 2. Copier l’environnement
make copy-env

# 3. Lancer les conteneurs
make up

# 4. Initialiser l’app
make key
make migrate
make seed

# 5. Accès
# App        → http://localhost:8000
# Swagger    → http://localhost:8000/api/documentation
# PhpMyAdmin → http://localhost:8080 (login: gpvalise_user / secret)
```

🛠️ Roadmap fonctionnelle
Tâche État
Authentification Sanctum ✅ Terminé
Booking CRUD + logique métier ✅ Terminé
Trip CRUD complet ✅ Terminé
Documentation Swagger ✅ En place
Dockerisation (Laravel + MySQL + NGINX) 🔄 En cours
CI/CD GitHub Actions 🔄 En cours
Sécurité avancée (Policies, rôles, etc.) 🔜 À venir
Modules Luggage, Payment, Users complets 🔜 À venir
Backups, monitoring, alertes 🔜 À venir

👨‍💻 À propos
Projet développé dans le cadre d'une reconversion professionnelle vers le back-end & DevOps.

Auteur : Kasse Lamine
📧 Contact : lamine.kasse.dev@gmail.com
