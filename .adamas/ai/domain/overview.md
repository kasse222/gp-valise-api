# 🧠 Domain Overview — GP-Valise

> Vision système et règles transverses du projet.

---

## 🎯 Vision produit

GP-Valise est une plateforme logistique de confiance permettant à un expéditeur d'envoyer un objet via un voyageur tiers, avec un système sécurisé de paiement, escrow, tracking, audit et gestion des litiges.

**Trois piliers :**

| Pilier        | Composants                                                      |
| ------------- | --------------------------------------------------------------- |
| 🚚 Logistique | Trip · Luggage · Booking · BookingItem                          |
| 🔒 Confiance  | KYC · Reports · AuditLog · Dispute                              |
| 💰 Finance    | Payment · Transaction · Escrow · Ledger · Refund · Payout · Fee |

---

## 👥 Acteurs

| Acteur         | Peut                                                                          | Ne peut pas                                      |
| -------------- | ----------------------------------------------------------------------------- | ------------------------------------------------ |
| **Expéditeur** | Créer bagages, réserver, payer, suivre, ouvrir un litige                      | Accéder aux données d'autrui, résoudre un litige |
| **Voyageur**   | Publier un trajet, confirmer livraison, recevoir payout, répondre à un litige | Ouvrir un litige, résoudre un litige             |
| **Admin**      | Tout superviser, gérer les litiges, déclencher actions critiques              | Actions sans audit, actions sans raison          |

---

## 🧱 Objets métier principaux

### Trip

Trajet proposé par un voyageur.

- Capacité en **grammes** (integer)
- Statuts : `ACTIVE` · `PENDING` · `CLOSED` · `CANCELLED`
- Réservable si : `gramsDisponible() > 0` + date future + `status.isReservable()`

### Luggage

Objet ou colis transporté.

- Poids en **grammes** (integer)
- Cycle : `EN_ATTENTE → RESERVEE → EN_TRANSIT → LIVREE`
- Libéré si booking expire ou est annulé

### Booking ⭐ Pivot métier

Réservation entre expéditeur, bagages et trajet.

- Orchestre logistique + finance
- Dépend des Transactions pour les décisions financières
- Ne calcule jamais les montants lui-même
- Porte l'état escrow (`delivered_at`, `escrow_releasable_at`, `disputed_at`)

### BookingItem

Lien concret `Booking ↔ Luggage ↔ Trip`.

- Gère le multi-bagage
- Alloue la capacité précise

### Transaction

**Source de vérité financière absolue.**

| Type          | Sens                    |
| ------------- | ----------------------- |
| `CHARGE`      | Expéditeur → Plateforme |
| `PAYOUT`      | Plateforme → Voyageur   |
| `REFUND`      | Plateforme → Expéditeur |
| `FEE`         | Commission GP-Valise    |
| `PAYMENT_FEE` | Frais PSP/bancaires     |

### Dispute _(Phase 6)_

Workflow d'arbitrage découplé du système financier.

```
booking.status = EN_LITIGE    ← signal financier (escrow bloqué)
dispute.status                ← workflow arbitrage interne
```

Ces deux statuts évoluent **indépendamment**.

---

## 🔁 Cycle de vie du Booking

```
EN_ATTENTE
  └─► EN_PAIEMENT ──► EXPIREE
          │
          ▼ (CHARGE COMPLETED)
       CONFIRMEE ──────────────────► EN_LITIGE
          │                                │
          ▼ (livraison)                    ▼
        LIVREE ──► EN_LITIGE         REMBOURSEE
          │             │
          │ (escrow 48h)│
          ▼             ▼
        TERMINE     REMBOURSEE
```

### Table des transitions autorisées

