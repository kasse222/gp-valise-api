# ✈️ GP-Valise API — Backend Marketplace Logistique (Laravel)

Backend API Laravel pour une plateforme logistique entre voyageurs et expéditeurs.

GP-Valise modélise un **cas réel de marketplace transactionnelle**, avec gestion complète :

- des réservations
- du cycle de vie métier
- des flux financiers (charge, payout, refund)
- des paiements asynchrones (inspirés Stripe)

> 🎯 Objectif : construire un backend **cohérent, robuste et prêt pour un environnement SaaS réel**

---

![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/containerized-Docker-blue)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/tests-150%2B%20passing-brightgreen)](#tests)

---

## 🚀 Vision produit

GP-Valise est une API backend pour une plateforme logistique de confiance entre :

- **Voyageur** : publie un trajet avec capacité disponible
- **Expéditeur** : réserve des kg pour transporter un colis
- **Admin** : supervise les flux et les litiges

Le système repose sur trois piliers :

- **Logistique** : trajets, étapes, capacité, livraison
- **Confiance** : rôles, KYC, signalements, litiges
- **Finance** : transactions, payout, refund, traçabilité

---

## 💸 Architecture Paiement (Async Ready)

Le système suit un modèle **asynchrone inspiré des PSP modernes (Stripe-like)**.

### Concepts clés

- `PaymentProvider` (abstraction)
- `Transaction` = source de vérité financière
- Webhooks pour la confirmation réelle

### Flow simplifié

````text
CHARGE → (provider) → COMPLETED / PENDING / FAILED

REFUND → PENDING
           ↓
      webhook
           ↓
COMPLETED → booking REMBOURSEE
FAILED    → booking reste EN_LITIGE


## Stack technique

- **Laravel 12** (API-only)
- **Sanctum** (authentification token)
- **PestPHP** (tests automatisés)
- **MySQL 8** (développement)
- **SQLite in-memory** (tests / CI)
- **Docker** (environnement local)
- **GitHub Actions** (CI)
- **Swagger / l5-swagger** (documentation API)
- **Enums métier** (source de vérité des statuts)
- **Actions** (cas d’usage métier)
- **Policies** (contrôle d’accès)
- **FormRequests** (validation HTTP)

---

## Architecture

Le projet suit une architecture orientée cas d’usage :

```text
Controller → Action → Model / Enum / Validator
````

### Répartition des responsabilités

- **Controller** : orchestration HTTP uniquement
- **Action** : logique métier d’un use case
- **Policy** : autorisation / contrôle d’accès
- **FormRequest** : validation d’entrée
- **Enum** : états, transitions, comportements métier purs
- **Validator** : règles métier réutilisables
- **Service** : orchestration transverse rare (intégration externe)

### Principes appliqués

- Principes appliqués
- séparation stricte des responsabilités
- logique métier centralisée
- idempotence
- cohérence transactionnelle

---

## Modules principaux

Le backend couvre aujourd’hui les modules suivants :

- **User**
- **Trip**
- **Booking**
- **BookingItem**
- **Luggage**
- **BookingStatusHistory**
- **Payment**
- **Transaction**
- **Location**
- **Report**
- **Plan**
- **Invitation**

---

## Booking lifecycle

Le cœur du projet repose sur un cycle de vie métier réaliste des réservations.

### Flow principal

```text
EN_ATTENTE → EN_PAIEMENT → CONFIRMEE
                     ↓
                  EXPIREE
```

Puis :

```text
CONFIRMEE → LIVREE → TERMINE
```

Et en cas de problème :

```text
LIVREE → EN_LITIGE → REMBOURSEE
```

### Règles clés

- une réservation n’est plus confirmable directement après création
- un booking passe en `EN_PAIEMENT`
- la capacité est bloquée temporairement
- si le paiement réussit et que les conditions métier sont réunies, le booking passe en `CONFIRMEE`
- si le délai de paiement expire, le booking passe en `EXPIREE`
- l’expiration libère les ressources réservées
- les transitions sensibles sont historisées

### Batch automatique

Le projet intègre une commande batch dédiée :

```bash
php artisan bookings:expire-pending
```

Elle permet de :

- scanner les bookings `EN_PAIEMENT` expirés
- appliquer la transition vers `EXPIREE`
- libérer les valises liées
- garantir un comportement idempotent et stable

---

## 💰 Transactions, payout et refund

- Types de transactions

*CHARGE
*PAYOUT
*REFUND
*FEE

- Refund (v1)

*refund manuel
*refund total
*refund unique
*uniquement en litige
\*bloqué si payout existant

- Async (important)

*REFUND = PENDING
*finalisation via webhook
\*idempotence garantie

👉 Ce design permet de simuler un système réel type Stripe.

## Concurrence et idempotence

Le projet traite

*surbooking
*double réservation
*double refund
*retry webhook

### Techniques utilisées

- `DB::transaction(...)`
- `lockForUpdate()`
- guards métier idempotents
- recalcul sous transaction

---

## Sécurité

- `auth:sanctum`
- `Policies` par ressource
- `verified_user`/`kyc`
- `throttling opérations sensibles`

## Tests

Le projet dispose actuellement d’une suite de tests verte :

- **153 tests**
- **406 assertions**
- durée locale observée : **~6 secondes**
- couverture métier complète

### Couverture fonctionnelle

Les tests couvrent notamment :

- booking lifecycle
- expiration batch
- concurrence métier
- refund async
- webhook (success / failed / idempotence)

---

## Installation locale

```bash
git clone https://github.com/kasse222/gp-valise-api.git
cd gp-valise-api

make copy-env
make up
make key
make migrate
make seed
```

### Interfaces locales

- API : `http://localhost:8000`
- Swagger : `http://localhost:8000/api/documentation`

## Roadmap

### Court terme

- sécurité webhook (signature)
- logs métier
- monitoring

### Moyen terme

- refund partiel
- payout async complet
- intégration Stripe

## Auteur

Backend Developer (Laravel)

- Laravel
- architecture backend
- API design
- logique métier
- Docker / CI
- préparation à des systèmes SaaS transactionnels

- GitHub : `kasse222`
- Email : `laminekasse.dev@gmail.com`

🎯 Objectif :

CDI Maroc 🇲🇦
Remote international 🌍

Spécialisé en :

API design

- systèmes transactionnels
- architecture backend
- Laravel / Docker / CI

📧 laminekasse.dev@gmail.com
