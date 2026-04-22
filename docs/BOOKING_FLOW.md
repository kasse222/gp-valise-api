Oui. Le fond est bon, mais le document peut être **resserré, clarifié et rendu plus crédible techniquement**.

Je te passe une version **refactorisée propre**, plus alignée avec ton code actuel et plus solide en review.

---

## Branche utilisée

`refactor/booking-lifecycle`

## Objectif

Clarifier le flow `Booking` autour de :

- concurrence
- locking
- idempotence
- capacité
- séparation des responsabilités

---

# Version refactorisée

````md
# 🔐 Booking Flow — Concurrency, Locking & Idempotence

## 🎯 Objectif

Mettre en place un flow de réservation robuste capable de :

- gérer la concurrence entre requêtes simultanées,
- éviter le surbooking,
- préserver la cohérence métier,
- rester idempotent en cas de retry HTTP, replay batch ou double exécution.

---

## 📦 1. Périmètre métier couvert

Ce document se concentre sur le sous-flow critique de réservation, paiement et expiration :

```text
EN_PAIEMENT → CONFIRMEE
      ↓
   EXPIREE
```
````

### Règles métier

- un booking est créé en `EN_PAIEMENT`,
- la capacité du trip est bloquée temporairement,
- les valises associées sont réservées pendant la fenêtre de paiement,
- si le paiement aboutit et que les conditions métier restent valides, le booking passe en `CONFIRMEE`,
- si le délai de paiement expire, le booking passe en `EXPIREE`,
- une expiration libère les ressources réservées.

> Le `BookingStatusEnum` couvre un cycle de vie plus large, mais ce document se limite volontairement au cœur du flow paiement / expiration.

---

## ⚖️ 2. Sémantique de capacité

### Règle métier

Un trip considère comme **occupés** :

- les bookings `CONFIRMEE`,
- les bookings `EN_PAIEMENT` dont `payment_expires_at` n’est pas dépassé.

Un trip ne considère pas comme occupés :

- les bookings `EN_PAIEMENT` expirés,
- les bookings finaux non actifs (`ANNULE`, `EXPIREE`, etc.).

### Implémentation

La capacité réservée est calculée dans :

```php
Trip::kgReserved()
```

et exploitée dans :

```php
Trip::canAcceptKg()
```

### Conséquence

Cette règle aligne enfin le calcul de capacité avec le métier réel :

- `EN_PAIEMENT` bloque temporairement les kg,
- `EXPIREE` les libère,
- `CONFIRMEE` les conserve jusqu’à l’étape suivante du lifecycle.

---

## 🔒 3. Concurrence et verrouillage

### Risque

Sans verrouillage transactionnel :

- deux requêtes lisent la même capacité disponible,
- les deux passent la validation,
- les deux réservent,
- la capacité réelle est dépassée.

### Réponse technique

Les opérations critiques sont exécutées dans :

```php
DB::transaction(...)
```

avec verrouillage pessimiste.

### Verrou sur le trip

```php
Trip::query()->lockForUpdate()->findOrFail(...)
```

Ce verrou protège la décision globale sur la capacité.

### Verrou sur les valises

```php
Luggage::query()->whereIn(...)->lockForUpdate()->get()
```

Ce verrou empêche la double réservation concurrente d’une même valise.

### Principe

> Une règle métier lue sans verrou peut devenir fausse immédiatement après la lecture.

Le verrouillage garantit que la validation et l’écriture sont faites sur un état cohérent.

---

## 🔁 4. Idempotence

### Risques réels

Le flow doit rester stable face à :

- retry HTTP,
- double clic utilisateur,
- scheduler rejoué,
- job relancé,
- batch exécuté plusieurs fois.

### Stratégie

#### 1. Relecture en base sous verrou

```php
Booking::query()->lockForUpdate()->find(...)
```

#### 2. Guards métier explicites

Exemples :

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
- état final stable et cohérent.

L’objectif n’est pas seulement d’éviter l’erreur, mais d’éviter de **rejouer les side effects**.

---

## 🧪 5. Couverture de tests

Les tests couvrent notamment :

- la sémantique de capacité,
- le blocage temporaire des kg en `EN_PAIEMENT`,
- la libération des valises à expiration,
- l’idempotence de l’expiration,
- l’absence de double historique métier,
- la stabilité de `expired_at` et `payment_expires_at`,
- le comportement du batch d’expiration.

### Exemple d’idempotence

```php
ExpirePendingBooking::execute($booking);
ExpirePendingBooking::execute($booking);
```

### Attendu

- pas de double expiration,
- pas de double historique,
- pas de double libération de ressources,
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

Cette structure suit l’architecture cible de GP-Valise et permet de limiter la duplication des règles métier.

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

### Bénéfice

Cela évite :

- la duplication de logique dans plusieurs Actions,
- les incohérences de timestamp,
- les transitions partielles ou non traçables.

---

## 🚀 8. Résultat

### Flow métier couvert

- `EN_PAIEMENT -> CONFIRMEE`
- `EN_PAIEMENT -> EXPIREE`

### Concurrence

- verrouillage du `Trip`
- verrouillage des `Luggage`
- validation de capacité à l’intérieur de la transaction

### Idempotence

- relecture DB sous verrou
- guards métier
- replays sans side effects supplémentaires

a faire
"""Optimiser la réservation hot-spot avec une stratégie de capacité atomique ou une sérialisation par trip_id.

### Robustesse

- expiration batch cohérente,
- ressources libérées correctement,
- comportement stable sous retry, replay et charge concurrente.

```

```
