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

-   **Laravel 12** (API-only)
-   **Sanctum** – Authentification par token
-   **PestPHP** – Framework de tests modernes
-   **MySQL 8** – Base de données relationnelle
-   **Docker** – Environnement de dev/test/prod
-   **Swagger (l5-swagger)** – Documentation interactive
-   **GitHub Actions** – CI/CD (build + tests)
-   **Enums centrés métier** – statuts, rôles, types

---

## 📦 Modèles implémentés

| Modèle                 | Description                                                            |
| ---------------------- | ---------------------------------------------------------------------- |
| `User`                 | Utilisateur (`voyageur`, `expéditeur`, `admin`)                        |
| `Trip`                 | Trajet proposé avec lieu, capacité, dates, type (`standard`, etc.)     |
| `Luggage`              | Colis/valise à expédier, avec suivi et statut (`enum`)                 |
| `Booking`              | Réservation d’un trajet pour un ou plusieurs bagages                   |
| `BookingItem`          | Association réservation/valise (kg, prix, suivi)                       |
| `BookingStatusHistory` | Historique des changements de statut (log métier horodaté)             |
| `Payment`              | Paiement lié à une réservation                                         |
| `Report`               | Signalement morphable (utilisateur, trajet, réservation)               |
| `Location`             | Coordonnées GPS (géolocalisation live)                                 |
| `Plan`                 | Abonnement utilisateur (freemium, premium, etc.)                       |
| `Invitation`           | Invitation à rejoindre la plateforme (recommandation, lien parrainage) |
| `Transaction`          | Suivi comptable des paiements internes/externes                        |

---

## 🔐 Authentification

-   `POST /api/v1/register`
-   `POST /api/v1/login`
-   `GET /api/v1/me`
-   `POST /api/v1/logout`

> Token-based (Laravel Sanctum)  
> FormRequest + Policy + Enum + Roles

---

## 📦 Réservations (Bookings)

### Endpoints REST

| Méthode | Route                            | Description               |
| ------- | -------------------------------- | ------------------------- |
| GET     | `/api/v1/bookings`               | Liste des réservations    |
| POST    | `/api/v1/bookings`               | Créer une réservation     |
| GET     | `/api/v1/bookings/{id}`          | Voir une réservation      |
| PUT     | `/api/v1/bookings/{id}`          | Modifier une réservation  |
| DELETE  | `/api/v1/bookings/{id}`          | Supprimer une réservation |
| POST    | `/api/v1/bookings/{id}/confirm`  | Confirmer une réservation |
| POST    | `/api/v1/bookings/{id}/cancel`   | Annuler une réservation   |
| POST    | `/api/v1/bookings/{id}/complete` | Marquer comme livrée      |

### Actions métier

> Toutes les transitions de statuts (`CONFIRMEE`, `ANNULEE`, `TERMINE`) :

-   Centralisées via `BookingStatusEnum`
-   Historisées via `BookingStatusHistory::log()`
-   Sécurisées par `BookingPolicy`

🎒 **Valises (Luggage)**

| Méthode | Route                   | Description                         |
| ------- | ----------------------- | ----------------------------------- |
| GET     | `/api/v1/luggages`      | Lister les valises de l’utilisateur |
| POST    | `/api/v1/luggages`      | Créer une valise                    |
| GET     | `/api/v1/luggages/{id}` | Voir une valise                     |
| PUT     | `/api/v1/luggages/{id}` | Modifier une valise                 |
| DELETE  | `/api/v1/luggages/{id}` | Supprimer une valise                |

-   Enum : `LuggageStatusEnum`
-   Sécurité : `LuggagePolicy`
-   Validation : `StoreLuggageRequest`, `UpdateLuggageRequest`

---

## 🧪 Tests automatisés

> Écrits en **PestPHP** (tests simples + tests de logique métier + edge-cases)

### Ce qui est couvert :

-   Auth : register/login/logout
-   Booking CRUD
-   Transitions de statut (confirm, cancel, complete)
-   Erreurs métier (ex : réservation déjà terminée)
-   Enum : logique métier dans `BookingStatusEnum::canTransitionTo()`

---

## 🧱 Sécurité & Accès

-   Authentification : `auth:sanctum`
-   Middleware : `EnsureRole`, `EnsureKYC`, `Throttle`
-   FormRequests : validation sécurisée
-   Enum : `BookingStatusEnum`, `LuggageStatusEnum`, `TripTypeEnum`
-   Policies Laravel : `BookingPolicy`, `LuggagePolicy`
-   Historique des statuts : sécurisé, immuable
-   Roadmap :
    -   Gestion des permissions via `spatie/laravel-permission`
    -   Intégration KYC (upload docs + validation)
    -   Checklist sécurité OWASP pour API

---

## 🧬 Données de test (Seeders)

-   👤 15 utilisateurs (`5 voyageurs`, `5 expéditeurs`, `5 admins`)
-   ✈️ 30 `Trips` (variés, avec `type_trip`)
-   🎒 40 `Luggages`
-   📦 20 `Bookings` avec `BookingItems` générés
-   💰 20 `Payments` simulés
-   🔁 `BookingStatusHistory` auto-généré au statut initial
-   🛡️ 10 `Reports`
-   📍 150 `Locations` (coordonnées aléatoires)
-   💸 10 `Transactions` (paiement simulé)
-   📨 5 `Invitations` créées

---

## ⚙️ Installation locale (Docker)

```bash
# 1. Cloner le repo
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

# 2. Copier le fichier .env
make copy-env

# 3. Démarrer l'environnement Docker
make up

# 4. Générer la clé Laravel, migrer et remplir la base
make key
make migrate
make seed

# Accès :
# API         → http://localhost:8000
# Swagger     → http://localhost:8000/api/documentation
# PhpMyAdmin  → http://localhost:8080 (gpvalise_user / secret)
```
