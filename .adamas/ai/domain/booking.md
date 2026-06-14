# 🧠 Booking Domain — GP-Valise

---

# 🎯 Objectif

Le Booking est l'entité centrale du système GP-Valise.

Il représente :

- une réservation logistique
- un engagement financier
- un cycle métier transactionnel complet

Il garantit la cohérence entre :

```txt
Expéditeur ↔ Trip ↔ Luggage ↔ Transaction ↔ Destinataire
```

---

# 🧠 Principe fondamental

> Le Booking est le pivot métier.

Le Booking :

- orchestre la logistique
- dépend des événements financiers
- ne calcule jamais la finance lui-même

---

# 🏗️ Modèle Instant Booking

GP-Valise utilise un modèle **Instant Booking** : dès que le paiement est confirmé, la réservation est automatiquement `CONFIRMEE`. Il n'y a pas d'approbation manuelle du voyageur.

Le voyageur définit ses conditions au moment de la publication du trajet (capacité, prix/kg, point de rendez-vous). L'acceptation est implicite dès publication.

---

# 🧱 Structure

## Relations principales

| Relation          | Description                        |
| ----------------- | ---------------------------------- |
| `user_id`         | expéditeur                         |
| `trip_id`         | trajet associé                     |
| `bookingItems`    | bagages réservés                   |
| `transactions`    | flux financiers                    |
| `statusHistories` | historique des transitions         |
| `recipient`       | destinataire désigné par le sender |

## Champs destinataire (obligatoires à la réservation)

| Champ               | Type    | Description                        |
| ------------------- | ------- | ---------------------------------- |
| `recipient_name`    | string  | nom du destinataire                |
| `recipient_phone`   | string  | téléphone du destinataire          |
| `recipient_email`   | string  | email du destinataire              |

## Champs remise / livraison

| Champ               | Type      | Description                                      |
| ------------------- | --------- | ------------------------------------------------ |
| `handed_over_at`    | timestamp | moment de remise physique sender → traveler      |
| `delivery_code`     | string    | code secret 6 chiffres généré à EN_TRANSIT       |
| `delivery_qr_token` | string    | UUID unique généré à EN_TRANSIT                  |
| `cancel_reason`     | string    | raison d'annulation                              |
| `refund_rate`       | integer   | taux remboursement appliqué (100 \| 70 \| 0)     |

---

# 🔁 Cycle de vie

## Flow principal

```txt
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ EN_TRANSIT
→ LIVREE
→ TERMINE
```

---

## États alternatifs

| Statut      | Description                                |
| ----------- | ------------------------------------------ |
| EXPIREE     | paiement non effectué dans le délai        |
| ANNULE      | annulé par sender ou traveler              |
| REMBOURSEE  | refund effectué                            |
| EN_LITIGE   | problème déclaré pendant ou après livraison|

> **Supprimés :** `PENDING_APPROVAL` et `DECLINED_BY_TRAVELER` — obsolètes avec le modèle Instant Booking.

---

# 🔒 Invariants critiques

---

## 1. États finaux

États considérés comme finaux :

```txt
TERMINE
REMBOURSEE
ANNULE
EXPIREE
```

Une fois finalisé :

- aucune mutation métier critique
- aucune transition standard
- aucune écriture financière supplémentaire

---

## 2. Couplage avec Transaction

Le Booking dépend toujours des Transactions pour :

| Action     | Condition        |
| ---------- | ---------------- |
| CONFIRMEE  | CHARGE COMPLETED |
| REMBOURSEE | REFUND COMPLETED |
| TERMINE    | PAYOUT COMPLETED |

---

## 3. Exclusivité financière

```txt
PAYOUT ⊕ REFUND
```

Un booking ne peut jamais avoir payout ET refund.

---

## 4. Cohérence logistique

Toujours garantir :

```txt
Booking ↔ BookingItem ↔ Luggage ↔ Trip ↔ Recipient
```

---

## 5. Escrow consistency

```txt
LIVREE ≠ payout immédiat
```

Un booking livré entre dans une phase escrow.

Le payout devient éligible uniquement après :

- expiration du délai escrow
- absence de dispute
- validation des invariants financiers

---

# ⚠️ Règles métier critiques

---

## Création (Instant Booking)

Un booking est créé directement en `EN_PAIEMENT` si :

- le trip est actif et a la capacité suffisante
- le sender a renseigné : poids, nature du contenu, destinataire (nom + tel + email)
- la capacité est verrouillée (`lockForUpdate`) pendant la création