| De          | Vers        | Déclencheur                    |
| ----------- | ----------- | ------------------------------ |
| EN_ATTENTE  | EN_PAIEMENT | Paiement initié                |
| EN_PAIEMENT | CONFIRMEE   | CHARGE COMPLETED (webhook)     |
| EN_PAIEMENT | EXPIREE     | payment_expires_at dépassé     |
| CONFIRMEE   | LIVREE      | Voyageur confirme livraison    |
| CONFIRMEE   | EN_LITIGE   | Litige déclaré                 |
| CONFIRMEE   | REMBOURSEE  | REFUND COMPLETED (webhook)     |
| LIVREE      | EN_LITIGE   | Dispute post-livraison         |
| LIVREE      | TERMINE     | PAYOUT COMPLETED (post-escrow) |
| EN_LITIGE   | REMBOURSEE  | REFUND COMPLETED (webhook)     |
| EN_LITIGE   | LIVREE      | Résolution litige → payout     |

---

## 🔒 Règles transverses critiques

### Finance

```
Transaction = source de vérité financière
Ledger      = vérité comptable double-entry

PAYOUT ⊕ REFUND
PAYOUT + FEE + REFUND ≤ CHARGE
SUM(debits) = SUM(credits) par transaction ledger
```

### Escrow

```
LIVREE ≠ payout immédiat
LIVREE = début escrow (48h par défaut)

Payout éligible si :
  booking.status = LIVREE
  AND escrow_releasable_at <= now()
  AND disputed_at IS NULL
  AND charge COMPLETED EXISTS
  AND no REFUND EXISTS
  AND no PAYOUT EXISTS
  AND no FEE EXISTS
```

### Booking final

Un booking en statut final (`TERMINE`, `REMBOURSEE`, `ANNULE`, `EXPIREE`) est **immuable**.
Aucune mutation métier critique, aucune écriture financière supplémentaire.

### Cohérence logistique

```
Booking ↔ BookingItem ↔ Luggage ↔ Trip
```

- Un bagage ne peut pas être réservé plusieurs fois simultanément
- La capacité d'un trip ne doit jamais être dépassée
- L'expiration d'un booking libère les ressources associées
- CONFIRMEE + EN_PAIEMENT non expirés consomment de la capacité

---

## 🔄 Concurrence

| Opération            | Protection requise                         |
| -------------------- | ------------------------------------------ |
| Réservation capacité | `lockForUpdate()` + `DB::transaction()`    |
| Confirmation booking | `lockForUpdate()` + idempotence            |
| Escrow release       | `lockForUpdate()` + guards invariants      |
| Refund / Payout      | `lockForUpdate()` + `DB::transaction()`    |
| Webhook retry        | Idempotence via `event_id`                 |
| Ouverture dispute    | `DB::transaction()` + unique constraint DB |

---

## 🔍 Traçabilité

Chaque opération sensible doit être reconstructible via :

```
correlation_id
  → logs applicatifs
  → webhook_logs
  → transactions
  → ledger_entries
  → audit_logs
  → booking_status_histories
  → dispute_status_histories
```

> Objectif : retrouver **qui a fait quoi, quand, pourquoi**, et avec quel impact financier.

---

## 📈 Roadmap

| Phase | Description                                    | Statut              |
| ----- | ---------------------------------------------- | ------------------- |
| 1     | MVP — Booking, Transaction, Webhook, Audit     | ✅                  |
| 2     | PSP routing Kkiapay/Stripe multi-corridor      | ✅                  |
| 3     | platform_accounts + PostgreSQL + integer units | ✅                  |
| 4     | Escrow 48h + OpenDispute v1                    | ✅                  |
| 5     | Ledger double-entry EUR/XOF                    | ✅                  |
| 6     | Dispute system v2 + Filament Admin             | ✅ (merge en cours) |
| 7     | API publique dispute + notifs + S3             | ⏳                  |

---

## 🧠 Design intention

GP-Valise privilégie **cohérence transactionnelle** avant simplicité.

Chaque choix architectural doit éviter une dette irréversible sur les flux critiques, tout en restant opérable et compréhensible.

```
observable · rejouable · auditable · idempotent · recoverable
```
