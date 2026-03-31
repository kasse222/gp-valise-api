# 🔐 Booking Flow — Concurrency, Locking & Idempotence

## 🎯 Objectif

Mettre en place un système de réservation robuste capable de :

- gérer la concurrence (multi-requests simultanées)
- éviter le surbooking
- garantir la cohérence métier
- être idempotent (safe en cas de retry)

---

# 📦 1. Booking Lifecycle (Flow métier)

Le cycle de vie d’un booking est basé sur un modèle orienté paiement :

```

EN_PAIEMENT → CONFIRMEE
↓
EXPIREE

```

## Règles métier

- Un booking est créé directement en `EN_PAIEMENT`
- La capacité est bloquée temporairement
- Si paiement réussi → `CONFIRMEE`
- Si délai dépassé → `EXPIREE`
- En cas d’expiration → libération des ressources (valises)

---

# ⚖️ 2. Capacity Semantics

## Règle

Un trip considère comme "occupé" :

- `CONFIRMEE` ✔️
- `EN_PAIEMENT` non expiré ✔️

Ne compte pas :

- `EN_PAIEMENT` expiré ❌
- `ANNULE`, `REFUSE`, etc. ❌

## Implémentation

Méthode clé :

```php
Trip::kgReserved()
```

👉 utilisée dans :

```php
Trip::canAcceptKg()
```

---

# 🔒 3. Concurrence & Locking

## Problème

Sans protection :

- 2 requêtes lisent la même capacité
- les deux passent
- surbooking

## Solution

### Transaction + verrouillage

```php
DB::transaction(...)
```

### Lock sur Trip

```php
->lockForUpdate()
```

👉 protège la capacité globale

### Lock sur Luggage

```php
->whereIn(...)->lockForUpdate()
```

👉 empêche double réservation de la même valise

---

## 🧠 Principe

> Une règle métier lue sans verrou peut devenir fausse immédiatement après.

---

# 🔁 4. Idempotence

## Problème

- retry HTTP
- double appel API
- cron / batch exécuté plusieurs fois

👉 sans idempotence → incohérences

---

## Solution

### 1. Relecture DB sous transaction

```php
Booking::lockForUpdate()->find(...)
```

### 2. Guards métier

```php
if ($booking->status !== EN_PAIEMENT) return;
```

```php
if ($booking->payment_expires_at->isFuture()) return;
```

```php
if (! $booking->canTransitionTo(EXPIREE)) return;
```

---

## Résultat

- exécution 1 → effet réel
- exécution 2 → aucun effet secondaire

👉 système stable

---

# 🧪 5. Tests

## Coverage

- Capacity semantics
- Concurrency-safe reservation
- Expiration logic
- Idempotence

## Exemple clé

```php
ExpirePendingBooking::execute($booking);
ExpirePendingBooking::execute($booking);
```

👉 vérifie :

- pas de double expiration
- pas de double historique
- timestamps inchangés

---

# 🧠 6. Architecture

## Pattern utilisé

```
Controller → Action → Model
```

### Rôles

- Controller → orchestration HTTP
- Action → logique métier
- Model → règles métier + transitions
- Enum → états + transitions
- Policy → accès

---

# 🔧 7. transitionTo()

Centralisation des effets métier :

```php
if ($newStatus === BookingStatusEnum::EXPIREE) {
    $this->expired_at = now();
    $this->payment_expires_at = null;
}
```

👉 évite duplication dans les actions

---

# 🚀 8. Résultat final

## ✅ Booking Flow

- EN_PAIEMENT → CONFIRMEE / EXPIREE

## ✅ Concurrence

- lockForUpdate() sur Trip
- lockForUpdate() sur Luggage

## ✅ Idempotence

- guards métier
- relecture DB
- test idempotent

## ✅ Tests

- 136+ tests passés
- couverture métier solide

---

# 💡 9. Points clés à retenir

- la concurrence casse les systèmes naïfs
- les transactions doivent encapsuler les décisions métier
- l’idempotence est obligatoire en production
- la logique métier doit être centralisée

---

# 🔮 10. Extensions futures

- paiement réel (Stripe)
- idempotency key (API)
- queue + retry safe
- monitoring / logs métier
- optimisation SQL (index)

---

# 🧠 Conclusion

Ce module n’est plus un CRUD.

👉 C’est un système métier robuste :

- cohérent
- sécurisé
- scalable

```



---

# 🧠 Mini conseil (important)

Ce document :

👉 tu peux le réutiliser pour :

- README GitHub
- LinkedIn
- entretien technique

---
```
