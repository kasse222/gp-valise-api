# 🧠 Domain Overview — GP-Valise

## 🎯 Vision produit

GP-Valise est une plateforme logistique de confiance permettant :

- à un expéditeur d’envoyer un objet, colis ou bagage ;
- via un voyageur tiers ;
- avec un système sécurisé de paiement, suivi, audit et gestion des litiges.

Le produit repose sur trois piliers :

- Logistique : Trip, Luggage, Booking
- Confiance : KYC, Reports, AuditLog, Litiges
- Finance : Payment, Transaction, Refund, Payout, Fee

---

## 👥 Acteurs

### Expéditeur

- crée des bagages ou colis ;
- réserve un trajet ;
- effectue un paiement ;
- suit la livraison ;
- peut ouvrir un litige.

### Voyageur

- publie un trajet ;
- transporte les bagages ou colis ;
- confirme la livraison ;
- reçoit un payout.

### Admin

- supervise la plateforme ;
- gère les litiges ;
- contrôle les fraudes ;
- peut déclencher des actions sensibles sous audit strict.

---

## 🧱 Objets métier principaux

### Trip

Trajet proposé par un voyageur.

Responsabilités principales :

- définir une origine, une destination et éventuellement des étapes ;
- exposer une capacité transportable ;
- porter un statut de disponibilité ;
- servir de support aux réservations.

### Luggage

Objet, colis ou bagage transporté.

Responsabilités principales :

- décrire l’objet transporté ;
- porter un poids, volume, tracking et statut ;
- être rattaché à un expéditeur ;
- suivre le cycle logistique lié au booking.

### Booking

Réservation entre un expéditeur, un ou plusieurs bagages et un trajet.

C’est l’entité centrale du système.

Responsabilités principales :

- relier la logistique et la finance ;
- porter le cycle métier principal ;
- réserver temporairement ou définitivement la capacité ;
- servir de pivot aux transactions, status histories et litiges.

### BookingItem

Liaison concrète entre :

```txt
Booking ↔ Luggage ↔ Trip
```

Responsabilités principales :

- gérer le multi-bagage ;
- allouer une capacité précise ;
- porter les détails de poids/prix liés à une réservation.

### Payment

Vue métier du paiement côté utilisateur.

Responsabilités principales :

- représenter l’état fonctionnel d’un paiement ;
- abstraire le provider ;
- ne pas être la vérité comptable.

### Transaction

Vérité financière atomique.

Responsabilités principales :

- représenter un mouvement financier réel ou simulé ;
- porter les types CHARGE, PAYOUT, REFUND, FEE, PAYMENT_FEE ;
- servir de source de vérité financière.

---

## 💰 Principe financier global

Transaction = source de vérité financière.

Payment représente le workflow utilisateur.
Transaction représente la réalité comptable.

Le Booking ne dépend jamais directement du provider PSP.

---

## 🔁 Cycle de vie global du Booking

### Flow principal

```txt
EN_ATTENTE
→ EN_PAIEMENT
→ CONFIRMEE
→ LIVREE
→ TERMINE
```

### Cas alternatifs

| Statut     | Condition                                        |
| ---------- | ------------------------------------------------ |
| EXPIREE    | Paiement non effectué dans le délai imparti      |
| ANNULE     | Annulation avant finalisation                    |
| REMBOURSEE | Refund completed                                 |
| EN_LITIGE  | Problème déclaré après confirmation ou livraison |
| SUSPENDUE  | Blocage manuel ou système                        |

---

## 🔒 Règles transverses critiques

- Un booking en statut final est immuable.
- Toute transition doit être validée par l’Enum.
- Toute transition sensible doit être historisée.
- Aucune confirmation ne peut exister sans CHARGE completed.
- Un booking ne peut jamais avoir simultanément PAYOUT et REFUND.
- Toute opération financière critique doit être idempotente.
- Toute opération admin sensible doit être auditée.

---

## 🎒 Cohérence logistique

Les entités suivantes doivent rester alignées :

```txt
Booking ↔ BookingItem ↔ Luggage ↔ Trip
```

Invariants :

- un bagage ne peut pas être réservé plusieurs fois simultanément ;
- la capacité d’un trip ne doit jamais être dépassée ;
- l’expiration d’un booking doit libérer les ressources associées ;
- les bookings confirmés et les bookings en paiement non expirés consomment de la capacité.

Toute incohérence entre ces entités est un bug critique.

---

## 🔄 Concurrence

Les opérations critiques doivent utiliser :

- transaction DB ;
- lockForUpdate si risque de concurrence ;
- idempotence sur les flux financiers et async ;
- statut final comme garde-fou contre les doubles traitements.

Cas critiques :

- réservation de capacité ;
- confirmation de paiement ;
- refund ;
- payout ;
- webhook retry ;
- expiration automatique.

---

## 🔍 Traçabilité

Le système doit pouvoir reconstruire l’histoire d’une opération sensible.

Sources de traçabilité :

- transactions ;
- audit logs ;
- webhook logs ;
- booking status histories ;
- correlation_id ;
- logs applicatifs.

Objectif :

```txt
retrouver qui a fait quoi, quand, pourquoi, et avec quel impact métier/financier
```

---

## 🔮 Roadmap v5

### Escrow avancé

- CHARGE retenue par la plateforme ;
- PAYOUT différé après validation ;
- compensation selon litige.

### Litiges structurés

- workflow de dispute ;
- arbitrage admin ;
- preuve via audit log ;
- compensation ou refund contrôlé.

### Multi-pays / multi-devise

- platform accounts ;
- routing financier par devise/pays ;
- compatibilité PSP multiples.

### Ledger futur

- parent_transaction_id ;
- journal comptable complet ;
- traçabilité financière avancée ;
- reporting financier fiable.

---

## 🧠 Design intention

GP-Valise privilégie :

- cohérence métier ;
- traçabilité ;
- auditabilité ;
- sécurité financière ;
- simplicité MVP ;
- évolutivité contrôlée.

Le système doit rester simple tant que le produit est en MVP, mais chaque choix doit éviter une dette irréversible sur les flux critiques.
