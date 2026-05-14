# 🧠 Coding Standards — GP-Valise

> Standards de développement obligatoires pour garantir cohérence, sécurité et maintenabilité.

---

## 🧼 Style de code

| Règle      | Valeur                                             |
| ---------- | -------------------------------------------------- |
| Standard   | PSR-12 obligatoire                                 |
| Types      | `declare(strict_types=1);` + types stricts partout |
| Logique    | Early return (éviter `else`)                       |
| Lisibilité | Code lisible > code intelligent                    |

---

## 📁 Nommage

| Couche      | Convention      | Exemple                            |
| ----------- | --------------- | ---------------------------------- |
| Controller  | `XxxController` | `BookingController`                |
| Action      | `VerbeNom`      | `ConfirmBooking`, `ResolveDispute` |
| FormRequest | `XxxRequest`    | `CreateBookingRequest`             |
| Policy      | `XxxPolicy`     | `BookingPolicy`                    |
| Enum        | `XxxEnum`       | `BookingStatusEnum`                |
| Service     | `XxxService`    | `TransactionEligibilityService`    |
| Job         | `VerbeNomJob`   | `ReleaseEscrowPayoutJob`           |
| Resource    | `XxxResource`   | `BookingResource`                  |

---

## 🧱 Structure

```
app/
  Actions/
    Booking/
    Transaction/
    Dispute/
  Models/
  Enums/
  Services/
    Finance/
    Audit/
    PSP/
  Jobs/
  Events/
  Policies/
  Http/
    Controllers/
    Requests/
    Resources/
```

---

## 💱 Représentation des données

### Monétaire — integer minor units

```php
// ✅ Obligatoire
1500      // = 15.00€
10000     // = 100.00€

// ❌ Interdit
15.00     // float
'15.00'   // string
```

### Poids — integer grammes

```php
// ✅ Obligatoire
25000     // = 25kg
500       // = 500g

// ❌ Interdit
25.0      // float
```

---

## 🔒 Transactions DB

**Obligatoire si :**

- Modification multi-modèles
- Opération financière (Transaction + LedgerEntry)
- Opération critique (dispute, escrow release)

```php
DB::transaction(fn () => ...);
```

---

## ⚠️ Concurrence

`lockForUpdate()` obligatoire sur :

| Opération            | Raison              |
| -------------------- | ------------------- |
| Réservation capacité | Race condition      |
| Confirmation booking | Double confirmation |
| Escrow release       | Double payout       |
| Refund               | Double refund       |
| Payout               | Double payout       |
| Webhook processing   | Replay idempotence  |

---

## 🔁 Idempotence

Obligatoire pour : webhook · paiement · payout · refund · ledger entries

```php
if ($alreadyProcessed) {
    return; // silencieux — pas d'exception
}
```

---

## 🧠 Enums — règle critique

```php
// ❌ Interdit
if ($booking->status === 'CONFIRMEE')

// ✅ Obligatoire
if ($booking->status === BookingStatusEnum::CONFIRMEE)
```

---

## 💰 Calculs financiers

**Aucun calcul** dans : Controller · Model · Policy

```
✅ TransactionAmountCalculator  → fee, payout, refund, payment_fee
✅ FeeCalculator                → résolution du taux applicable
```

---

## 🏦 Ledger — règles critiques

```php
// ❌ Interdit
$account->balance += $amount;          // balance matérialisée

// ✅ Obligatoire
LedgerWriter::writeCharge($transaction); // dans DB::transaction()

// ❌ Interdit hors seal()
$entry->save();                         // LedgerEntry immuable
```

---

## 🔌 PSP isolation

```php
// ❌ Interdit dans Actions / Controllers / Domain
new KkiapayProvider()
$kkiapay->charge(...)

// ✅ Obligatoire
$provider = $resolver->resolve($request);
$provider->charge($paymentRequestData);
```

Le domaine ne connaît jamais un PSP concret.

---

## 🚫 Interdits critiques

```
dd(), dump() en production
request() dans une Action
Auth:: dans une Action
Statuts en string (Enum obligatoire)
Logique métier dans Controller ou Policy
Service fourre-tout
Duplication de logique
Absence de DB::transaction() sur opération critique
Absence d'idempotence sur flux financier
Float ou decimal pour money/poids
Payload PSP brut dans le domaine
Balance matérialisée sur ledger_accounts
```

---

## 🎯 Actions — bonnes pratiques

```php
// Signature explicite
public function execute(
    Booking $booking,
    User $admin,
    string $reason,
): Transaction {
    // ...
}
```

- Paramètres typés
- Retour typé
- Aucune dépendance globale
- Exception métier claire si invariant violé

---

## ⚠️ Exceptions

```
✅ DomainException      → violation invariant métier
✅ ValidationException  → données invalides
❌ Exception silencieuse
❌ catch vide
❌ return null sur erreur critique
```

---

## 📜 Logging

**Logger :** erreurs métier · événements critiques · retry/échec

**Ne jamais logger :**

```
email · téléphone · KYC · token · numéro carte
```

---

## 🔍 Observabilité

`correlation_id` obligatoire dans :

| Contexte      | Mécanisme                         |
| ------------- | --------------------------------- |
| Requêtes HTTP | Middleware (généré ou conservé)   |
| Logs Laravel  | `Log::withContext([...])`         |
| Jobs async    | Propriété du constructeur         |
| Webhooks      | Transmis à `HandlePaymentWebhook` |

---

## ⚙️ Jobs

```
✅ Un Job appelle une Action
✅ retry + backoff obligatoires
✅ correlation_id transmis
✅ Alerting Slack si échec critique
❌ Logique métier dans le Job
```

---

## 🧪 Tests

### Priorité

1. Actions (logique métier + invariants)
2. Services critiques (EligibilityService, Calculator, LedgerWriter)
3. HTTP (routes, policies, codes réponse)

### À couvrir obligatoirement

- Cas nominal
- Erreurs et edge cases
- Idempotence
- Guards escrow
- Guards dispute
- Concurrence (si applicable)

### Bonnes pratiques

- Factories uniquement (pas de données hardcodées)
- Tests isolés et déterministes
- Pas de rôle random dans les factories critiques

---

## 📌 Références

`.adamas/ai/engineering/method-rules.md` · `.adamas/ai/domain/architecture.md` · `.adamas/domain/ledger-strategy.md` · `.adamas/domain/psp-routing/Architecture.md`
