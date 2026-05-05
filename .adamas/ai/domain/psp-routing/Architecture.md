# 🧱 SECTION — Architecture

````md
## Architecture

Le système de paiement repose sur une architecture découplée entre domaine métier et infrastructure PSP.

### 🔁 Flux global

Action métier  
→ DTO (`PaymentRequestData` / `RefundRequestData`)  
→ `PaymentProviderResolver`  
→ `PaymentProvider` (Stripe, Kkiapay, Fake…)  
→ `PaymentResponseData`  
→ Mapping vers `Transaction`  
→ Événement (`TransactionCreated`, `TransactionRefunded`)

---

### 🧩 Composants

#### 1. PaymentProvider (Contract)

Interface commune à tous les PSP :

- charge()
- refund()
- verifyWebhook()
- normalizeWebhook()

👉 Aucun provider concret ne doit être utilisé directement dans le domaine.

---

#### 2. PaymentProviderResolver

Responsable de :

- déterminer le provider en fonction du pays + méthode
- instancier dynamiquement le bon provider

👉 Point central du routing.

---

#### 3. DTOs (Data Transfer Objects)

Permettent de découpler le domaine des formats PSP :

- PaymentRequestData
- RefundRequestData
- PaymentResponseData
- PaymentEventData
- WebhookVerificationData

👉 Le domaine ne manipule jamais des payloads bruts PSP.

---

#### 4. Providers

Implémentations concrètes :

- FakePaymentProvider (tests / local)
- StripeProvider (Europe / carte)
- KkiapayProvider (Afrique / mobile money)

---

### 🧠 Principe clé

```txt
Le domaine dépend d’abstractions, jamais d’un provider concret.
```
````

---

### 🔒 Isolation

- Booking / Transaction ignorent totalement Stripe/Kkiapay
- Toute interaction passe par le resolver

```

```
