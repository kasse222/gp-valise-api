# 🔐 Access Control — GP-Valise

## 🎯 Objectif

Définir les règles de contrôle d’accès du système afin de garantir :

- sécurité des données
- isolation des utilisateurs
- protection des opérations sensibles (finance, admin)
- conformité des actions métier

> Toute action doit être autorisée explicitement, jamais implicitement.

---

## 🧠 Principe fondamental

```txt
DENY BY DEFAULT
```

Si une action n’est pas explicitement autorisée → elle est interdite.

---

## 👥 Rôles

### User (expéditeur / voyageur)

Peut :

- accéder à ses propres données
- créer et gérer ses bookings
- consulter ses transactions

Ne peut jamais :

- accéder aux données d’un autre utilisateur
- effectuer des actions admin
- modifier des transactions finalisées

---

### Admin

Peut :

- accéder à toutes les données
- consulter les audit logs
- déclencher des actions critiques (refund override)

Contraintes :

- toute action sensible doit être auditée
- aucune action silencieuse autorisée

---

## 🧱 Niveaux de contrôle

### 1. HTTP Layer (Controller)

Responsable de :

```php
$this->authorize('view', $booking);
```

→ Toujours présent sur endpoints sensibles

---

### 2. Policy Layer

Responsable de :

- vérifier l’accès à une ressource
- vérifier le rôle utilisateur
- vérifier la propriété (ownership)

Exemple :

```php
public function view(User $user, Booking $booking): bool
{
    return $user->id === $booking->user_id;
}
```

---

### 3. Action Layer (sécurité métier)

Responsable de :

- vérifier les règles métier critiques
- bloquer les opérations interdites même si Policy OK

Exemple :

```php
if ($booking->isFinal()) {
    throw new DomainException('Booking finalisé');
}
```

👉 Important :

```txt
Policy ≠ sécurité métier
```

---

## 🔁 Séparation des responsabilités

| Couche     | Rôle                      |
| ---------- | ------------------------- |
| Controller | déclenche authorize()     |
| Policy     | vérifie accès utilisateur |
| Action     | vérifie règles métier     |

---

## 🔒 Règles critiques

### Ownership

Un utilisateur ne peut accéder qu’à :

```txt
ses propres données
```

---

### Isolation

Jamais de fuite de données :

- pas d’accès indirect via relations
- pas de filtrage oublié
- pas de query globale non sécurisée

---

### Finance

Opérations protégées :

- refund
- payout
- charge

Conditions :

- ownership vérifié
- statut valide
- invariants respectés

---

### Admin override

Conditions obligatoires :

- rôle admin
- raison obligatoire
- audit log obligatoire
- invariants financiers respectés

---

## ⚠️ Cas critiques

### Refund

- interdit si payout existe
- interdit si déjà refund
- interdit si utilisateur non propriétaire

---

### Payout

- interdit si refund existe
- interdit si déjà payout

---

### Booking

- interdit si finalisé
- interdit si capacité dépassée

---

## 🚫 Interdits

- logique métier dans Policy
- absence de `$this->authorize()` sur endpoint sensible
- accès direct Model sans filtre utilisateur
- bypass des Enums
- utilisation de `isAdmin()` sans Policy
- exposer des IDs sans contrôle

---

## 🔐 Middleware

Utilisés pour :

- auth (`auth:sanctum`)
- KYC (`kyc`)
- user verified (`verified_user`)

Exemple :

```php
Route::middleware(['auth:sanctum', 'kyc'])->group(...)
```

---

## 🔍 Tests attendus

### Obligatoires

- accès autorisé (cas nominal)
- accès interdit (autre user)
- accès admin vs user
- accès non authentifié

---

### Exemple

```php
it('forbids access to another user booking', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $userA->id,
    ]);

    Sanctum::actingAs($userB);

    $this->getJson("/api/bookings/{$booking->id}")
        ->assertForbidden();
});
```

---

## 🔗 Lien avec audit

Toute action sensible doit être traçable :

- admin refund
- override
- accès critique

```txt
access control + audit = confiance système
```

---

## 🧠 Résumé exécutif

```txt
Policy = qui peut accéder
Action = ce qui est autorisé métier
```

Les deux sont nécessaires.

---

## 🧠 Design intention

Le système doit être :

- sécurisé par défaut
- explicite
- testable
- sans ambiguïté

---

## 🔥 Niveau attendu (senior)

Tu dois être capable de répondre :

👉 “Est-ce qu’un user peut casser ton système en appelant ton API ?”

Si oui → bug critique
Si non → bon design

---

## 🧠 Principe clé

> Une faille d’accès est plus grave qu’un bug fonctionnel.

```

```
