```markdown
# Escrow Lifecycle — GP-Valise

## Vue d'ensemble
```

CONFIRMEE
│
▼
[CompleteBooking::execute()]
│
▼
LIVREE
│
├── delivered_at = now()
└── escrow_releasable_at = now() + 48h
│
▼
[scheduler hourly]
escrow:release-payouts
│
▼
[ReleaseEscrowBatch::execute()]
scan bookings WHERE:
status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND no PAYOUT
AND no REFUND
│
▼
[ReleaseEscrowPayoutJob]
│
▼
[CreatePayoutTransaction::execute()]
│
▼
PAYOUT PENDING → voyageur
FEE COMPLETED → plateforme

```

## Invariant payout

```

booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND charge COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS

```

## Flow dispute

```

LIVREE ou CONFIRMEE
│
▼
[OpenDispute::execute()] ← Phase 4 suite
│
├── disputed_at = now()
└── transition → EN_LITIGE
│
▼
escrow bloqué indéfiniment
ReleaseEscrowBatch ignore ce booking
│
▼
[Admin override requis]
AdminRefundTransaction::execute()
│
▼
REFUND COMPLETED → expéditeur
booking → REMBOURSEE

````

## Champs bookings

| Champ | Type | Description |
|---|---|---|
| `delivered_at` | timestamp nullable | Moment où le voyageur marque LIVREE |
| `escrow_releasable_at` | timestamp nullable | `delivered_at + escrow_delay_hours` |
| `disputed_at` | timestamp nullable | Moment d'ouverture dispute — bloque escrow |

## Config

```env
GPVALISE_ESCROW_DELAY_HOURS=48  # défaut : 48h
````

## Transitions statut

```
CONFIRMEE → LIVREE          CompleteBooking
LIVREE    → EN_LITIGE       OpenDispute        (Phase 4 suite)
LIVREE    → TERMINE         (post-payout)
EN_LITIGE → REMBOURSEE      AdminRefundTransaction
EN_LITIGE → TERMINE         AdminOverride
```

## Composants

| Fichier                                                | Rôle                                  |
| ------------------------------------------------------ | ------------------------------------- |
| `app/Actions/Booking/CompleteBooking.php`              | Transition LIVREE + markDelivered()   |
| `app/Actions/Booking/ReleaseEscrowBatch.php`           | Scan + dispatch jobs                  |
| `app/Jobs/ReleaseEscrowPayoutJob.php`                  | Exécution payout avec guard           |
| `app/Console/Commands/ReleaseEscrowPayoutsCommand.php` | Artisan command                       |
| `app/Services/TransactionEligibilityService.php`       | Invariant payout                      |
| `app/Models/Booking.php`                               | isEscrowReleasable(), markDelivered() |

## Décisions

**Pourquoi pas de job différé (delay) ?**
Un job différé avec `dispatch()->delay(48h)` est fragile — si le worker redémarre,
le job peut être perdu. Le scheduler périodique est plus résilient : idempotent,
rejouable, observable via Horizon.

**Pourquoi 48h ?**
Compromis marketplace standard. Configurable via `GPVALISE_ESCROW_DELAY_HOURS`
pour ajuster par environnement ou par type de trajet (Phase 5+).

**Pourquoi `disputed_at` et pas un statut ?**
`disputed_at` est un timestamp métier — il permet de calculer la durée de la dispute,
de l'historiser, et de la lever sans changer le statut. Le statut `EN_LITIGE` reste
la source de vérité pour les transitions, `disputed_at` est le garde-fou escrow.

```

---

```
