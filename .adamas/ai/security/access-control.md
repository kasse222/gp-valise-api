# 🔐 Access Control — GP-Valise

> Toute action doit être autorisée **explicitement**. Jamais implicitement.

---

## 🧠 Principe fondamental

```
DENY BY DEFAULT
```

Si une action n'est pas explicitement autorisée → elle est **interdite**.

---

## 👥 Rôles

| Rôle                    | Peut                                                          | Ne peut jamais                            |
| ----------------------- | ------------------------------------------------------------- | ----------------------------------------- |
| **Expéditeur** (SENDER) | Ses bookings, ses bagages, ses transactions, ouvrir un litige | Données d'autrui, actions admin           |
| **Voyageur** (TRAVELER) | Ses trips, bookings de ses trips, répondre à un litige        | Données d'autrui, résoudre un litige      |
| **Admin**               | Tout lire, audit logs, actions critiques, résoudre litiges    | Actions sans audit, actions sans raison   |
| **Super Admin**         | Tout (comme Admin + privilèges système)                       | Actions sans audit                        |
| **Modérateur**          | Lecture limitée                                               | Écriture, résolution, actions financières |

---

## 🧱 Niveaux de contrôle

```
HTTP Layer   →  Controller : $this->authorize('action', $model)
Policy Layer →  vérifie rôle + ownership
Action Layer →  vérifie invariants métier (final, escrow, dispute)
```

> `Policy ≠ sécurité métier`
> La Policy dit **qui peut accéder**. L'Action dit **ce qui est permis**.

### Séparation des responsabilités

| Couche     | Rôle                                |
| ---------- | ----------------------------------- |
| Controller | Déclenche `authorize()`             |
| Policy     | Vérifie rôle et ownership           |
| Action     | Vérifie règles métier et invariants |

---

## 🔒 Règles par domaine

### Booking

| Opération        | Expéditeur           | Voyageur          | Admin |
| ---------------- | -------------------- | ----------------- | ----- |
| Lire son booking | ✅                   | ✅ (de ses trips) | ✅    |
| Créer            | ✅                   | ❌                | ✅    |
| Annuler          | ✅ (si non confirmé) | ❌                | ✅    |
| Marquer livré    | ❌                   | ✅                | ✅    |

### Transaction / Finance

| Opération             | User                 | Admin                  |
| --------------------- | -------------------- | ---------------------- |
| Lire ses transactions | ✅                   | ✅                     |
| Créer CHARGE          | ✅ (son booking)     | ✅                     |
| Refund standard       | ✅ (conditions)      | ✅                     |
| Refund admin override | ❌                   | ✅ + audit obligatoire |
| Payout                | Automatique (escrow) | ✅                     |

### Dispute _(Phase 6)_

| Acteur      | Ouvrir | Écrire message | Changer statut | Résoudre |
| ----------- | ------ | -------------- | -------------- | -------- |
| Expéditeur  | ✅     | ✅             | ❌             | ❌       |
| Voyageur    | ❌     | ✅             | ❌             | ❌       |
| Admin       | ✅     | ✅             | ✅             | ✅       |
| Super Admin | ✅     | ✅             | ✅             | ✅       |
| Modérateur  | ❌     | ❌             | ❌             | ❌       |

**Règles critiques dispute :**

- `RESOLVED` est terminal — aucune modification possible
- Résolution = décision financière + audit log obligatoire
- Un seul litige actif par booking (contrainte DB UNIQUE)

### Audit Logs

| Opération | User                        | Admin          |
| --------- | --------------------------- | -------------- |
| Lire      | ❌                          | ✅             |
| Créer     | ❌ (via Actions uniquement) | Via Actions    |
| Modifier  | ❌                          | ❌ (immutable) |
| Supprimer | ❌                          | ❌ (immutable) |

### Filament Admin Dashboard _(Phase 6)_

Accès restreint aux rôles `ADMIN` et `SUPER_ADMIN` uniquement.
Middleware Horizon (`/horizon`) : même restriction.

---

## ⚠️ Cas critiques

### Refund

```
❌ Si payout existe
❌ Si refund déjà existant
❌ Si utilisateur non propriétaire du booking
```

### Payout (escrow release)

```
❌ Si refund existe
❌ Si payout déjà existant
❌ Si disputed_at IS NOT NULL
❌ Si escrow_releasable_at > now()
```

### Booking

```
❌ Si statut final (TERMINE, REMBOURSEE, ANNULE, EXPIREE)
❌ Si capacité dépassée
```

### Dispute

```
❌ Ouvrir si booking non CONFIRMEE / LIVREE
❌ Écrire message si RESOLVED
❌ Résoudre si rôle insuffisant
```

---

## 🔐 Middleware

```php
Route::middleware(['auth:sanctum', 'kyc'])->group(...)
```

| Middleware      | Rôle                      |
| --------------- | ------------------------- |
| `auth:sanctum`  | Authentification token    |
| `kyc`           | Vérification KYC complète |
| `verified_user` | Email vérifié             |

---

## 🚫 Interdits

```
Logique métier dans Policy
Absence de authorize() sur endpoint sensible
Accès direct Model sans filtre utilisateur
Bypass des Enums pour les transitions
Utilisation de isAdmin() sans Policy
Exposer des IDs internes sans contrôle
Action admin sans audit log
```

---

## 🧪 Tests obligatoires

| Scénario                              | Résultat attendu |
| ------------------------------------- | ---------------- |
| Accès autorisé (cas nominal)          | 200              |
| Accès interdit (autre user)           | 403              |
| Accès admin vs user standard          | Différencié      |
| Accès non authentifié                 | 401              |
| Voyageur ouvre un litige              | 403              |
| Expéditeur résout un litige           | 403              |
| Admin sans raison sur refund override | 422              |

---

## 🧠 Principe clé

```
Policy  = qui peut accéder
Action  = ce qui est autorisé métier
Audit   = preuve de ce qui a été fait

access control + audit = confiance système
```

> Une faille d'accès est plus grave qu'un bug fonctionnel.
