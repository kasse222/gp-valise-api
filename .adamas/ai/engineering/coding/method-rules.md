# 🧠 Method Rules — GP-Valise

> Une méthode doit vivre dans la couche qui porte sa **responsabilité réelle**.

---

## 🔥 Règle de décision rapide

| Question                                            | Réponse                     |
| --------------------------------------------------- | --------------------------- |
| La méthode lit uniquement l'état local d'un objet ? | **Model** ou **Enum**       |
| La méthode change l'état du système ?               | **Action**                  |
| La méthode est utilisée par plusieurs Actions ?     | **Service**                 |
| La méthode décide si un user a le droit ?           | **Policy**                  |
| La méthode transforme une réponse API ?             | **Resource**                |
| La méthode traite de l'async ?                      | **Job → Action**            |
| La méthode normalise un webhook PSP ?               | **WebhookProcessor**        |
| La méthode route vers un PSP ?                      | **PaymentProviderResolver** |
| La méthode crée des écritures comptables ?          | **LedgerWriter**            |
| La méthode calcule une balance ?                    | **LedgerReader**            |

---

## ✅ Model

**Autorisé :**

- Getters métier simples sur ses propres données
- Scopes, relations
- Helpers locaux (`isFinal()`, `isAwaitingPayment()`, `isEscrowReleasable()`)

**Interdit :**

- Orchestrer plusieurs modèles
- Déclencher un workflow
- Calcul financier critique
- Appel Action / Service

```php
// ✅ Correct
$booking->isFinal();
$booking->isEscrowReleasable();
$transaction->isSucceeded();

// ❌ Interdit
$booking->processRefund();        // use case → Action
$booking->calculateFee();         // finance → Calculator
```

---

## ✅ Enum

**Autorisé :**

- `canTransitionTo()`, `isFinal()`, `label()`, `color()`
- Règles pures basées sur la valeur

**Interdit :**

- Accès DB
- Appel Model, Service, Action
- Logique multi-entités

```php
// ✅ Correct
BookingStatusEnum::CONFIRMEE->canTransitionTo(BookingStatusEnum::LIVREE);
DisputeStatusEnum::RESOLVED->isFinal(); // true — terminal

// ❌ Interdit
BookingStatusEnum::CONFIRMEE->getRelatedTransactions(); // DB → non
```

---

## ✅ Action

**Autorisé :**

- Orchestration métier complète d'un use case
- `DB::transaction()` + `lockForUpdate()`
- Appel Service transverse
- Dispatch Event / Job
- Levée exception métier

**Interdit :**

- `request()` ou `Auth` directs
- Calcul financier inline
- Plusieurs use cases

```php
// Actions existantes
ReserveBooking · ConfirmBooking · CompleteBooking
CreatePayoutTransaction · RefundTransaction · AdminRefundTransaction
HandlePaymentWebhook · OpenDispute · ResolveDispute
ReleaseEscrowBatch
```

---

## ✅ Service

**Autorisé :**

- Logique transverse réutilisable par plusieurs Actions
- Calcul financier centralisé
- Intégration externe (adaptée)
- Observabilité, audit integrity

**Interdit :**

- Remplacer une Action
- Contenir un use case complet
- Appeler une Action

```php
// Services existants
TransactionAmountCalculator    // calculs financiers
TransactionEligibilityService  // guards payout/refund/escrow
AuditLogIntegrityService       // seal() + verifyChain()
LedgerWriter                   // écritures double-entry
LedgerReader                   // calcul balances
QueueHealthService             // monitoring
SlackNotifier                  // alerting
```

---

## ✅ WebhookProcessor _(Phase 2+)_

**Responsabilités :**

- `verifyWebhook()` → authentification PSP-specific
- `normalizeWebhook()` → `PaymentEventData` canonique
- Isoler le Controller des payloads PSP bruts

**Interdit :**

- Logique métier
- Modification domaine

---

## ✅ PaymentProviderResolver _(Phase 2+)_

**Responsabilités :**

