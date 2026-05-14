# 🔍 Audit Methodology — GP-Valise

> Aucune modification ne doit être faite sans audit préalable.
> Corriger sans comprendre = créer de la dette technique.

---

## 🎯 Objectif

L'audit doit répondre à :

> Ce code est-il **cohérent, sécurisé, testable** et aligné avec `.adamas` ?

---

## 🔁 Étapes obligatoires

### 1. Définition du périmètre

Préciser avant toute analyse :

| Élément          | Description                                             |
| ---------------- | ------------------------------------------------------- |
| Module           | Booking / Transaction / Escrow / Ledger / Dispute / PSP |
| Classe / fichier | Nom précis                                              |
| Use case métier  | Ce que le code est censé faire                          |
| Flux impacté     | HTTP / Webhook / Async / Scheduler                      |
| Niveau de risque | Finance / Sécurité / Concurrence / Métier               |

**Exemples :**

```
Périmètre : ReleaseEscrowBatch — use case escrow release.
Risque : finance + concurrence + idempotence.

Périmètre : ResolveDispute — use case résolution litige.
Risque : finance + audit + invariants.
```

---

### 2. Lecture des sources `.adamas`

**Obligatoires pour toute analyse :**

- `.adamas/ai/core/system-prompt.md`
- `.adamas/ai/core/constraints.md`
- `.adamas/ai/domain/architecture.md`
- `.adamas/ai/domain/overview.md`
- `.adamas/ai/engineering/standards.md`
- `.adamas/ai/governance/checklist.md`

**Selon le sujet :**

| Sujet            | Source                                                         |
| ---------------- | -------------------------------------------------------------- |
| Booking / Escrow | `.adamas/domain/booking.md`                                    |
| Dispute workflow | `.adamas/domain/dispute-strategy.md`                           |
| Escrow lifecycle | `.adamas/domain/escrow-lifecycle.md`                           |
| Ledger           | `.adamas/domain/ledger-strategy.md`                            |
| PSP / Webhook    | `.adamas/domain/psp-routing/Architecture.md` + `Invariants.md` |
| Finance          | `.adamas/ai/security/financial-rules.md`                       |
| Webhook sécurité | `.adamas/ai/security/webhook-security.md`                      |
| Accès / rôles    | `.adamas/ai/security/access-control.md`                        |
| Audit            | `.adamas/ai/governance/audit.md`                               |
| Observabilité    | `.adamas/ai/observability/*`                                   |
| Git              | `.adamas/ai/engineering/git-workflow.md`                       |

---

### 3. Analyse structurelle

Vérifier la séparation des responsabilités :

| Couche           | Responsabilité attendue                  |
| ---------------- | ---------------------------------------- |
| Controller       | Orchestration HTTP uniquement            |
| FormRequest      | Validation HTTP simple                   |
| Policy           | Autorisation uniquement                  |
| Action           | Use case métier complet                  |
| Service          | Logique transverse                       |
| Enum             | Source de vérité des statuts/transitions |
| Model            | Données + helpers locaux                 |
| Resource         | Transformation réponse API               |
| Job              | Orchestration async → Action             |
| WebhookProcessor | Verify + normalize → PaymentEventData    |
| LedgerWriter     | Écritures double-entry en transaction    |

---

### 4. Analyse métier

Vérifier :

- Cohérence du flow avec `BookingStatusEnum`
- Transitions validées par `canTransitionTo()`
- Invariants métier respectés
- Absence de duplication de use case
- Alignement avec la roadmap Phase 7

**Pour GP-Valise, vérifier particulièrement :**

- Booking lifecycle (EN_PAIEMENT → LIVREE → TERMINE)
- Escrow consistency (`escrow_releasable_at`, `disputed_at`)
- Dispute workflow (OPEN → RESOLVED)
- Cohérence Booking ↔ Transaction ↔ LedgerEntry

---

### 5. Analyse financière

**Obligatoire dès qu'un flux touche Payment, Transaction, Refund, Payout, Fee ou LedgerEntry.**

