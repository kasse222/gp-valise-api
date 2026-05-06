## Routing

Le routing PSP permet de sélectionner dynamiquement le provider selon le contexte métier.

---

## 🔁 Entrées

Le routing se base sur :

- country (ISO 3166-1 alpha-2)
- method (`PaymentMethodEnum`)

Extensions futures :

- currency
- amount
- provider availability
- provider health status
- feature support (refund, payout, escrow…)

---

## ⚙️ Configuration

```php
config/payment_providers.php
```

Exemple :

```php
'routing' => [
    'SN' => [
        'mobile_money' => 'kkiapay',
        'card' => 'kkiapay',
    ],

    'MA' => [
        'card' => 'stripe',
    ],

    'FR' => [
        'card' => 'stripe',
    ],
],
```

---

## 🧠 Résolution

```php
$provider = $resolver->resolve($request);
```

Étapes :

1. normalisation country (`trim + uppercase`)
2. lecture mapping (`country + method`)
3. fallback provider
4. résolution via container Laravel
5. validation interface `PaymentProvider`

---

## 🔁 Fallback

```php
'default' => 'fake'
```

Le fallback garantit :

- stabilité dev/test ;
- comportement déterministe ;
- absence d’erreur fatale si mapping absent.

⚠️ Le fallback ne doit jamais masquer une erreur métier critique en production.

---

## 🧩 Principe d’architecture

```txt
Le domaine ne choisit jamais un provider.
Le domaine exprime un besoin.
Le resolver choisit le provider.
```

---

## 🔒 Isolation du domaine

Le domaine ignore totalement :

- Stripe
- Kkiapay
- payloads PSP
- formats webhook externes

Le domaine manipule uniquement :

- DTOs
- Enums
- PaymentEventData

---

## 🔁 Routing runtime

Le provider est résolu au runtime.

Aucun provider ne doit être hardcodé dans :

- Actions
- Controllers
- Models
- Services métier

❌ interdit :

```php
new StripeProvider()
```

---

## 🔄 Normalisation obligatoire

Tous les PSP doivent produire un format unifié :

```php
PaymentEventData
```

Le système ne traite jamais directement un payload PSP brut.

Flux obligatoire :

```txt
Webhook brut
→ verifyWebhook()
→ normalizeWebhook()
→ PaymentEventData
→ HandlePaymentWebhook
```

---

## ⚠️ Résilience provider

Le système doit supporter :

- retry webhook ;
- payload incomplet ;
- événements dupliqués ;
- événements désordonnés ;
- provider temporairement indisponible.

Le resolver doit rester découplé du domaine.

---

## 🚀 Évolutions futures

Le routing pourra évoluer vers :

- routing par devise ;
- routing par corridor ;
- failover multi-PSP ;
- load balancing PSP ;
- scoring de fiabilité provider ;
- routing par coût PSP ;
- routing par disponibilité temps réel.

---

## 🧠 Capability-based routing (future)

Tous les PSP ne supportent pas :

- refund ;
- payout ;
- escrow ;
- mobile money ;
- cartes bancaires.

Le routing évoluera vers un modèle basé sur les capacités :

```txt
provider.supportsRefunds()
provider.supportsPayouts()
provider.supportsMobileMoney()
```

---

## 🔥 Principe fondamental

```txt
Le provider est interchangeable.
Le domaine ne doit jamais dépendre
d’un PSP spécifique.
```