Il n'y a pas d'étape `PENDING_APPROVAL`. Le paiement doit être effectué immédiatement.

---

## Confirmation

Un booking devient `CONFIRMEE` uniquement si :

- transaction `CHARGE` existe et status = `COMPLETED`
- capacité trip déduite définitivement : `kg_disponibles -= kg_réservés`

À ce moment, les notifications sont envoyées :

**Sender reçoit :**
- nom et téléphone du traveler
- point de rendez-vous remise

**Traveler reçoit :**
- nature et poids du colis
- téléphone du sender
- point de rendez-vous remise

---

## Remise physique (EN_TRANSIT)

Lorsque le sender remet le colis au traveler :

- `handed_over_at` = now()
- `delivery_code` = code secret 6 chiffres généré
- `delivery_qr_token` = UUID unique généré
- Booking → `EN_TRANSIT`
- QR code + code secret envoyés au **destinataire** (email + SMS)

---

## Livraison à destination

Le traveler scanne le QR ou saisit le code secret présenté par le destinataire.

- `delivered_at` = now()
- `escrow_releasable_at` = `delivered_at + 48h`
- Booking → `LIVREE`
- Fonds maintenus en escrow plateforme

---

## Expiration

Un booking `EN_PAIEMENT` devient `EXPIREE` si :

- `payment_expires_at` dépassé

Effets :

- libération de capacité trip
- bagages remis disponibles

---

## Annulation

### Sender annule

| Timing                        | Remboursement  | Compensation traveler |
| ----------------------------- | -------------- | --------------------- |
| > 48h avant départ du trip    | 100%           | 0%                    |
| < 48h avant départ du trip    | 70%            | 30% retenu            |
| No-show (non présentation RDV)| 0%             | 100% payout traveler  |

- `refund_rate` enregistré sur le booking
- capacité trip libérée dans tous les cas

### Traveler annule

- Remboursement 100% sender
- Notation traveler dégradée (-1)
- capacité trip libérée

### Conditions

Annulation possible uniquement depuis :

```txt
EN_PAIEMENT → ANNULE (avant confirmation paiement)
CONFIRMEE   → ANNULE (selon règles timing ci-dessus)
```

Impossible depuis `EN_TRANSIT`, `LIVREE`, `TERMINE`.

---

## Escrow release

Le payout est autorisé uniquement si :

```txt
booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND charge COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS
```

Le scheduler escrow est responsable de la libération.

---

## Litige

Un litige :

- bloque l'escrow
- interdit le payout
- peut mener à refund admin
- nécessite résolution explicite

Ouverture possible depuis `CONFIRMEE`, `EN_TRANSIT` ou `LIVREE`.

---

## Remboursement

Un booking devient `REMBOURSEE` uniquement si :

- transaction `REFUND` existe
- refund status = `COMPLETED`
- aucun payout existant

Le refund est déclenché via :

```txt
HandlePaymentWebhook::handleSuccess() sur refund.completed
```

---

# 🏦 Treasury Ownership

Avant payout :

```txt
fonds détenus par la plateforme
```

Après payout :

```txt
fonds transférés au voyageur
```

Après refund :

```txt
fonds retournés à l'expéditeur
```

Le Booking ne possède jamais directement les fonds.

La trésorerie est orchestrée via :

- Transactions
- PlatformAccounts
- Escrow lifecycle

---

# 🎒 Gestion des bagages

## Cycle Luggage

```txt
EN_ATTENTE
→ RESERVEE
→ EN_TRANSIT
→ LIVREE
```

## Règles

- un bagage ne peut être réservé qu'une seule fois
- dépend toujours d'un Booking
- libéré si booking expire ou annulé

---

# 🚚 Gestion de capacité

## Canonical unit

```txt
grams integer
```

Exemple : `25000 = 25kg`

## Calcul capacité utilisée

Comptabilisés :

- `CONFIRMEE`
- `EN_PAIEMENT` non expiré
- `EN_TRANSIT`

> **Supprimé :** `PENDING_APPROVAL` n'existe plus.

## Règle

```txt
capacity_used ≤ capacity_trip
```

Lock obligatoire (`lockForUpdate`) lors de toute réservation.

---

# 💰 Représentation monétaire

## Canonical unit

```txt
minor integer units
```

Exemple : `1500 = 15.00€`

## Règles

Interdits :

- float
- decimal métier
- calculs approximatifs

Autorisés :