| Vérification     | Critère                                        |
| ---------------- | ---------------------------------------------- |
| Source de vérité | Transaction (pas Booking)                      |
| Calculs          | Via `TransactionAmountCalculator`              |
| Montants         | Integer minor units, persistés, non recalculés |
| Exclusivité      | `PAYOUT ⊕ REFUND`                              |
| Séparation       | `FEE ≠ PAYMENT_FEE`                            |
| Escrow           | Payout non immédiat à LIVREE                   |
| Ledger           | `SUM(debits) = SUM(credits)`                   |
| Guards           | `hasExistingEntries` pour idempotence ledger   |
| Idempotence      | Charges/refunds/payouts protégés               |
| Audit            | Log créé dans la même `DB::transaction()`      |

---

### 6. Analyse technique

- Requêtes optimisées (pas de N+1)
- `DB::transaction()` sur opérations multi-modèles
- `lockForUpdate()` sur opérations concurrentes
- Gestion exceptions métier (pas silencieuses)
- Pagination sur les listes
- Index DB cohérents
- Idempotence sur opérations critiques
- Compatibilité async / job / webhook

---

### 7. Analyse sécurité

- Policy appliquée
- Pas de bypass d'autorisation
- Séparation `ADMIN / TRAVELER / SENDER`
- Aucune donnée sensible dans les logs
- Signature HMAC sur webhooks
- `WebhookProcessor` utilisé (payload PSP jamais brut)
- Audit des opérations sensibles (resolve dispute, admin refund)

---

### 8. Analyse observabilité

- `correlation_id` sur requêtes HTTP et jobs
- Logs structurés (avec contexte)
- Absence de bruit
- `webhook_logs` exploitables
- `audit_logs` consultables et chaînés
- `dispute_status_histories` présents
- Capacité à reconstruire un incident

> Peut-on retrouver l'histoire complète d'une opération sensible ?

---

### 9. Analyse testabilité

- Action testable isolément
- Tests nominal / erreur / edge case
- Tests HTTP (routes, policies, codes réponse)
- Guards escrow testés
- Guards dispute testés
- Idempotence testée
- Concurrence testée si applicable

---

## 🧪 Classification des problèmes

| Niveau          | Type                            | Exemples                                          |
| --------------- | ------------------------------- | ------------------------------------------------- |
| 🔴 Critique     | Bug métier / finance / sécurité | Double payout, bypass escrow, violation invariant |
| 🟠 Important    | Architecture / dette bloquante  | Mauvaise couche, duplication, non-idempotence     |
| 🟡 Amélioration | Lisibilité / perf               | Naming, formatage, optimisation mineure           |

---

## 🧠 Règle de décision

Avant toute correction :

1. Est-ce une violation `.adamas` ?
2. Est-ce un problème métier, financier, sécurité ou technique ?
3. Quel est l'impact réel ?
4. La correction **réduit-elle** la complexité ?
5. La correction est-elle testable ?
6. Le changement mérite-t-il une entrée dans `decision-log.md` ?

> Si la correction complexifie sans gain clair → ne pas corriger immédiatement.

---

## ✅ Résultat attendu d'un audit

1. Périmètre défini
2. Contexte métier compris
3. Problèmes classés par gravité
4. Impact métier / technique / sécurité
5. Recommandations
6. Plan d'implémentation
7. Tests à ajouter ou relancer
8. Risques de régression
9. Décision `.adamas` à documenter si nécessaire

---

## 🚫 Interdits

```
Proposer du code sans audit préalable
Corriger sans comprendre le flow complet
Ignorer les Enums
Contourner les Policies
Refactor massif non demandé
Mélanger doc, feature et fix dans une même branche
Modifier un flux financier sans test
Logger des données sensibles
```

---

## 🛡️ Non-régression obligatoire

- Tous les tests existants passent
- Au moins un test couvre le bug ou la règle
- Aucune régression métier introduite
- Impacts DB/API identifiés
- Risques documentés

---

## 🧰 Outils d'audit

`Pest` · `Laravel Pint` · `PHPStan/Larastan` · `Deptrac` · `GitHub Actions` · `php artisan route:list` · logs Laravel · `EXPLAIN` SQL si nécessaire

> Ces outils ne remplacent jamais l'analyse métier.
