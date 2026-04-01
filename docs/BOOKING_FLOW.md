# 🔐 Booking Flow — Concurrency, Locking & Idempotence

## 🎯 Objectif

Mettre en place un système de réservation robuste capable de :

- gérer la concurrence entre requêtes simultanées,
- éviter le surbooking,
- garantir la cohérence métier,
- rester idempotent en cas de retry, replay batch ou double exécution.

---

## 📦 1. Sous-flow métier couvert par ce document

Ce document se concentre sur le sous-flow critique du paiement et de l’expiration d’un booking :

```text
EN_PAIEMENT → CONFIRMEE
      ↓
   EXPIREE
```

### Règles métier

- un booking est créé en `EN_PAIEMENT`,
- la capacité est bloquée temporairement,
- si le paiement aboutit, le booking passe en `CONFIRMEE`,
- si le délai de paiement est dépassé, le booking passe en `EXPIREE`,
- une expiration libère les ressources réservées.

> Le modèle global de `BookingStatusEnum` est plus riche, mais ce document cible le cœur du flow paiement/expiration.

---

## ⚖️ 2. Capacity semantics

### Règle métier

Un trip considère comme occupés :

- les bookings `CONFIRMEE`,
- les bookings `EN_PAIEMENT` dont `payment_expires_at` n’est pas dépassé.

Ne comptent pas :

- les bookings `EN_PAIEMENT` expirés,
- les bookings finaux non actifs (`ANNULE`, `REFUSE`, `EXPIREE`, etc.).

### Implémentation

La capacité réservée est calculée dans :

```php
Trip::kgReserved()
```

et utilisée dans :

```php
Trip::canAcceptKg()
```

Cette règle aligne enfin le calcul de capacité avec le métier : `EN_PAIEMENT` bloque temporairement les kg, `EXPIREE` les libère.

---

## 🔒 3. Concurrence & locking

### Risque

Sans verrouillage transactionnel :

- deux requêtes lisent la même capacité disponible,
- les deux passent la validation,
- la capacité est dépassée.

### Réponse technique

Les opérations critiques sont exécutées dans :

```php
DB::transaction(...)
```

avec verrouillage pessimiste :

#### verrou sur le Trip

```php
->lockForUpdate()
```

Il protège la décision sur la capacité globale.

#### verrou sur les Luggages

```php
->whereIn(...)->lockForUpdate()
```

Il empêche la double réservation concurrente d’une même valise.

### Principe

> Une règle métier lue sans verrou peut devenir fausse immédiatement après lecture.

---

## 🔁 4. Idempotence

### Risques réels

- retry HTTP,
- double clic utilisateur,
- batch rejoué,
- scheduler exécuté plusieurs fois,
- job relancé après incident.

### Stratégie

#### 1. Relecture en base sous transaction

```php
Booking::query()->lockForUpdate()->find(...)
```

#### 2. Guards métier

```php
if ($booking->status !== BookingStatusEnum::EN_PAIEMENT) {
    return $booking;
}
```

```php
if ($booking->payment_expires_at === null || $booking->payment_expires_at->isFuture()) {
    return $booking;
}
```

```php
if (! $booking->canTransitionTo(BookingStatusEnum::EXPIREE)) {
    return $booking;
}
```

### Résultat attendu

- 1re exécution : effet métier réel,
- 2e exécution : aucun effet secondaire supplémentaire,
- état final stable.

L’objectif n’est pas seulement d’éviter l’erreur, mais d’éviter de **rejouer les side effects**.

---

## 🧪 5. Tests couverts

Les tests vérifient notamment :

- la bonne sémantique de capacité,
- le blocage des kg en `EN_PAIEMENT` non expiré,
- la libération des valises à expiration,
- l’absence de double historique lors d’une seconde expiration,
- la stabilité de `expired_at` et de `payment_expires_at`,
- la cohérence du flow batch d’expiration.

Exemple d’idempotence :

```php
ExpirePendingBooking::execute($booking);
ExpirePendingBooking::execute($booking);
```

Attendu :

- pas de double expiration,
- pas de double historique,
- pas de side effects répétés.

---

## 🧠 6. Architecture retenue

Pattern principal :

```text
Controller → Action → Model / Enum
```

### Répartition des responsabilités

- `Controller` → orchestration HTTP
- `Action` → use case métier
- `Model` → orchestration d’état et relations métier
- `Enum` → états, transitions et comportements métier purs
- `Policy` → contrôle d’accès

Cette structure suit l’architecture cible du projet GP-Valise.

---

## 🔧 7. Centralisation des effets métier

Les timestamps liés aux transitions sont centralisés dans :

```php
Booking::transitionTo()
```

Exemple pour `EXPIREE` :

```php
if ($newStatus === BookingStatusEnum::EXPIREE) {
    $this->expired_at = now();
    $this->payment_expires_at = null;
}
```

Cela évite de dupliquer la logique dans plusieurs Actions et garantit un changement d’état cohérent.

---

## 🚀 8. Résultat

### Booking flow

- `EN_PAIEMENT -> CONFIRMEE`
- `EN_PAIEMENT -> EXPIREE`

### Concurrence

- verrouillage sur `Trip`
- verrouillage sur `Luggage`
- validation de capacité dans la transaction

### Idempotence

- relecture DB sous verrou
- guards métier
- replays sans effets secondaires supplémentaires

### Robustesse

- batch d’expiration cohérent,
- ressources libérées correctement,
- comportement stable sous retry et sous charge.

---
