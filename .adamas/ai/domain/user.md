# 🧠 User Domain — GP-Valise

---

# 🎯 Objectif

Le User est l'acteur central du système GP-Valise.

Il représente :

- un participant à la marketplace (expéditeur ou voyageur)
- un sujet d'autorisation (rôle, KYC, vérification)
- un bénéficiaire de transactions financières

Il garantit la cohérence entre :

```txt
User ↔ Role ↔ KycRequest ↔ Trip / Booking / Transaction
```

---

# 🧠 Principe fondamental

> Le User est la source de vérité pour l'identité et l'autorisation.

Le User :

- porte le rôle métier
- conditionne l'accès aux fonctionnalités sensibles
- est requis pour toute opération financière

---

# 🧱 Structure

## Champs principaux

| Champ               | Type      | Description                        |
| ------------------- | --------- | ---------------------------------- |
| `first_name`        | string    | Prénom                             |
| `last_name`         | string    | Nom                                |
| `email`             | string    | Email unique                       |
| `phone`             | string?   | Téléphone (nullable)               |
| `country`           | string?   | Pays — utilisé pour le routing PSP |
| `role`              | enum      | Rôle métier (UserRoleEnum)         |
| `verified_user`     | boolean   | Utilisateur vérifié manuellement   |
| `email_verified_at` | datetime? | Vérification email                 |
| `phone_verified_at` | datetime? | Vérification téléphone             |
| `kyc_passed_at`     | datetime? | KYC validé — source de vérité KYC  |
| `plan_id`           | fk?       | Plan actif                         |
| `plan_expires_at`   | datetime? | Expiration du plan                 |

---

# 👥 Rôles

## Enum `UserRoleEnum`

| Rôle          | Description                    |
| ------------- | ------------------------------ |
| `SENDER`      | Expéditeur — crée des bookings |
| `TRAVELER`    | Voyageur — crée des trips      |
| `ADMIN`       | Administrateur — accès complet |
| `SUPER_ADMIN` | Super admin — accès étendu     |
| `MODERATOR`   | Modérateur — accès limité      |

## Règles

- `ADMIN` et `SUPER_ADMIN` **non disponibles à l'inscription publique**
- Un utilisateur ne peut pas changer de rôle lui-même
- Le rôle conditionne les routes accessibles via `EnsureRole` middleware

---

# 🔐 Niveaux de vérification

```txt
1. Email vérifié     → email_verified_at
2. Téléphone vérifié → phone_verified_at
3. User vérifié      → verified_user = true
4. KYC validé        → kyc_passed_at
```

Chaque niveau conditionne des accès différents :

| Niveau          | Requis pour                          |
| --------------- | ------------------------------------ |
| `verified_user` | Créer des transactions               |
| `kyc_passed_at` | Paiements sensibles (middleware kyc) |

---

# 🪪 KYC Lifecycle

## Vue d'ensemble

```txt
User soumet KycRequest
    ↓
PENDING
    ↓
Admin examine (photos ID + colis)
    ↓
APPROVED → kyc_passed_at = now()
REJECTED → kyc_passed_at = null + rejection_reason
```

## Table `kyc_requests`

| Champ               | Type      | Description                                                              |
| ------------------- | --------- | ------------------------------------------------------------------------ |
| `user_id`           | fk        | Historique complet — un seul PENDING par user (index partiel PostgreSQL) |
| `status`            | enum      | KycStatusEnum                                                            |
| `id_photo_path`     | string    | Chemin photo pièce d'identité                                            |
| `parcel_photo_path` | string    | Chemin photo du colis                                                    |
| `admin_notes`       | text?     | Notes de l'admin                                                         |
| `rejection_reason`  | text?     | Raison du rejet                                                          |
| `reviewed_by`       | fk?       | Admin qui a traité la demande                                            |
| `submitted_at`      | datetime  | Date de soumission                                                       |
| `reviewed_at`       | datetime? | Date de traitement                                                       |

## Statuts KYC

| Statut     | Description                         |
| ---------- | ----------------------------------- |
| `PENDING`  | En attente d'examen admin           |
| `APPROVED` | Validé — kyc_passed_at renseigné    |
| `REJECTED` | Rejeté — kyc_passed_at remis à null |

## Invariants KYC

```txt
Un seul KycRequest PENDING par user (index partiel PostgreSQL)
APPROVED → kyc_passed_at IS NOT NULL
REJECTED → kyc_passed_at IS NULL
Une demande REJECTED peut être resoumise (remplace l'ancienne)
Une demande PENDING ne peut pas être resoumise
Une demande APPROVED ne peut pas être resoumise
```

## Actions

| Action              | Acteur            | Description                      |
| ------------------- | ----------------- | -------------------------------- |
| `SubmitKycRequest`  | SENDER / TRAVELER | Soumet photos ID + colis         |
| `ApproveKycRequest` | ADMIN             | Approuve → kyc_passed_at = now() |
| `RejectKycRequest`  | ADMIN             | Rejette + raison obligatoire     |

---

# 🔒 Autorisation

## Policy `KycRequestPolicy`

| Action    | Acteur autorisé       |
| --------- | --------------------- |
| `viewAny` | ADMIN uniquement      |
| `view`    | ADMIN ou propriétaire |
| `create`  | SENDER ou TRAVELER    |
| `approve` | ADMIN + KYC PENDING   |
| `reject`  | ADMIN + KYC PENDING   |

## Middleware `kyc`

Bloque l'accès aux routes financières sensibles si `kyc_passed_at IS NULL`.

Routes protégées :

```php
Route::middleware(['verified_user', 'kyc', 'throttle.sensitive:finance,5,1'])
```

---

# 🔁 Relations principales

| Relation         | Description                  |
| ---------------- | ---------------------------- |
| `trips()`        | Trajets créés (TRAVELER)     |
| `bookings()`     | Réservations créées (SENDER) |
| `transactions()` | Transactions financières     |
| `luggages()`     | Valises créées               |
| `plan()`         | Plan actif                   |
| `kycRequest()`   | Demande KYC active           |

---

# 🏦 Rôle financier

Le `country` du User conditionne le routing PSP :

```txt
country = SN → PayDunya (XOF)
country = MA → Stripe (EUR)
country = FR → Stripe (EUR)
default      → FakeProvider
```

Le `kyc_passed_at` conditionne l'accès aux paiements réels.

---

# 🧪 Testabilité

Le domaine doit être testé pour :

- création avec rôles autorisés / refusés
- KYC submit / approve / reject
- invariants KYC (unique, statuts, remplacements)
- middleware kyc bloque sans kyc_passed_at
- routing PSP par country

---

# ⚠️ Anti-patterns interdits

- inscription avec rôle ADMIN / SUPER_ADMIN
- kyc_passed_at modifié hors ApproveKycRequest / RejectKycRequest
- accès financier sans kyc_passed_at
- double KycRequest PENDING pour le même user

---

# 🔮 Extensions futures

- vérification email automatique (lien)
- vérification téléphone OTP
- KYC automatisé (Sumsub, Onfido)
- scoring de confiance utilisateur
- suspension / bannissement
- notifications KYC approuvé / rejeté

---

# 🧠 Résumé exécutif

```txt
User = acteur central de la marketplace

- porte le rôle métier
- conditionne l'autorisation
- KYC = clé d'accès aux paiements réels
- country = clé du routing PSP
- évolue vers vérification automatisée
```
