# Dispute Strategy — GP-Valise

## Principe fondamental

Le système de dispute est découplé du système financier.

```txt
booking.status = EN_LITIGE    ← signal financier (escrow bloqué)
dispute.status                ← workflow arbitrage interne
```

Ces deux statuts évoluent indépendamment.
Le booking reste EN_LITIGE jusqu'à résolution financière (REMBOURSEE ou TERMINE).
Le dispute évolue selon son propre workflow d'arbitrage.

---

## Acteurs

| Acteur      | Peut ouvrir | Peut écrire message | Peut changer statut | Peut résoudre |
| ----------- | ----------- | ------------------- | ------------------- | ------------- |
| Expéditeur  | ✅          | ✅                  | ❌                  | ❌            |
| Voyageur    | ❌          | ✅                  | ❌                  | ❌            |
| Admin       | ✅          | ✅                  | ✅                  | ✅            |
| Super Admin | ✅          | ✅                  | ✅                  | ✅            |
| Modérateur  | ❌          | ❌                  | ❌                  | ❌            |

---

## Workflow dispute

```txt
                    ┌─────────────────────────────┐
                    │           OPEN               │
                    └──────────────┬──────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │        UNDER_REVIEW          │◄──────────┐
                    └──────┬───────────────┬───────┘           │
                           │               │                   │
              ┌────────────▼──┐     ┌──────▼──────────┐       │
              │WAITING_CUSTOMER│     │WAITING_TRAVELER  │       │
              └────────────┬──┘     └──────┬──────────┘       │
                           │               │                   │
                           └───────┬───────┘                   │
                                   │                           │
                    ┌──────────────▼──────────────┐           │
              ┌────►│          ESCALATED           │───────────┘
              │     └──────────────┬──────────────┘
              │                    │
              │     ┌──────────────▼──────────────┐
              └─────│           RESOLVED           │
                    └─────────────────────────────┘
```

### Transitions autorisées

| De               | Vers                                                    |
| ---------------- | ------------------------------------------------------- |
| OPEN             | UNDER_REVIEW, ESCALATED, RESOLVED                       |
| UNDER_REVIEW     | WAITING_CUSTOMER, WAITING_TRAVELER, ESCALATED, RESOLVED |
| WAITING_CUSTOMER | UNDER_REVIEW, ESCALATED, RESOLVED                       |
| WAITING_TRAVELER | UNDER_REVIEW, ESCALATED, RESOLVED                       |
| ESCALATED        | UNDER_REVIEW, RESOLVED                                  |
| RESOLVED         | ∅ (terminal)                                            |

---

## Contraintes

```txt
1. Une seule dispute active par booking (unique constraint DB)
2. RESOLVED est terminal — aucune transition possible
3. resolve() est atomique :
   status + decision + resolution + resolved_by + resolved_at
   + DisputeStatusHistory + DisputeStatusChanged event
4. Toute transition crée un DisputeStatusHistory
5. dispute.booking_id unique → pas de double dispute simultanée
```

---

## Résolution

Deux décisions possibles :

```txt
DECISION_REFUND → AdminRefundTransaction
  - PAYOUT PENDING → FAILED (libère éligibilité refund)
  - Transaction REFUND créée
  - Booking reste EN_LITIGE → webhook refund.completed → REMBOURSEE
  - Dispute → RESOLVED (decision: refund)

DECISION_PAYOUT → payout direct
  - PAYOUT PENDING → COMPLETED
  - LedgerWriter::writePayoutPaid()
  - Booking EN_LITIGE → TERMINE
  - Dispute → RESOLVED (decision: payout)
```

Contrainte payout :

```txt
Un booking EN_LITIGE venant de CONFIRMEE n'a pas de PAYOUT PENDING.
La décision 'payout' est impossible dans ce cas.
L'admin doit choisir 'refund'.
```

---

## Messages & preuves

```txt
dispute_messages
  author_id   → expéditeur | voyageur | admin
  body        → texte libre
  attachments → json (paths S3 ou URLs) — optionnel
  created_at  → append-only, pas de updated_at
```

Acteurs autorisés à écrire :

- expéditeur du booking
- voyageur du trip
- admin / super_admin

Dispute RESOLVED → plus de nouveaux messages.

---

## Assignation admin

L'admin qui passe une dispute à UNDER_REVIEW est automatiquement
assigné si `assigned_to` est null.

Pas de réassignation automatique si déjà assigné.
Réassignation manuelle possible via `assigned_to` update.

---

## Events

```txt
DisputeStatusChanged(dispute, ?oldStatus, newStatus, reason)
  → dispatché à chaque transition (y compris OPEN initial)
  → oldStatus = null pour la création

DisputeMessageAdded(message)
  → dispatché à chaque nouveau message
```

Ces events sont prévus pour notifications futures
(email, websocket, Slack) — non implémentées en Phase 6.

---

## Tables

```sql
disputes
  id
  booking_id          unique FK → bookings
  status              open | under_review | waiting_customer
                      | waiting_traveler | escalated | resolved
  opened_by           FK → users
  assigned_to         FK → users nullable
  resolved_by         FK → users nullable
  reason              text (raison ouverture)
  resolution          text nullable (raison fermeture)
  decision            refund | payout | null
  resolved_at         timestamp nullable
  created_at / updated_at

dispute_messages
  id
  dispute_id          FK → disputes (cascade)
  author_id           FK → users
  body                text
  attachments         json nullable
  created_at          (pas de updated_at — append-only)

dispute_status_histories
  id
  dispute_id          FK → disputes (cascade)
  old_status          string nullable (null = création)
  new_status          string
  changed_by          FK → users nullable
  reason              string nullable
  created_at          (pas de updated_at — append-only)
```

---

## Actions

| Action              | Acteur                    | Effet principal                        |
| ------------------- | ------------------------- | -------------------------------------- |
| OpenDispute         | expéditeur / admin        | Dispute OPEN + booking EN_LITIGE       |
| UpdateDisputeStatus | admin                     | Transition workflow + history          |
| AddDisputeMessage   | expéditeur/voyageur/admin | Message + pièces jointes               |
| ResolveDispute      | admin                     | Dispute RESOLVED + décision financière |

---

## Invariants financiers

```txt
- disputed_at !== null → isEscrowReleasable() = false
- Escrow bloqué indéfiniment pendant dispute
- PAYOUT PENDING bloqué par ReleaseEscrowBatch
- PAYOUT FAILED ne bloque pas un remboursement ultérieur
- Un seul REFUND par booking (invariant TransactionEligibilityService)
```

---

## Roadmap dispute

```txt
Phase 6 ✅
  OpenDispute v2    → crée Dispute en base
  ResolveDispute v2 → Dispute::resolve()
  UpdateDisputeStatus
  AddDisputeMessage
  DisputeResource Filament (⏳ en cours)

Phase 7 ⏳
  API publique lecture (expéditeur/voyageur)
  Notifications email/websocket
  Upload pièces jointes S3
  Multi-dispute historique
  SLA escalade automatique
```

```

---
```
