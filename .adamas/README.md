# 🧠 .adamas — GP-Valise Architecture System

## 🎯 Objectif

`.adamas` est le système de gouvernance technique du projet **GP-Valise**.

Il centralise :

- les règles métier
- les règles d'architecture
- les standards de code
- les décisions techniques
- les principes de sécurité
- les pratiques d'observabilité

> Ce dossier transforme le projet en système structuré, auditable et scalable.

---

## 🚀 Projet : GP-Valise

Backend SaaS logistique permettant :

- à un expéditeur d'envoyer un objet
- via un voyageur tiers
- avec un système sécurisé (paiement, escrow, ledger, litige)

Stack :

- Laravel 12
- PHP 8.2
- PostgreSQL 16 Alpine
- Redis (queues + Horizon + monitoring)
- Pest (415 tests / 985 assertions)
- Docker + Docker Compose
- Filament 3.3 (admin panel)

Architecture :

- Action-driven
- Domain-driven (light)
- Enums comme source de vérité métier
- Async (webhooks + queues Horizon)
- Integer minor units (centimes, grammes)

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
└── domain/
    ├── booking.md
    ├── dispute-strategy.md
    ├── escrow-lifecycle.md
    ├── ledger-strategy.md
    └── psp-routing/
```

---

## 📚 Modules

### 🧠 Domain

- `booking.md` — cycle de vie booking, transitions, guards
- `dispute-strategy.md` — workflow dispute, acteurs, résolution
- `escrow-lifecycle.md` — escrow 48h, release, blocage dispute
- `ledger-strategy.md` — double-entry, flows, comptes EUR/XOF
- `transaction.md` — source de vérité financière

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
Ledger = vérité comptable double-entry
```

- pas de double payout
- pas de refund après payout COMPLETED
- montants persistés en integer minor units (centimes)
- `SUM(debits) = SUM(credits)` garanti à tout moment
- guards idempotence sur tous les flows ledger

---

### 2. Escrow

```txt
LIVREE ≠ payout immédiat
LIVREE = début période escrow (48h par défaut)
```

- `escrow_releasable_at = delivered_at + GPVALISE_ESCROW_DELAY_HOURS`
- `disputed_at !== null` → escrow bloqué indéfiniment
- scheduler hourly → `ReleaseEscrowBatch`
- payout jamais immédiat après livraison

---

### 3. Dispute

```txt
booking.status = EN_LITIGE    ← signal financier
dispute.status                ← workflow arbitrage
```

- une seule dispute active par booking (contrainte DB unique)
- RESOLVED est terminal — immuable
- résolution = décision financière (refund | payout)
- audit log obligatoire sur résolution

---

### 4. Sécurité

```txt
DENY BY DEFAULT
```

- Policy obligatoire sur toutes les routes
- Action valide les règles métier
- Admin uniquement pour actions financières critiques

---

### 5. Webhooks

```txt
NEVER TRUST EXTERNAL INPUT
```

- signature HMAC obligatoire
- idempotence via `event_id` unique
- normalisation avant dispatch (WebhookProcessor)
- lock DB sur traitement

---

### 6. Observabilité

```txt
correlation_id = traçabilité totale
```

- généré à chaque requête HTTP
- propagé dans logs, jobs, webhooks, audit_logs
- `X-Correlation-ID` header en réponse

---

## 🧪 Qualité

Le projet impose :

- tests Pest complets (415 tests / 985 assertions)
- idempotence sur toutes les actions financières
- gestion concurrence (`lockForUpdate` systématique)
- audit log immuable + chain hash (`AuditLogIntegrityService`)
- monitoring queues et webhooks
- guards idempotence ledger (`hasExistingEntries`)

---

## 📈 Roadmap phases

```txt
Phase 1 — MVP                         ✅
Phase 2 — PSP routing Kkiapay/Stripe  ✅
Phase 3 — platform_accounts + PostgreSQL + integer units  ✅
Phase 4 — Escrow 48h + OpenDispute    ✅
Phase 5 — Ledger double-entry         ✅
Phase 6 — Dispute system v2           ✅ (en cours merge)
  Filament Admin Dashboard            ✅
  DisputeResource Filament            ⏳
Phase 7 — API publique dispute        ⏳
  Notifications email/websocket       ⏳
  Upload pièces jointes S3            ⏳
  Multi-dispute historique            ⏳
```

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
- balance matérialisée sur ledger_accounts
- écriture ledger hors `DB::transaction()`
- modifier une `LedgerEntry` existante

---

## 🧠 Principe clé

> `.adamas` n'est pas de la documentation.
> C'est le **système de contrôle du projet**.

---

## ⚙️ Execution Rules

### Webhooks

- vérification signature obligatoire (HMAC / provider)
- rejet immédiat si signature invalide
- stockage `event_id` avec contrainte UNIQUE
- idempotence stricte (1 event → 1 effet)
- traitement dans transaction DB avec lock
- normalisation payload avant dispatch (WebhookProcessor)

---

### Transactions financières

- aucune modification directe de balance
- toute mutation passe par Transaction + LedgerEntry
- audit obligatoire pour actions critiques admin
- aucun calcul financier inline dans Controller
- `FEE` créée au moment du PAYOUT, pas à la CHARGE

---

### Ledger

- `writeCharge` → au moment PSP (webhook transaction.success)
- `writePayoutRelease` → au moment escrow release
- `writePayoutPaid` → au moment MarkPayoutCompleted
- `writeRefund` → au moment webhook refund.completed
- `writePaymentFee` → avec writePayoutRelease
- tous les flows sont idempotents

---

### Sécurité

- toute route protégée par Policy
- aucune action sans validation métier
- aucun fallback implicite permissif
- `FakePaymentProvider` interdit en production (double guard)

---

### Observabilité

- `correlation_id` généré à chaque requête
- propagé dans logs, jobs, webhooks, audit_logs
- utilisé pour debug cross-system
- `AuditLogIntegrityService` : seal() dans même DB::transaction

---

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
- un job échoue et est retenté

```
## Operational Philosophy

GP-Valise est conçu comme un système opérable.

Chaque flow critique doit être :
- observable ;
- rejouable ;
- auditable ;
- idempotent ;
- recoverable.

Les workflows critiques ne doivent jamais dépendre
d’un état implicite ou d’un provider externe.
---
```

## Pourquoi `.adamas` existe

Le projet GP-Valise dépasse le cadre d’un simple CRUD Laravel.

La multiplication des workflows :

- paiements async ;
- escrow ;
- ledger ;
- disputes ;
- auditabilité ;
- admin ops ;

nécessite une gouvernance explicite.

`.adamas` agit comme :

- source de vérité architecture ;
- système de contrôle ;
- garde-fou technique ;
- référentiel de décisions.
