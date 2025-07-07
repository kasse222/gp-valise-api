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

## 🧪 Tests automatisés (PestPHP)

-   **Environnement :** SQLite en mémoire (CI), MySQL local optionnel
-   **Statut actuel :**

    -   **109 tests réussis / 269 assertions**
    -   Temps total : **\~0.88s**

---

## 📌 Modules validés – v0.3 stable (100 % ✅)

| Module              | Couverture fonctionnelle                      | Cas spécifiques testés                                     |
| ------------------- | --------------------------------------------- | ---------------------------------------------------------- |
| 🔐 **Auth**         | Register, login, logout, /me                  | Accès non authentifié, validation inputs                   |
| 👤 **User**         | CRUD profil, sécurité                         | Vérification email/téléphone, changement mdp, accès profil |
| 📦 **Booking**      | CRUD, transitions (confirm, cancel, complete) | Transitions invalides, historique des statuts              |
| 🎒 **Luggage**      | CRUD complet                                  | Politiques sécurité (403), accès par utilisateur           |
| ✈️ **Trip**         | CRUD complet                                  | Autorisation d'accès, validations métier                   |
| 💼 **Plan**         | CRUD Admin-only                               | Accès restreint, plans actifs                              |
| 📢 **Report**       | CRUD propre utilisateur                       | Sécurisation accès (403), validation raison                |
| 💸 **Payment**      | CRUD utilisateur connecté                     | Rejets accès interdits (403)                               |
| 💰 **Transaction**  | CRUD sécurisé                                 | Validations cohérence, lien user/booking                   |
| 🧠 **Enums Métier** | Centralisés avec logique métier               | Transitions, états finaux, labels, couleurs                |

---

## 🔥 Sécurité & validation (OWASP & métier)

-   Tests poussés sur autorisations (403 Forbidden).
-   Validation métier précise des cas limites (422 Unprocessable Entity).

---

## 🎯 Prochaines étapes identifiées – (v0.4)

### 🔖 1. **Invitation** (`Invitation`)

-   Gestion cycle de vie : création, acceptation, token usage unique/expiration.

### 🔖 2. **Location** (`Location`)

-   CRUD sécurisé, accès restreint, contrôles spécifiques.

### 🔖 3. **Tests d'intégration avancés**

-   Pagination, filtres, optimisation SQL (`withCount`, eager loading).

### 🔖 4. **Tests OWASP sécurité avancés**

-   Injection SQL/XSS, rate limiting, endpoints protégés.

---

## 💻 Commande de lancement des tests (pour rappel rapide) :

```bash
./vendor/bin/pest
```

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
```
