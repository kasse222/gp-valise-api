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
-   **Swagger (l5-swagger)** – Documentation de l’API

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
| `Location`    | Coordonnées GPS d’un trajet pour le suivi en temps réel                  |

---

## 🔐 Authentification

-   Utilise Laravel **Sanctum**
-   Endpoints actuels :
    -   `POST /api/v1/register`
    -   `POST /api/v1/login`
    -   `GET  /api/v1/me`
    -   `POST /api/v1/logout`

---

## 📦 Réservations (Bookings)

-   Endpoints REST :

    -   `GET /api/v1/bookings` : liste les réservations du voyageur connecté
    -   `POST /api/v1/bookings` : créer une réservation (expéditeur)
    -   `GET /api/v1/bookings/{id}` : voir le détail d’une réservation
    -   `PUT /api/v1/bookings/{id}` : mise à jour (statut)
    -   `DELETE /api/v1/bookings/{id}` : suppression

-   Actions métier :

    -   `POST /api/v1/bookings/{id}/confirm` : confirmation par le voyageur
    -   `POST /api/v1/bookings/{id}/cancel` : annulation par voyageur ou système
    -   `POST /api/v1/bookings/{id}/complete` : marquer comme terminée

-   Architecture métier découplée via :
    -   `App\Actions\Booking\ReserveBooking`
    -   `App\Actions\Booking\ConfirmBooking`
    -   `App\Actions\Booking\CancelBooking`
    -   `App\Actions\Booking\CompleteBooking`

---

## 🧪 Tests automatisés

\*PestPHP (full TDD sur bookings/trips/luggages)

\*Couverture :

-Authentification & accès

-éservations : création, mise à jour statuts, suppression, annulation, confirmation

-Cas limites (bagage déjà réservé, rôle, accès interdit…)

---

## 🧱 Sécurité & Accès

-   Middleware `auth:sanctum` sur toutes les routes sensibles
-   Règles métier :
    -   Un Booking appartient toujours à l’utilisateur qui le crée
    -   Seul l’expéditeur peut réserver, seul le voyageur du trip peut confirmer/refuser
    -   Statut modifiable selon le rôle, validation statuts via enum
    -   Contrôle du poids/capacité avant réservation (à renforcer)
    -   Tentatives non autorisées → 403 Forbidden
-   Roadmap sécurité :
    -   Policies & Gates
    -   Contrôle par rôles (admin, premium…)
    -   Vérification d’identité (KYC simplifié)
    -   OWASP API checklist

---

## 📊 Données de test (seeders)

-   15 utilisateurs générés (`5 voyageurs`, `5 expéditeurs`, `5 admins`)
-   30 trajets (`trips`) associés à des voyageurs
-   40 bagages (`luggages`) associés à des expéditeurs
-   20 réservations (`bookings`) avec BookingItems et paiements simulés
-   10 signalements (`reports`) générés aléatoirement
-   150 coordonnées GPS (`locations`) pour suivi temps réel

---

## 🛠️ Roadmap fonctionnelle

| Tâche                                    | État        |
| ---------------------------------------- | ----------- |
| Authentification Sanctum                 | ✅ Terminé  |
| Booking CRUD + logique métier            | ✅ Terminé  |
| Trip CRUD complet                        | ✅ Terminé  |
| Documentation Swagger                    | ✅ En place |
| Dockerisation (Laravel + MySQL + NGINX)  | 🔄 En cours |
| CI/CD GitHub Actions                     | 🔄 En cours |
| Sécurité avancée (Policies, rôles, etc.) | 🔜 À venir  |
| Modules Luggage, Payment, Users complets | 🔜 À venir  |
| Backups, monitoring, alertes             | 🔜 À venir  |

---

## 🔗 Liens utiles

-   GitHub : [https://github.com/kasse222/gp-valise-api](https://github.com/kasse222/gp-valise-api)
-   Swagger : `/api/documentation` (généré via `l5-swagger`)

---

## 👨‍💻 À propos

Projet développé dans le cadre d'une reconversion professionnelle vers le back-end & DevOps.  
Contributeur principal : **Kasse Lamine**  
📧 Contact : `laminekasse.dev@gmail.com`

---
