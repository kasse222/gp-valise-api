# 🧪 Testing Strategy — GP-Valise

> 415 tests · 985 assertions · 3.04s
> Pest · PostgreSQL test DB · Docker

---

## 🧠 Philosophie

```
Tester le comportement métier, pas l'implémentation.
Un test qui casse quand on renomme une variable → mauvais test.
Un test qui casse quand on viole un invariant → bon test.
```

---

## 🏗️ Organisation

```
tests/
├── Unit/
│   ├── Enums/           → transitions, helpers métier
│   ├── Services/        → Calculator, EligibilityService, AuditIntegrity
│   └── Validators/      → LuggageValidator, BookingValidator
└── Feature/
    ├── Booking/
    │   ├── Actions/     → use cases métier
    │   └── CrudTest     → endpoints HTTP
    ├── Payment/
    │   ├── Actions/     → HandlePaymentWebhook, CreatePayout, Refund
    │   ├── Jobs/        → ProcessPaymentWebhook
    │   └── Webhook*/    → Controller, EndToEnd
    ├── Transaction/
    │   └── Actions/     → AdminRefundTransaction, CreatePayoutTransaction
    ├── Trip/
    │   └── CapacitySemanticsTest → alignement scopeReservable/gramsReserved
    ├── Dispute/         → OpenDispute, ResolveDispute, UpdateStatus, AddMessage
    ├── Admin/           → AuditLogViewer, DisputeResource
    ├── Observability/   → CorrelationIdMiddlewareTest
    └── Console/         → MonitorQueueHealth, MonitorWebhookHealth
```

---

## 🎯 Priorités de couverture

| Priorité | Couche              | Raison                                       |
| -------- | ------------------- | -------------------------------------------- |
| 🔴 1     | Actions financières | Invariants métier critiques                  |
| 🔴 1     | Services critiques  | Calculator, EligibilityService, LedgerWriter |
| 🔴 1     | Webhook handling    | Idempotence + flows financiers async         |
| 🔴 1     | Escrow guards       | Payout non immédiat, blocage dispute         |
| 🟠 2     | Dispute workflow    | Transitions, résolution, accès rôles         |
| 🟠 2     | Capacité Trip       | Alignement scopeReservable / gramsReserved   |
| 🟠 2     | Audit integrity     | Chaîne de hash                               |
| 🟡 3     | Endpoints HTTP      | Routes, policies, codes réponse              |
| 🟡 3     | Console commands    | Monitoring, batch                            |

---

## 🔁 Ce qui doit être testé par type

### Actions

Pour chaque Action, couvrir obligatoirement :

| Scénario                           | Type    |
| ---------------------------------- | ------- |
| Cas nominal (happy path)           | Feature |
| Invariant violé → exception métier | Feature |
| Idempotence (double appel)         | Feature |
| Guard escrow si applicable         | Feature |
| Guard dispute si applicable        | Feature |
| Audit log créé si action sensible  | Feature |

### Services financiers

| Service                         | Tests obligatoires                                                                               |
| ------------------------------- | ------------------------------------------------------------------------------------------------ |
| `TransactionAmountCalculator`   | fee, payout, refund, payment_fee, profit_net, taux configurables                                 |
| `TransactionEligibilityService` | payout OK, payout refusé (refund existe, payout existe, fee existe, escrow non écoulé, disputed) |
| `AuditLogIntegrityService`      | seal(), verifyLog(), détection corruption, chaîne complète                                       |
| `LedgerWriter`                  | SUM(debits) = SUM(credits) par flow, idempotence hasExistingEntries                              |

### Webhooks

| Scénario                                    | Attendu              |
| ------------------------------------------- | -------------------- |
| Signature valide                            | Traité               |
| Signature invalide                          | HTTP 403             |
| Double event_id                             | Ignoré (idempotence) |
| Transaction introuvable                     | Retry                |
| Event non supporté                          | Ignored              |
| Transaction déjà finalisée                  | Ignored              |
| Payload incomplet                           | Ignoré sans log      |
| `refund.completed` depuis booking CONFIRMEE | REMBOURSEE (bug C3)  |
| `correlation_id` propagé dans WebhookLog    | Présent              |

### Escrow

| Scénario                                        | Attendu             |
| ----------------------------------------------- | ------------------- |
| Payout bloqué si `escrow_releasable_at > now()` | Exception           |
| Payout bloqué si `disputed_at IS NOT NULL`      | Exception           |
| Payout déclenché si conditions OK               | Créé                |
| Idempotence batch (double exécution)            | Aucun double payout |

