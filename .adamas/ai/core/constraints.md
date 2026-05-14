# 🚫 AI Constraints — GP-Valise

> Limites strictes que l'IA doit respecter lors de toute intervention sur GP-Valise.

---

## 📚 Ordre de lecture obligatoire

Avant toute analyse, l'IA charge dans l'ordre :

```
1. .adamas/README.md
2. .adamas/ai/core/system-prompt.md
3. .adamas/ai/core/constraints.md
```

Puis les modules selon le contexte :

### 🧠 Domain — logique métier

| Fichier                                      | Sujet                                    |
| -------------------------------------------- | ---------------------------------------- |
| `.adamas/domain/booking.md`                  | Cycle de vie, transitions, escrow guards |
| `.adamas/domain/dispute-strategy.md`         | Workflow arbitrage, acteurs, résolution  |
| `.adamas/domain/escrow-lifecycle.md`         | Escrow 48h, release, blocage dispute     |
| `.adamas/domain/ledger-strategy.md`          | Double-entry, comptes, flows             |
| `.adamas/domain/psp-routing/Architecture.md` | Architecture PSP, DTOs                   |
| `.adamas/domain/psp-routing/Invariants.md`   | Invariants PSP et webhook                |
| `.adamas/domain/psp-routing/Routing.md`      | Routing multi-PSP                        |
| `.adamas/domain/psp-routing/payment.md`      | Logique financière complète              |

### ⚙️ Engineering

| Fichier                                  | Sujet                       |
| ---------------------------------------- | --------------------------- |
| `.adamas/ai/domain/architecture.md`      | Responsabilités des couches |
| `.adamas/ai/engineering/method-rules.md` | Où placer chaque méthode    |
| `.adamas/ai/engineering/standards.md`    | Standards de code           |
| `.adamas/ai/engineering/git-workflow.md` | Workflow Git                |

### 🛡️ Security

| Fichier                                   | Sujet                        |
| ----------------------------------------- | ---------------------------- |
| `.adamas/ai/security/access-control.md`   | Contrôle d'accès, rôles      |
| `.adamas/ai/security/financial-rules.md`  | Règles financières critiques |
| `.adamas/ai/security/webhook-security.md` | Sécurité webhooks            |

### 🔍 Observability

| Fichier                                      | Sujet                    |
| -------------------------------------------- | ------------------------ |
| `.adamas/ai/observability/correlation-id.md` | Traçabilité bout en bout |
| `.adamas/ai/observability/logging.md`        | Stratégie de logging     |
| `.adamas/ai/observability/monitoring.md`     | Monitoring et alerting   |

### 🧠 Governance

| Fichier                                 | Sujet                  |
| --------------------------------------- | ---------------------- |
| `.adamas/ai/governance/methodology.md`  | Méthodologie d'audit   |
| `.adamas/ai/governance/checklist.md`    | Checklist review/merge |
| `.adamas/ai/governance/decision-log.md` | Décisions documentées  |

---

## 🚫 Interdits absolus

```
❌ Générer du code sans audit préalable
❌ Valider un code parce qu'il fonctionne (sans vérifier les invariants)
❌ Ignorer les règles .adamas
❌ Justifier artificiellement du code incorrect
❌ Proposer un refactor massif non demandé
❌ Modifier plusieurs couches sans justification forte
❌ Créer une abstraction inutile
❌ Ajouter une dépendance non demandée
❌ Inventer des classes, méthodes ou fichiers absents
❌ Contourner les Enums (statuts en string)
❌ Logger des données sensibles
❌ Proposer une architecture incompatible avec la roadmap Phase 7+
❌ Utiliser float/decimal pour money ou poids
❌ Appeler un PSP concret hors infrastructure adapter
❌ Créer une balance matérialisée sur ledger_accounts
❌ Écrire dans le ledger hors DB::transaction()
❌ Déclencher un payout immédiat à LIVREE (bypass escrow)
```

---

## 🧱 Contraintes d'architecture

| Couche                  | Responsabilité unique    | Interdit             |
| ----------------------- | ------------------------ | -------------------- |
| Controller              | Orchestration HTTP       | Logique métier       |
| Action                  | Use case métier complet  | `request()`, `Auth`  |
| Policy                  | Autorisation             | Décision métier      |
| FormRequest             | Validation HTTP simple   | Validation domaine   |
| Enum                    | Source de vérité statuts | Accès DB             |
| Model                   | Données + helpers locaux | Orchestration        |
| Service                 | Logique transverse       | Remplacer une Action |
| WebhookProcessor        | Verify + normalize       | Logique métier       |
| PaymentProviderResolver | Routing PSP runtime      | Hardcoding           |
| LedgerWriter            | Écritures double-entry   | Calcul financier     |

---

## 🔧 Scope des corrections

Toute correction doit être :

- **ciblée** — un problème principal
- **atomique** — une couche à la fois
- **testée** — test obligatoire
- **documentée** — decision-log si impactant

Si la correction est large → **découper en étapes**.

---

## 🚨 Priorisation

| Priorité | Type                                      |
| -------- | ----------------------------------------- |
| 🔴 1     | Bugs métier / sécurité / finance / escrow |
| 🟠 2     | Mauvaise architecture                     |
| 🟠 3     | Dette technique bloquante                 |
| 🟡 4     | Tests manquants                           |
| 🟡 5     | Lisibilité                                |

---

## 💳 Contraintes financières critiques

```
Transaction = source de vérité financière
Ledger      = vérité comptable double-entry

CHARGE obligatoire avant confirmation
PAYOUT ⊕ REFUND (mutuellement exclusifs)
Pas de double : charge / payout / refund / fee
Montants persistés — jamais recalculés
FEE ≠ PAYMENT_FEE
PAYOUT + FEE + REFUND ≤ CHARGE
SUM(debits) = SUM(credits) par transaction ledger
LIVREE ≠ payout immédiat (escrow 48h)
```

---

## 🤖 Comportement attendu de l'IA

```
1. Définir le périmètre
2. Distinguer hypothèse vs fait
3. Expliquer l'impact métier
4. Proposer un plan avant tout code
5. Attendre validation
```

> L'IA doit réduire la complexité, pas la déplacer.
