# 🧳 Trip Domain — GP-Valise

## 🎯 Objectif

Un Trip représente un trajet proposé par un voyageur.
Il définit la capacité disponible, le prix au kilo
et les conditions de réservation.

---

## 🧩 Modèle Trip

| Champ         | Type           | Description                     |
| ------------- | -------------- | ------------------------------- |
| user_id       | FK User        | Voyageur propriétaire du trajet |
| departure     | string         | Ville de départ                 |
| destination   | string         | Ville d'arrivée                 |
| date          | datetime       | Date du vol / trajet            |
| capacity      | float          | Capacité totale en kg           |
| status        | TripStatusEnum | Statut du trajet                |
| type_trip     | TripTypeEnum   | Type (avion, bateau, etc.)      |
| flight_number | string null    | Numéro de vol                   |
| price_per_kg  | decimal(10,2)  | Prix par kilogramme             |

---

## 🔒 Règles métier

### Réservabilité

Un Trip est réservable si et seulement si :

- `kgDisponible() > 0`
- `date` est dans le futur
- `status.isReservable() === true`

**Action dédiée** : `CanBeReserved::handle(Trip $trip): bool`

> ⚠️ La méthode `isReservable()` ne doit PAS exister sur le Model Trip.
> Toute vérification de réservabilité passe par `CanBeReserved`.

### Capacité

```
kgReserved()    = SUM(kg_reserved) des BookingItems
                  pour bookings CONFIRMEE
                  + bookings EN_PAIEMENT non expirés

kgDisponible()  = max(0, capacity - kgReserved())
canAcceptKg(kg) = (kgReserved() + kg) <= capacity
```

> ⚠️ `scopeReservable()` doit être aligné avec `kgReserved()`.
> Les deux doivent compter CONFIRMEE + EN_PAIEMENT non expirés.
> Une incohérence entre les deux crée des fantômes de capacité.

---

## 🔁 Statuts Trip

| Statut    | Réservable | Final |
| --------- | ---------- | ----- |
| ACTIVE    | ✅         | ❌    |
| PENDING   | ✅         | ❌    |
| CLOSED    | ❌         | ✅    |
| CANCELLED | ❌         | ✅    |

---

## 🏗️ Architecture

| Composant        | Responsabilité                            |
| ---------------- | ----------------------------------------- |
| Trip Model       | données, relations, agrégats de capacité  |
| CanBeReserved    | décide si un Trip est réservable          |
| TripResource     | sérialise is_reservable via CanBeReserved |
| scopeReservable  | filtre SQL aligné avec kgReserved()       |
| ReserveBooking   | valide et crée la réservation             |
| BookingValidator | valide les bagages avant réservation      |

---

## 🚫 À ne pas faire

- Ne jamais appeler une Action depuis le Model Trip
- Ne jamais calculer is_reservable sans passer par CanBeReserved
- Ne jamais diverger scopeReservable() de kgReserved()
- Ne jamais recalculer la capacité depuis un Booking directement

---

## 🧠 Résumé

> Un Trip expose sa capacité. CanBeReserved décide.
> TripResource sérialise. scopeReservable filtre.
> Ces quatre composants doivent rester cohérents.

```

```
