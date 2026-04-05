# ✈️ GP-Valise API — Backend Marketplace Logistique (Laravel)

Backend API Laravel pour une plateforme logistique entre voyageurs et expéditeurs.

GP-Valise modélise un cas réel de marketplace logistique : un voyageur publie un trajet avec une capacité disponible, un expéditeur réserve des kg pour faire transporter un bagage ou un colis, puis le système orchestre réservation, paiement, expiration, livraison et flux financiers.

Le projet a été conçu comme une API métier robuste, avec un focus sur :

- cohérence métier
- concurrence
- idempotence
- séparation claire des responsabilités
- préparation progressive à un modèle escrow / marketplace

![CI](https://github.com/kasse222/gp-valise-api/actions/workflows/ci.yml/badge.svg)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/containerized-Docker-blue)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/tests-149%20passing-brightgreen)](#tests)
[![Made by Lamine Kasse](https://img.shields.io/badge/made%20by-Lamine%20Kasse-%23ff69b4)](mailto:laminekasse.dev@gmail.com)

> Documentation Swagger locale : `http://localhost:8000/api/documentation`

---

## Sommaire

- [Vision produit](#vision-produit)
- [Stack technique](#stack-technique)
- [Architecture](#architecture)
- [Modules principaux](#modules-principaux)
- [Booking lifecycle](#booking-lifecycle)
- [Transactions, payout et refund](#transactions-payout-et-refund)
- [Concurrence et idempotence](#concurrence-et-idempotence)
- [Sécurité](#sécurité)
- [Tests](#tests)
- [Installation locale](#installation-locale)
- [Roadmap](#roadmap)
- [Auteur](#auteur)

---

## Vision produit

GP-Valise est une API backend pour une plateforme logistique de confiance entre :

- **voyageur** : publie un trajet avec une capacité disponible
- **expéditeur** : réserve de la capacité pour faire transporter un bagage ou un colis
- **admin** : supervise les flux, les litiges et les opérations sensibles

L’objectif n’est pas seulement de créer des réservations, mais de garantir un système cohérent sur trois axes :

- **logistique** : trajets, capacité, étapes, bagages, livraison
- **confiance** : rôles, vérification, KYC, signalements, litiges
- **finance** : charge, payout, refund, traçabilité transactionnelle

---

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
```

### Répartition des responsabilités

- **Controller** : orchestration HTTP uniquement
- **Action** : logique métier d’un use case
- **Policy** : autorisation / contrôle d’accès
- **FormRequest** : validation d’entrée
- **Enum** : états, transitions, comportements métier purs
- **Validator** : règles métier réutilisables
- **Service** : orchestration transverse rare

### Principes appliqués

- pas de logique métier dans les controllers
- pas de logique métier dans les policies
- pas d’accès base de données dans les enums
- centralisation progressive des règles dans les modèles et actions

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
LIVREE / TERMINE → EN_LITIGE → REMBOURSEE
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

## Transactions, payout et refund

La couche financière distingue clairement :

- **Payment** : vue métier du cycle de paiement
- **Transaction** : mouvement financier unitaire et traçable

### Types de transactions pris en charge

- `CHARGE`
- `PAYOUT`
- `REFUND`
- `FEE`

### Payout

Après livraison :

- un payout peut être préparé seulement si une charge complétée existe
- le système empêche les doubles payouts
- une commission plateforme peut être générée

### Refund v1

Le remboursement actuellement implémenté suit des règles strictes :

- refund **manuel**
- refund **total**
- refund **unique**
- refund **autorisé seulement en litige**
- refund **bloqué si un payout existe**
- refund basé sur **le montant**
- transition métier cohérente vers `REMBOURSEE`

Ce choix permet de garder un flow simple, sûr et testable, avant d’introduire plus tard :

- refund partiel
- plusieurs refunds
- compensation après payout
- arbitrage avancé

---

## Concurrence et idempotence

Le projet traite explicitement les problèmes de concurrence et de retry.

### Ce qui est géré

- surbooking évité via verrouillage pessimiste
- double réservation de bagage évitée
- expiration batch rejouable sans side effects multiples
- refund sécurisé contre les doubles exécutions concurrentes

### Techniques utilisées

- `DB::transaction(...)`
- `lockForUpdate()`
- guards métier idempotents
- recalcul et relecture sous transaction

### Exemple de logique robuste

- verrou sur le trip pour protéger la capacité
- verrou sur les bagages pour éviter une réservation concurrente
- verrou sur les transactions d’un booking pour éviter un double refund

---

## Sécurité

Le projet applique plusieurs couches de sécurité :

- `auth:sanctum`
- `Policies` par ressource
- middleware de rôle
- `verified_user`
- `kyc`
- `throttle.sensitive`

Le endpoint refund est notamment protégé par :

- utilisateur vérifié
- KYC
- throttling
- policy dédiée
- règles métier dans l’action

---

## Tests

Le projet dispose actuellement d’une suite de tests verte :

- **149 tests**
- **406 assertions**
- durée locale observée : **~6 secondes**

### Couverture fonctionnelle

Les tests couvrent notamment :

- authentification
- CRUD des modules principaux
- booking lifecycle
- expiration batch
- concurrence métier
- payout
- refund manuel en litige
- contrôle d’accès
- throttling des opérations sensibles

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
- PhpMyAdmin : `http://localhost:8080`

### Tests

```bash
make test
```

---

## Roadmap

### Court terme

- enrichir le README et la documentation d’architecture
- finaliser l’alignement de certains modules partiellement refactorés
- améliorer l’observabilité et les logs métier
- renforcer encore les tests edge cases

### Moyen terme

- refund partiel
- plusieurs refunds
- compensation après payout
- intégration PSP réel (Stripe / webhook)
- durcissement Docker prod / CI/CD / monitoring

---

## Auteur

Projet développé par **Lamine Kasse** dans le cadre d’une montée en compétence avancée en :

- Laravel
- architecture backend
- API design
- logique métier
- Docker / CI
- préparation à des systèmes SaaS transactionnels

- GitHub : `kasse222`
- Email : `laminekasse.dev@gmail.com`
