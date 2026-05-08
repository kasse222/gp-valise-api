# Kkiapay SDK Integration

## Decision

The official Kkiapay PHP SDK is allowed inside the infrastructure/provider layer only.

Direct SDK usage outside payment providers is forbidden.

Architecture:

```txt
Domain / Actions
→ PaymentProvider interface
→ KkiapayProvider
→ KkiapayAdminClient
→ Official SDK
```

---

## Why

Kkiapay requires:

- 3-key authentication
- verifyTransaction()
- refundTransaction()

The official SDK simplifies:

- authentication
- sandbox/prod handling
- admin operations

Using the SDK directly inside Actions or domain services would tightly couple the business layer to a specific PSP.

---

## Constraints

### Forbidden

```php
new \Kkiapay\Kkiapay(...)
```

outside:

```txt
KkiapayAdminClient
```

### Forbidden

Direct SDK calls inside:

- Actions
- Controllers
- Domain services
- Validators

---

## Allowed responsibilities

### KkiapayAdminClient

Infrastructure wrapper only.

Responsibilities:

- SDK instantiation
- verifyTransaction()
- refundTransaction()
- exception normalization

No business logic allowed.

---

## verifyTransaction() — statut actuel

`KkiapayAdminClient::verify()` est implémenté mais pas encore branché dans le flow principal.

Usage prévu :

- double vérification webhook avant traitement (K4 renforcé)
- réconciliation manuelle admin

Règle : ne pas appeler `verify()` automatiquement à chaque webhook — latence et quota API.
Réserver aux cas ambigus ou à la demande admin explicite.

## Refund invariant

Kkiapay refunds are treated as asynchronous/untrusted until explicitly confirmed.

Rule:

```txt
unknown response != completed refund
```

The system must never mark a refund as completed solely because the SDK call succeeded.

Webhook or explicit verification remains the source of truth.

---

## Architectural impact

This preserves:

- provider isolation
- multi-PSP compatibility
- future PSP replacement capability
- testability via contracts/mocks
- domain purity

---

## Tradeoff

Using `dev-master` introduces some stability risk.

Accepted because:

- isolated behind adapter layer
- provider remains replaceable
- no SDK leakage into domain
- sandbox-first integration phase

---
