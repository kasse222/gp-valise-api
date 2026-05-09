````md id="z2m8k1"
# Kkiapay SDK Integration

---

# Decision

The official Kkiapay PHP SDK is restricted to the infrastructure adapter layer only.

Direct SDK usage outside payment infrastructure is forbidden.

Architecture:

```txt
Domain / Actions
→ PaymentProvider interface
→ KkiapayProvider
→ KkiapayAdminClient
→ Official Kkiapay SDK
```
````

The domain layer must never depend directly on:

- Kkiapay SDK
- Kkiapay payload formats
- Kkiapay-specific identifiers
- Kkiapay business assumptions

---

# Why

Kkiapay requires:

- 3-key authentication
- transaction verification
- refund operations
- webhook validation
- sandbox/prod handling

The official SDK simplifies:

- authentication
- provider communication
- admin operations
- environment handling

Using the SDK directly inside Actions or domain services would tightly couple the business layer to a specific PSP.

---

# Architectural Boundary

The SDK belongs exclusively to:

```txt id="6frqfy"
infrastructure adapter layer
```

It must never become:

```txt id="n53k9o"
a business decision dependency
```

The domain remains:

- PSP-agnostic
- provider-isolated
- treasury-driven

---

# Constraints

---

## Forbidden

```php id="5g6h7a"
new \Kkiapay\Kkiapay(...)
```

outside:

```txt id="a9v8k3"
KkiapayAdminClient
```

---

## Forbidden

Direct SDK calls inside:

- Actions
- Controllers
- Domain services
- Validators
- Policies
- Eligibility services

---

## Forbidden

Using Kkiapay-specific values inside domain logic:

- `transactionId`
- `partnerId`
- `source_common_name`
- raw SDK response fields

must never influence:

- payout eligibility
- refund eligibility
- booking transitions
- escrow rules

---

# Allowed Responsibilities

## KkiapayAdminClient

Infrastructure wrapper only.

Responsibilities:

- SDK instantiation
- verifyTransaction()
- refundTransaction()
- exception normalization
- response normalization

---

## Forbidden Responsibilities

`KkiapayAdminClient` must never contain:

- business logic
- payout rules
- refund eligibility logic
- escrow logic
- booking transitions
- treasury ownership logic

---

# Treasury Boundary

The SDK never owns treasury logic.

Treasury ownership remains controlled by:

- Transactions
- PlatformAccounts
- Escrow rules
- Eligibility services

The SDK is only:

```txt id="5y7g8j"
an external financial communication adapter
```

---

# verifyTransaction() — Current Status

`KkiapayAdminClient::verify()` is implemented but not yet part of the primary financial flow.

Planned usages:

- reconciliation
- admin verification
- ambiguous webhook verification
- manual treasury investigations

---

# Verification Strategy

`verify()` is considered:

```txt id="r6s7t8"
an auxiliary reconciliation mechanism
```

and NOT:

```txt id="h1j2k3"
the primary source of truth
```

Primary financial signals remain:

- webhook events
- invariant validation
- persisted transactions

---

# Async Consistency

Kkiapay operations are treated as:

```txt id="v4b5n6"
eventually consistent
```

A successful SDK response does NOT guarantee:

- financial finality
- payout eligibility
- refund completion

Financial state becomes authoritative only after:

- webhook confirmation
- reconciliation
- invariant validation
- persisted transaction state

---

# Refund Invariant

Calling:

```php id="g7h8i9"
refundTransaction()
```

means:

```txt id="x9y0z1"
refund request initiated
```

NOT:

```txt id="c2d3e4"
refund completed
```

---

# Critical Rule

```txt id="f5g6h7"
unknown response != completed refund
```

The system must never mark a refund as completed solely because the SDK call succeeded.

Refund finality requires:

- webhook confirmation
  OR
- explicit reconciliation verification

---

# Webhook Priority

Webhook events remain:

```txt id="u7v8w9"
the primary async financial signal
```

The SDK is secondary to:

- webhook-driven consistency
- persisted transaction states
- domain invariants

---

# Multi-PSP Portability

The domain layer must remain:

```txt id="m1n2o3"
provider-agnostic
```

Replacing Kkiapay with another mobile money provider must NOT require:

- Booking domain changes
- Transaction domain changes
- Escrow rule changes
- Treasury rule changes

Only infrastructure adapters should change.

---

# Architectural Impact

This architecture preserves:

- provider isolation
- treasury isolation
- multi-PSP compatibility
- future PSP replacement capability
- testability via contracts/mocks
- async consistency
- domain purity

---

# Tradeoff

Using:

```txt id="q4r5s6"
dev-master
```

introduces some stability risk.

Accepted because:

- isolated behind adapter layer
- SDK leakage prevented
- provider remains replaceable
- sandbox-first integration phase
- infrastructure abstraction already enforced

---

# Future Evolution

Possible future additions:

- reconciliation workers
- PSP health scoring
- webhook confidence scoring
- provider failover
- payout provider routing
- settlement reconciliation
- dispute-linked verification

---

# Design Philosophy

The SDK is treated as:

```txt id="t7u8v9"
an unreliable external boundary
```

The domain layer remains:

- deterministic
- invariant-driven
- provider-isolated
- treasury-controlled

Financial consistency must never depend solely on:

- SDK success
- HTTP status codes
- provider optimistic responses

```

```
