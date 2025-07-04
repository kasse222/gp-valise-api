# ✈️ GP-Valise API

![Tests](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)
[![Swagger](https://img.shields.io/badge/docs-swagger-blue.svg)](http://localhost:8000/api/documentation)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/containerized-Docker-blue)](https://www.docker.com/)
[![Made with ❤️ by Lamine Kasse](https://img.shields.io/badge/made%20by-Lamine%20Kasse-%23ff69b4)](mailto:laminekasse.dev@gmail.com)

---

## 🧭 Sommaire

-   [🚀 Stack technique](#-stack-technique)
-   [📦 Modèles implémentés](#-modèles-implémentés)
-   [🔐 Authentification](#-authentification)
-   [📦 Réservations & Valises](#-réservations--valises)
-   [🧪 Tests automatisés](#-tests-automatisés)
-   [🧱 Sécurité & Accès](#-sécurité--accès)
-   [🧬 Données de test (seeders)](#-données-de-test-seeders)
-   [⚙️ Installation locale (Docker)](#️-installation-locale-docker)
-   [🛠️ Roadmap fonctionnelle](#️-roadmap-fonctionnelle)
-   [👨‍💻 À propos](#-à-propos)

---

## 🚀 Stack technique

-   **Laravel 12** (API only)
-   **Sanctum** pour l’authentification
-   **PestPHP** pour les tests automatisés
-   **MySQL 8** – base de données relationnelle
-   **Docker** – dev/test/prod isolés
-   **Swagger (l5-swagger)** – documentation API interactive
-   **GitHub Actions** – CI/CD automatisé
-   **Enums métiers** – statuts, rôles, types, etc.
-   **Actions Laravel** – logique métier séparée
-   **Policies** – sécurité d’accès centralisée

---

## 📦 Modèles implémentés

| Modèle                 | Description principale                   |
| ---------------------- | ---------------------------------------- |
| `User`                 | Voyageur, Expéditeur, Admin              |
| `Trip`                 | Trajets proposés                         |
| `Booking`              | Réservation d’un ou plusieurs colis      |
| `BookingItem`          | Détail des valises réservées             |
| `Luggage`              | Valise à envoyer (statut, volume, poids) |
| `BookingStatusHistory` | Historique des statuts                   |
| `Payment`              | Paiements utilisateurs                   |
| `Transaction`          | Enregistrements financiers               |
| `Report`               | Signalements d’incidents                 |
| `Location`             | Coordonnées GPS                          |
| `Plan`                 | Abonnements freemium / premium           |
| `Invitation`           | Invitations ou liens parrainage          |

---

## 🔐 Authentification

| Méthode | Endpoint    | Description       |
| ------- | ----------- | ----------------- |
| POST    | `/register` | Inscription       |
| POST    | `/login`    | Connexion         |
| GET     | `/me`       | Infos utilisateur |
| POST    | `/logout`   | Déconnexion       |

-   Tokens via Sanctum
-   Sécurité : FormRequest + RoleMiddleware + Policy

---

## 📦 Réservations & Valises

### Booking – Endpoints

| Méthode | Route                     | Description              |
| ------- | ------------------------- | ------------------------ |
| GET     | `/bookings`               | Liste des réservations   |
| POST    | `/bookings`               | Créer une réservation    |
| PUT     | `/bookings/{id}`          | Modifier le statut       |
| DELETE  | `/bookings/{id}`          | Supprimer la réservation |
| POST    | `/bookings/{id}/confirm`  | Confirmer                |
| POST    | `/bookings/{id}/cancel`   | Annuler                  |
| POST    | `/bookings/{id}/complete` | Marquer comme livrée     |

-   Statuts via `BookingStatusEnum`
-   Transitions historisées
-   Autorisation via `BookingPolicy`

### Luggage – Endpoints

| Méthode | Route            | Description          |
| ------- | ---------------- | -------------------- |
| GET     | `/luggages`      | Liste de ses valises |
| POST    | `/luggages`      | Créer une valise     |
| PUT     | `/luggages/{id}` | Modifier une valise  |
| DELETE  | `/luggages/{id}` | Supprimer            |

-   Sécurité : `LuggagePolicy`
-   Validation : FormRequests
-   Enum : `LuggageStatusEnum`

---

## 🧪 Tests automatisés

-   **Framework** : PestPHP
-   **Environnements** : SQLite en mémoire (CI) + MySQL local (optionnel)

# ✅ Modules testés – v0.3

Ce document liste l’ensemble des modules de l’API GP-Valise testés automatiquement avec PestPHP.  
Statut : **100 % OK** – `91 tests passés / 227 assertions` – Temps total ~0.79s

---

## 🔐 Auth

-   `register`, `login`, `logout`, `/me`
-   🔒 Cas invalides : email existant, mot de passe incorrect, accès sans token

## 👤 User

-   Affichage & modification du profil
-   Vérification email & téléphone
-   Changement de mot de passe
-   Upgrade de plan via `PlanService`
-   Refus d’accès au profil d’un autre utilisateur

## 📦 Booking

-   CRUD complet (`index`, `store`, `show`, `update`, `destroy`)
-   Transitions métier :
    -   `confirm`, `cancel`, `complete`
-   Historique de statuts :
    -   Création + validations (rejet si transition non autorisée)
-   Booking Items :
    -   Création, update, suppression avec cohérence (booking/trip/luggage)

## 🎒 Luggage

-   CRUD : liste, création, mise à jour, suppression
-   Accès sécurisé (policies + tests 403)
-   Actions : `CreateLuggage`, `UpdateLuggage`

## ✈️ Trip

-   Liste des trajets, affichage
-   Création, modification, suppression avec tests d’autorisation

## 💼 Plan

-   Accès restreint aux plans actifs
-   Création, mise à jour, suppression (admin only)
-   Refus d’accès aux utilisateurs non admin

## 📢 Report

-   Liste des reports liés à l’utilisateur connecté
-   Création avec validation du `reason`
-   Accès à un report propre
-   🔒 Rejet 403 si accès à un report d’un autre utilisateur

## 🧠 Enums métier

-   `BookingStatusEnum`, `PaymentStatusEnum`, etc.
-   Logique métier centralisée :
    -   `canBeCancelled()`
    -   `canBeDelivered()`
    -   `canTransitionTo()`
    -   `isFinal()`
    -   `label()`, `color()`

## 🚫 Cas d’erreur & sécurité

-   Tentatives d’accès non autorisées (`403`)
-   Statuts invalides (`422`)
-   Réservations incohérentes (dates passées, kg dépassé, valise déjà réservée)
-   Rejet des actions interdites par règles métiers ou policies

---

✅ **Couverture totale validée pour la v0.3**  
📁 Test suite PestPHP  
🧪 Tous les tests situés dans `tests/Feature/` et `tests/Unit/`

---

### 🎯 À venir

-   💸 `Payment`, `Transaction`
-   👤 `User` (vérification email/téléphone, changement de mot de passe, upgrade de plan)
-   🚀 `Trip` (CRUD + logique métier, dates, capacité)
-   🧱 Tests d’intégration plus avancés (pagination, withCount, autorisations strictes)

---

👉 Lancer tous les tests :

````bash
./vendor/bin/pest


## 🧱 Sécurité & Accès

-   `auth:sanctum` obligatoire
-   Policies Laravel actives :
    -   `BookingPolicy`, `LuggagePolicy`, `PaymentPolicy`, etc.
-   Middleware personnalisés :
    -   `EnsureRole`, `EnsureKYC`, etc.
-   Enum = source de vérité métier
-   Historique `BookingStatusHistory` immutable

🔒 Prochaines étapes :

-   Spatie Laravel Permission
-   Checklist OWASP
-   Token JWT optionnel

---

## 🧬 Données de test (Seeders)

| Élément      | Quantité     |
| ------------ | ------------ |
| Users        | 15 (3 rôles) |
| Trips        | 30           |
| Bookings     | 20           |
| Luggages     | 40           |
| BookingItems | auto-générés |
| Payments     | 20           |
| Reports      | 10           |
| Locations    | 150          |
| Transactions | 10           |
| Invitations  | 5            |

---

👨‍💻 À propos
Projet open source développé avec ❤️ par Lamine Kasse dans le cadre d’une reconversion vers le back-end Laravel/DevOps/API.

GitHub → kasse222
Email → kasse.lamine.dev@cloud.com

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

# Interfaces disponibles :
# API         → http://localhost:8000
# Swagger     → http://localhost:8000/api/documentation
# PhpMyAdmin  → http://localhost:8080
````