### Dispute

| Scénario                           | Attendu                                   |
| ---------------------------------- | ----------------------------------------- |
| Expéditeur ouvre un litige         | Dispute OPEN + booking EN_LITIGE          |
| Voyageur tente d'ouvrir            | 403                                       |
| Double dispute sur même booking    | Exception (unique constraint)             |
| Admin résout → refund              | AdminRefundTransaction + Dispute RESOLVED |
| Admin résout → payout              | Payout + Dispute RESOLVED                 |
| Message après RESOLVED             | Exception                                 |
| Transition RESOLVED → autre statut | Exception (terminal)                      |

### Capacité Trip

| Scénario                                  | Attendu  |
| ----------------------------------------- | -------- |
| CONFIRMEE comptés dans gramsReserved      | Oui      |
| EN_PAIEMENT non expirés comptés           | Oui      |
| EN_PAIEMENT expirés non comptés           | Non      |
| scopeReservable aligné avec gramsReserved | Cohérent |
| canAcceptGrams() respecte les deux        | Oui      |

---

## 🏭 Factories — règles

```
✅ Factories uniquement (jamais de données hardcodées)
✅ État explicite dans les factories critiques
❌ Rôle random dans les factories financières
❌ User::factory()->create() sans rôle précis sur endpoints protégés
```

```php
// ✅ Correct
$admin = User::factory()->admin()->create();
$booking = Booking::factory()->confirmed()->create();

// ❌ Fragile
$user = User::factory()->create(); // quel rôle ?
```

**États factory recommandés :**

| Factory       | États utiles                                                   |
| ------------- | -------------------------------------------------------------- |
| `Booking`     | `pending()`, `confirmed()`, `delivered()`, `inDispute()`       |
| `Transaction` | `charge()`, `payout()`, `refund()`, `completed()`, `pending()` |
| `Dispute`     | `open()`, `underReview()`, `resolved()`                        |
| `User`        | `sender()`, `traveler()`, `admin()`                            |

---

## ✍️ Conventions de nommage

```php
// Format Pest
it('décrit le comportement attendu en français', function () {});

// ✅ Bon
it('refuse un payout si un refund existe déjà');
it('crée un audit log avec integrity_hash lors d\'un refund admin');
it('est idempotent si le même event_id est reçu deux fois');

// ❌ Mauvais
it('test payout');
it('works');
```

---

## 🧱 Règles d'isolation

```
✅ Chaque test est indépendant (RefreshDatabase ou transactions)
✅ Pas de partage d'état entre tests
✅ Mocks uniquement pour les PSP externes (FakePaymentProvider)
✅ DB réelle pour les invariants financiers (pas de mocks)
❌ Tester l'implémentation interne (test le comportement)
❌ Dépendre de l'ordre d'exécution des tests
```

---

## 🔐 Tests de sécurité obligatoires

Pour tout endpoint sensible :

| Scénario                       | Attendu |
| ------------------------------ | ------- |
| Non authentifié                | 401     |
| Rôle insuffisant               | 403     |
| Ressource d'un autre user      | 403     |
| Admin sur ressource quelconque | 200     |

---

## 📋 Tests de référence du projet

| Sujet               | Fichier                       | Ce qu'il prouve                 |
| ------------------- | ----------------------------- | ------------------------------- |
| Idempotence webhook | `HandlePaymentWebhookTest`    | event_id UNIQUE                 |
| Capacité trip       | `CapacitySemanticsTest`       | scopeReservable ↔ gramsReserved |
| Chaîne audit        | `AdminRefundTransactionTest`  | integrity_hash + previous_hash  |
| Bug C3              | `HandlePaymentWebhookTest`    | CONFIRMEE → REMBOURSEE          |
| Escrow guards       | `ReleaseEscrowBatchTest`      | 48h + no dispute                |
| Ledger balance      | `CreatePayoutTransactionTest` | SUM debits = SUM credits        |

---

## ⚡ Performance

```bash
# Run complet
make test

# Seuil acceptable
< 10s pour 415 tests

# Si > 10s → investiguer les requêtes N+1 ou les factories lentes
```

---

## 🚫 Interdits

```
dd() / dump() laissés dans les tests
Tests dépendants de l'ordre d'exécution
Mocks pour la logique financière interne
Assertions vagues (assertTrue(true))
Tests sans assertion métier réelle
Ignorer les cas d'idempotence sur flux financier
```

---

## 🧠 Principe clé

```
Un test qui ne peut pas échouer ne protège rien.
Un test qui échoue sur une violation d'invariant → valeur réelle.
```
