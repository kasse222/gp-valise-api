## Invariants

Les règles suivantes ne doivent jamais être violées.

---

## 💰 Invariants financiers

- Une `CHARGE` ne peut être créée qu’une seule fois par booking
- Un `REFUND` ne peut être exécuté qu’une seule fois
- Un `PAYOUT` exclut tout `REFUND`
- Les montants financiers persistés ne sont jamais recalculés
- `Transaction` est la seule source de vérité financière
- `PAYMENT_FEE` n’est jamais remboursée en MVP
- Les calculs financiers doivent passer par les services dédiés :
    - `FeeCalculator`
    - `TransactionAmountCalculator`

---

## 🔁 Invariants de flux

- Toute opération financière passe par un `PaymentProvider`
- Aucun accès direct à un PSP depuis le domaine
- Le `PaymentProviderResolver` est obligatoire pour sélectionner un provider
- Les webhooks ne modifient jamais directement le domaine
- Tout webhook doit passer par :
    - `verifyWebhook()`
    - `normalizeWebhook()`
    - `PaymentEventData`

---

## 🧾 Invariants métier

- `Booking` ne dépend jamais d’un provider PSP
- Le domaine ignore totalement :
    - Stripe
    - Kkiapay
    - payloads externes
- Les remboursements respectent toujours les règles métier :
    - `refund_possible = CHARGE - FEE`
- Les transitions de statuts doivent respecter `BookingStatusEnum`

---

## 🔒 Invariants webhook (critique)

- Un `eventId` ne doit être traité qu’une seule fois
- Tous les webhooks doivent être idempotents
- Les signatures ou mécanismes d’authenticité doivent être vérifiés avant traitement
- Aucun payload PSP brut ne doit atteindre le domaine
- Tous les webhooks doivent être normalisés en `PaymentEventData`
- Les retries webhook ne doivent jamais produire de double effet financier

---

## ⚙️ Invariants async

- Toute logique webhook critique passe par une queue
- Les handlers doivent être retry-safe
- Les traitements doivent supporter :
    - retry ;
    - duplication ;
    - désordre d’arrivée des événements ;
    - PSP instable

---

## 🛡️ Invariants sécurité

- `DENY BY DEFAULT`
- Toute action sensible nécessite :
    - Policy
    - validation métier
- Les audit logs critiques sont append-only
- Les audit logs critiques doivent être scellés (`seal()`)

---

## 🚫 Anti-patterns interdits

❌ Appeler Stripe/Kkiapay directement depuis une Action métier  
❌ Coupler `Booking` à un provider  
❌ Router un PSP dans un controller  
❌ Ignorer les webhooks dupliqués  
❌ Recalculer l’historique financier  
❌ Utiliser un payload PSP brut dans le domaine  
❌ Modifier un `AuditLog` après scellement  
❌ Hardcoder un provider dans le domaine

---

## 🧠 Règle fondamentale

```txt
Le système doit rester cohérent
même si :

- le provider ment,
- le webhook arrive 5 fois,
- les événements arrivent dans le désordre,
- ou le PSP devient indisponible.
```

---

## 🔥 Principe d’architecture

```txt
Le domaine doit survivre
à un provider défaillant.
```