- integer arithmetic
- deterministic computation

---

# 🔒 Concurrence

## Cas critiques

- réservation (déduction capacité)
- confirmation paiement
- expiration
- payout release
- ouverture litige

## Stratégie

- DB transaction obligatoire
- `lockForUpdate()`
- validation avant écriture

---

# 🔁 Idempotence

Doit être garantie pour :

- confirmation paiement
- expiration
- génération QR / code secret
- payout release
- refund
- webhook handling

---

# 🔄 Transitions

Centralisées dans `BookingStatusEnum`.

## Règles

- aucune transition hardcodée
- toujours via Enum
- validation via `canTransitionTo()`

## Table des transitions autorisées

| De → Vers                  | Déclencheur                              |
| -------------------------- | ---------------------------------------- |
| EN_ATTENTE → EN_PAIEMENT   | booking créé, paiement initié            |
| EN_PAIEMENT → CONFIRMEE    | charge COMPLETED (webhook)               |
| EN_PAIEMENT → EXPIREE      | payment_expires_at dépassé               |
| EN_PAIEMENT → ANNULE       | annulation avant paiement                |
| CONFIRMEE → EN_TRANSIT     | remise physique confirmée par traveler   |
| CONFIRMEE → ANNULE         | annulation sender ou traveler            |
| CONFIRMEE → EN_LITIGE      | ouverture litige                         |
| CONFIRMEE → REMBOURSEE     | refund completed                         |
| EN_TRANSIT → LIVREE        | destinataire scanne QR / saisit code     |
| EN_TRANSIT → EN_LITIGE     | dispute pendant transit                  |
| LIVREE → EN_LITIGE         | dispute post-livraison (< 48h escrow)    |
| LIVREE → TERMINE           | payout completed                         |
| EN_LITIGE → REMBOURSEE     | refund admin                             |
| EN_LITIGE → LIVREE         | résolution litige favorable              |

> **Supprimées :** transitions `PENDING_APPROVAL`, `DECLINED_BY_TRAVELER`.

---

# 🧾 Historisation

Chaque changement de statut doit :

- être historisé
- contenir :
  - ancien statut
  - nouveau statut
  - acteur
  - raison éventuelle

---

# 🔍 Observabilité

Un booking doit être traçable via :

- transactions
- audit logs
- webhook logs
- correlation_id

---

# ⚖️ Séparation des responsabilités

Le Booking :

- orchestre
- valide
- coordonne

Le Booking ne :

- calcule pas les montants
- ne calcule pas les fees
- ne connaît pas les PSP
- ne gère pas la trésorerie
- ne génère pas le QR code (délégué à un service dédié)

---

# 🔐 Sécurité

Accès via Policies :

| Acteur     | Accès                 |
| ---------- | --------------------- |
| Expéditeur | ses bookings          |
| Voyageur   | bookings de ses trips |
| Admin      | accès global          |

Le QR token et le code secret ne sont jamais exposés dans les réponses API standard — uniquement via les canaux de notification (email/SMS destinataire).

---

# 🧪 Testabilité

Le domaine doit être testé pour :

- transitions (nouvelle table)
- création instant booking
- déduction capacité avec lock
- confirmation paiement → notifications
- génération QR/code à EN_TRANSIT
- scan QR → LIVREE
- règles annulation (3 cas timing)
- escrow
- payout guards
- refund guards
- idempotence
- cohérence transactionnelle

---

# ⚠️ Anti-patterns interdits

- mutation statut hors Enum
- finance dans Booking
- bypass Transaction
- bypass escrow
- float pour money/weight
- réservation sans lock
- payout immédiat à LIVREE
- coordonnées traveler/sender partagées avant `CONFIRMEE`
- QR/code secret partagé avant `EN_TRANSIT`

---

# 🔮 Extensions futures

- dispute resolution workflow
- escrow configurable par corridor
- reserve balances
- payout batching
- reconciliation engine
- ledger interne
- multi-currency treasury
- volume-based booking
- risk scoring
- re-booking automatique si annulation traveler

---

# 🧠 Résumé exécutif

```txt
Booking = pivot métier transactionnel

- Instant Booking : pas d'approbation manuelle
- paiement immédiat → CONFIRMEE → notifications mutuelles
- remise physique → EN_TRANSIT → QR/code envoyé au destinataire
- scan destinataire → LIVREE → escrow 48h → payout traveler
- annulation : règles timing strictes côté sender
- reste découplé des PSP
- évolue vers un système treasury complet
```