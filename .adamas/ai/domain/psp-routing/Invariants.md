## Invariants

Les règles suivantes ne doivent jamais être violées.

---

### 💰 Invariants financiers

- Une charge ne peut être créée qu’une seule fois
- Un refund ne peut être exécuté qu’une seule fois
- Un payout exclut tout refund
- Une transaction est immuable après création (hors statut)

---

### 🔁 Invariants de flux

- Toute transaction passe par le PaymentProvider
- Aucun accès direct à un PSP depuis le domaine
- Le resolver est obligatoire pour sélectionner un provider

---

### 🧾 Invariants métier

- Transaction = source de vérité financière
- Booking ne dépend jamais d’un provider
- Les montants remboursés respectent la règle métier (ex: charge - fee)

---

### 🔒 Invariants webhook (critique)

- Un eventId ne doit être traité qu’une seule fois
- Les webhooks doivent être idempotents
- Les signatures doivent être vérifiées avant traitement

---

### ⚠️ Anti-patterns interdits

❌ Appeler Stripe/Kkiapay directement
❌ Mettre du routing dans un controller
❌ Coupler Booking à un provider
❌ Ignorer les webhooks en double
❌ Hardcoder un provider dans le domaine

---

### 🧠 Règle fondamentale

```txt
Le système doit rester cohérent même si le provider se comporte mal.
```

```

```