- Résoudre le provider PSP au runtime (pays + devise + méthode)
- Retourner une instance implémentant `PaymentProvider`

**Interdit :**

- Logique métier dans le resolver
- Hardcoding provider dans Actions

---

## ✅ LedgerWriter / LedgerReader _(Phase 5+)_

| Composant      | Responsabilité                                           | Interdit             |
| -------------- | -------------------------------------------------------- | -------------------- |
| `LedgerWriter` | Crée les écritures double-entry dans `DB::transaction()` | Calcul financier     |
| `LedgerReader` | Calcule balances via `SUM(ledger_entries)`               | Modifier des entries |

---

## ✅ Policy

**Autorisé :** Rôle, ownership simple, permissions simples.
**Interdit :** Transition métier, calcul financier, modification de données.

---

## ✅ FormRequest

**Autorisé :** `required`, format, taille, types simples.
**Interdit :** Capacité métier, statut, transitions complexes.

---

## ✅ Job

**Autorisé :** Appeler une Action, porter `correlation_id`, gérer retry/backoff/alerting.
**Interdit :** Logique métier complexe, dépendre de `request()`.

---

## ✅ Resource

**Autorisé :** Formatage sortie, `whenLoaded`, exposition contrôlée.
**Interdit :** Requêtes DB, logique métier, calcul financier.

---

## ⚠️ Cas sensibles GP-Valise

### Booking

| Question               | Réponse                       |
| ---------------------- | ----------------------------- |
| `isFinal()`            | Model                         |
| `isEscrowReleasable()` | Model                         |
| `canTransitionTo()`    | Enum                          |
| `canBeRefunded()`      | Enum                          |
| Confirmer booking      | Action (`ConfirmBooking`)     |
| Livrer booking         | Action (`CompleteBooking`)    |
| Libérer escrow         | Action (`ReleaseEscrowBatch`) |
| Ouvrir litige          | Action (`OpenDispute`)        |

### Transaction

| Question                  | Réponse                            |
| ------------------------- | ---------------------------------- |
| `isSucceeded()`           | Model                              |
| Éligibilité payout/refund | `TransactionEligibilityService`    |
| Calculer montants         | `TransactionAmountCalculator`      |
| Créer CHARGE              | Action (`CreateTransaction`)       |
| Créer PAYOUT              | Action (`CreatePayoutTransaction`) |

### Dispute

| Question             | Réponse                              |
| -------------------- | ------------------------------------ |
| `isFinal()`          | Enum (`DisputeStatusEnum::RESOLVED`) |
| Ouvrir               | Action (`OpenDispute`)               |
| Mettre à jour statut | Action (`UpdateDisputeStatus`)       |
| Résoudre             | Action (`ResolveDispute`)            |
| Ajouter message      | Action (`AddDisputeMessage`)         |

### Audit

| Question            | Réponse                                           |
| ------------------- | ------------------------------------------------- |
| `seal()`            | `AuditLogIntegrityService`                        |
| `verifyLog()`       | `AuditLogIntegrityService`                        |
| `verifyChainFrom()` | `AuditLogIntegrityService`                        |
| Créer audit log     | Dans l'Action critique (même `DB::transaction()`) |

### Observabilité

| Question                 | Réponse                    |
| ------------------------ | -------------------------- |
| Générer `correlation_id` | Middleware HTTP            |
| Porter `correlation_id`  | Propriété du Job           |
| Propager en DB           | Action / Job explicitement |

---

## 🚫 Anti-patterns interdits

```
Model obèse (orchestration dans le Model)
Service fourre-tout (use case dans un Service)
Action qui fait tout sans découpage
Policy qui décide du métier
FormRequest qui interroge le domaine
Resource qui calcule ou requête
Job qui remplace une Action
Enum qui accède à la DB
LedgerWriter avec calcul financier
PaymentProviderResolver avec logique métier
```

---

## 🧠 Principe clé

> Le bon emplacement d'une méthode se déduit de sa **responsabilité**, pas de sa facilité d'accès.
>
> Si le placement rend les tests difficiles → la méthode est au mauvais endroit.
