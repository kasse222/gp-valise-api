# 🧠 .adamas — GP-Valise Architecture System

## 🎯 Objectif

`.adamas` est le système de gouvernance technique du projet **GP-Valise**.

Il centralise :

- les règles métier
- les règles d’architecture
- les standards de code
- les décisions techniques
- les principes de sécurité
- les pratiques d’observabilité

> Ce dossier transforme le projet en système structuré, auditable et scalable.

---

## 🚀 Projet : GP-Valise

Backend SaaS logistique permettant :

- à un expéditeur d’envoyer un objet
- via un voyageur tiers
- avec un système sécurisé (paiement, tracking, litige)

Stack :

- Laravel 12
- MySQL / PostgreSQL (migration en cours)
- Redis (queues + monitoring)
- Pest (tests)
- Docker

Architecture :

- Action-driven
- Domain-driven (light)
- Enums comme source de vérité métier
- Async (webhooks + queues)

---

## 🧠 Philosophie

```txt
Clarté > magie
Règles explicites > implicites
Traçabilité > simplicité naïve
Sécurité > rapidité
```

---

## 🧱 Structure

```txt
.adamas/
├── ai/
│   ├── core/            → règles IA
│   ├── domain/          → logique métier
│   ├── engineering/     → règles code / architecture
│   ├── governance/      → audit / décisions
│   ├── observability/   → logs / monitoring / tracing
│   └── security/        → règles critiques (finance, accès, webhook)
```

---

## 📚 Modules

### 🧠 Domain

- `business-logic.md`
- `booking.md`
- `transaction.md`

👉 définit la logique métier pure

---

### ⚙️ Engineering

- `architecture.md`
- `method-rules.md`
- `standards.md`
- `git-workflow.md`

👉 définit comment coder correctement

---

### 🛡️ Security

- `access-control.md`
- `financial-rules.md`
- `webhook-security.md`

👉 protège le système contre :

- fraude
- erreurs financières
- accès non autorisé

---

### 🔍 Observability

- `correlation-id.md`
- `logging.md`
- `monitoring.md`

👉 permet :

- debug production
- traçabilité
- investigation incidents

---

### 🧠 Governance

- `audit.md`
- `decision-log.md`
- `checklist.md`

👉 impose :

- audit avant code
- décisions documentées
- qualité constante

---

## 🔒 Règles critiques

### 1. Finance

```txt
Transaction = source de vérité
```

- pas de double payout
- pas de refund après payout
- montants persistés

---

### 2. Sécurité

```txt
DENY BY DEFAULT
```

- Policy obligatoire
- Action valide les règles métier

---

### 3. Webhooks

```txt
NEVER TRUST EXTERNAL INPUT
```

- signature HMAC
- idempotence
- lock DB

---

### 4. Observabilité

```txt
correlation_id = traçabilité totale
```

- logs
- jobs
- DB (en cours)

---

## 🧪 Qualité

Le projet impose :

- tests Pest complets
- idempotence
- gestion concurrence (`lockForUpdate`)
- audit des actions critiques
- monitoring des queues et webhooks

---

## 📈 Niveau technique visé

Ce projet vise un niveau :

```txt
Backend SaaS production-ready
→ Fintech-ready
```

Car il inclut :

- système transactionnel robuste
- gestion async (webhooks)
- audit log immuable
- observabilité complète
- sécurité stricte

---

## 🎯 Objectif long terme

Évolution vers :

- escrow avancé
- ledger interne
- multi-accounts (multi-pays)
- dispute system
- intégration PSP réelle (Stripe, Wave, CMI)

---

## 🧠 Utilisation avec IA

Toute interaction IA doit respecter :

1. Audit avant code
2. Respect `.adamas`
3. Justification des choix
4. Pas de génération aveugle

---

## 🚫 Interdits

- logique métier hors Action
- accès DB dans Enum
- bypass Policy
- calcul financier non centralisé
- code sans test
- modification sans audit

---

## 🧠 Principe clé

> `.adamas` n’est pas de la documentation.
> C’est le **système de contrôle du projet**.

---

## 🔥 Pourquoi c’est important (recruteur)

Ce dossier montre que le projet n’est pas :

```txt
un CRUD Laravel
```

mais :

```txt
un système pensé comme un produit SaaS réel
```

---

## 📌 Résumé

```txt
Code + Règles + Audit + Observabilité = Système fiable
```

---

## ⚙️ Execution Rules

Les principes définis dans `.adamas` doivent être appliqués de manière concrète.

---

### Webhooks

NEVER TRUST EXTERNAL INPUT implique :

- vérification signature obligatoire (HMAC / provider)
- rejet immédiat si signature invalide
- stockage `event_id` avec contrainte UNIQUE
- idempotence stricte (1 event → 1 effet)
- traitement dans transaction DB avec lock

---

### Transactions financières

Transaction = source de vérité implique :

- aucune modification directe de balance
- toute mutation passe par Transaction
- audit obligatoire pour actions critiques
- aucun calcul financier inline dans Controller

---

### Sécurité

DENY BY DEFAULT implique :

- toute route protégée par Policy
- aucune action sans validation métier
- aucun fallback implicite permissif

---

### Observabilité

correlation_id implique :

- généré à chaque requête
- propagé dans :
    - logs
    - jobs
    - webhooks
- utilisé pour debug cross-system

## 🔁 External Systems Rule

Tout système externe (PSP, webhook, API) est considéré comme :

- non fiable
- duplicable
- lent
- incohérent

Le système interne doit rester cohérent même si :

- un webhook est envoyé 3 fois
- un provider répond partiellement
- une requête timeout
