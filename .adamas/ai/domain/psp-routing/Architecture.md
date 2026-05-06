Ta section est bonne.

Franchement, elle commence à ressembler à de la vraie documentation d’architecture backend senior.

Mais je vais te faire une correction “lead engineer” :
il manque surtout :

* la couche webhook canonique
* la séparation provider payload ↔ domaine
* le rôle de `PaymentEventData`
* les invariants architecturaux
* l’async flow

Et aujourd’hui, avec tout ce que vous avez construit sur Kkiapay + webhook normalization, ça doit apparaître.

---

# Version améliorée (niveau senior propre)

````md
# 🧱 SECTION — Architecture

## Architecture

Le système de paiement repose sur une architecture découplée entre :

- domaine métier ;
- orchestration paiement ;
- providers PSP ;
- traitement async des webhooks.

L’objectif est de permettre :

- multi-PSP ;
- résilience async ;
- idempotence ;
- évolutivité multi-pays ;
- indépendance du domaine vis-à-vis des PSP.

---

## 🔁 Flux global — Charge

```txt
Action métier
→ PaymentRequestData
→ PaymentProviderResolver
→ PaymentProvider concret
→ PSP externe
→ PaymentResponseData
→ Transaction
→ Event métier
````

---

## 🔁 Flux global — Webhook

```txt
Webhook HTTP
→ WebhookProcessor
→ verifyWebhook()
→ normalizeWebhook()
→ PaymentEventData (canonique)
→ ProcessPaymentWebhook Job
→ HandlePaymentWebhook
→ Domaine métier
```

---

## 🧩 Composants

### 1. PaymentProvider (Contract)

Contrat commun à tous les PSP.

Méthodes :

* `charge()`
* `refund()`
* `verifyWebhook()`
* `normalizeWebhook()`

Chaque provider doit :

* encapsuler les détails techniques PSP ;
* retourner des DTOs canoniques ;
* rester interchangeable.

```txt
Le domaine ne dépend jamais d’un PSP concret.
```

---

### 2. PaymentProviderResolver

Responsable du routing PSP.

Critères possibles :

* pays ;
* devise ;
* méthode de paiement ;
* environnement.

Exemple :

```txt
SN + MOBILE_MONEY → Kkiapay
FR + CARD → Stripe
fallback → FakeProvider
```

👉 Point central du multi-provider.

---

### 3. DTOs (Data Transfer Objects)

Les DTOs isolent complètement le domaine des payloads externes.

#### DTOs principaux

* `PaymentRequestData`
* `RefundRequestData`
* `PaymentResponseData`
* `PaymentEventData`
* `WebhookVerificationData`

---

### 4. PaymentEventData (Canonical Event)

Objet canonique utilisé après normalisation webhook.

Tous les PSP sont convertis vers :

```php
eventId
eventType
providerTransactionId
providerStatus
amount
currency
metadata
```

👉 Stripe, Kkiapay ou FakeProvider produisent tous le même format interne.

Le domaine ne manipule jamais :

* payload Stripe ;
* payload Kkiapay ;
* headers bruts ;
* formats PSP spécifiques.

---

### 5. WebhookProcessor

Responsable de :

* construire `WebhookVerificationData`
* vérifier l’authenticité webhook ;
* normaliser le payload ;
* produire un `PaymentEventData`.

👉 Le controller reste extrêmement fin.

---

### 6. Providers

Implémentations concrètes :

* `FakePaymentProvider`
* `StripeProvider`
* `KkiapayProvider`

Chaque provider gère :

* appels API ;
* mapping statuts ;
* normalisation webhook ;
* vérification signature ;
* spécificités PSP.

---

## 🔒 Isolation du domaine

Le domaine métier ignore totalement :

* Stripe ;
* Kkiapay ;
* Wave ;
* CMI ;
* payloads externes.

Les modèles :

* `Booking`
* `Transaction`
* `Payment`

ne connaissent jamais un PSP directement.

Toute interaction passe par :

```txt
Resolver
→ Contract
→ DTOs
```

---

## 🧠 Invariants architecturaux

### 1. Domaine provider-agnostic

```txt
Aucune logique métier ne dépend d’un provider concret.
```

---

### 2. Payload externe jamais utilisé directement

```txt
Tout webhook doit être normalisé avant traitement métier.
```

---

### 3. Async obligatoire

```txt
Les webhooks ne modifient jamais directement le domaine.
```

Ils dispatchent toujours un Job async.

---

### 4. Idempotence obligatoire

```txt
event_id = invariant système
```

Aucun webhook ne peut être traité deux fois.

---

### 5. Transaction = vérité financière

```txt
Transaction est la seule source de vérité financière.
```

---

## ⚖️ Tradeoffs

| Décision              | Avantage           | Coût                        |
| --------------------- | ------------------ | --------------------------- |
| DTOs canoniques       | découplage fort    | plus de mapping             |
| Resolver centralisé   | multi-PSP scalable | configuration plus complexe |
| Webhook async         | résilience         | debugging plus difficile    |
| Providers isolés      | testabilité élevée | plus de classes             |
| Normalisation webhook | domaine stable     | couche supplémentaire       |

---

## 🚀 Évolution prévue

Architecture prévue pour supporter :

* escrow réel ;
* ledger interne ;
* multi-wallets ;
* multi-devise ;
* PSP régionaux ;
* dispute system ;
* payouts différés ;
* observabilité distribuée.

---

## 🧠 Principe clé

```txt
Le domaine dépend d’abstractions.
Jamais d’un PSP.
```

````
