# ✈️ GP Valise API

**API RESTful sécurisée développée en Laravel 12** pour connecter des **voyageurs** (porteurs de valises) et des **expéditeurs** (personnes envoyant des valises ou colis d’un point A à un point B).

---

## 🚀 Stack technique

-   **Laravel 12**
-   **Sanctum** – Authentification via tokens
-   **PestPHP** – Framework de tests modernes
-   **MySQL** – Base de données relationnelle
-   **Docker** – En cours d’intégration (dev/test/prod)
-   **CI/CD GitHub Actions** – Déploiement automatisé à venir

---

## 📦 Modèles implémentés

| Modèle     | Description                                                              |
| ---------- | ------------------------------------------------------------------------ |
| `User`     | Utilisateur avec rôle : `voyageur`, `expediteur`, `admin`                |
| `Trip`     | Trajet proposé par un voyageur (lieux, capacité, date, n° vol)           |
| `Luggage`  | Valise, colis ou document à envoyer par un expéditeur                    |
| `Booking`  | Réservation d’un trajet pour un bagage spécifique                        |
| `Payment`  | Paiement associé à une réservation                                       |
| `Report`   | Signalement sur un utilisateur, un trajet ou une réservation (morphable) |
| `Location` | Coordonnées GPS d’un trajet pour le suivi en temps réel                  |

---

## 🔐 Authentification

-   Utilise Laravel **Sanctum**
-   Endpoints prévus :
    -   `POST /api/register`
    -   `POST /api/login`
    -   `GET /api/me`
    -   `POST /api/logout`

---

## 🧪 Tests automatisés

-   **PestPHP** en cours d’implémentation
-   Tests à venir sur :
    -   Authentification
    -   Réservations & paiements
    -   Contrôle d’accès

---

## 🧱 Sécurité & Accès

-   Middleware `auth:sanctum` pour routes protégées
-   Prévisions :
    -   Contrôle par rôles (admin, voyageur, expéditeur)
    -   Limiteurs de requêtes (rate limiting)
    -   Validation KYC & gestion utilisateurs vérifiés

---

## 📊 Données de test (via seeders)

-   15 utilisateurs générés (`5 voyageurs`, `5 expéditeurs`, `5 admins`)
-   30 trajets (`trips`) associés à des voyageurs
-   40 bagages (`luggages`) associés à des expéditeurs
-   20 réservations (`bookings`) + paiements associés
-   10 signalements (`reports`) randomisés
-   150 positions GPS (`locations`) liées aux trajets

---

## 🛠️ Roadmap fonctionnelle

-   [x] Authentification avec Sanctum
-   [x] Relations Eloquent réalistes (factory + seeders)
-   [ ] CRUD REST sécurisé (`Trip`, `Booking`, `Luggage`, etc.)
-   [ ] Dockerisation (MySQL + NGINX + PHP-FPM)
-   [ ] Intégration continue avec GitHub Actions
-   [ ] Documentation Swagger `/api/documentation`
-   [ ] Sécurité : Policies, Middlewares, vérif rôle + OWASP checklist
-   [ ] Support du **partage d’espace** & **objets sensibles**
-   [ ] Tracking temps réel des `trips` (via `locations`)

---

## 🔗 Liens utiles

-   GitHub : [https://github.com/kasse222/gp-valise-api](https://github.com/kasse222/gp-valise-api)
-   Swagger (à venir) : `/api/documentation`

---

## 👨‍💻 À propos

Projet développé dans le cadre d'une reconversion professionnelle vers le back-end et DevOps.  
Contributeur principal : **Kasse Lamine**  
📧 Contact : `laminekasse.dev@gmail.com`

---
