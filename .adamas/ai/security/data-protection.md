# 🔒 Data Protection — GP-Valise

> Règles de protection des données personnelles et de conformité RGPD.

---

## 🎯 Objectif

Définir :

- quelles données personnelles sont stockées
- comment les protéger
- comment répondre aux droits RGPD
- les contraintes liées aux données financières

---

## 🗂️ Cartographie des données personnelles

### Table `users`

| Champ                         | Catégorie    | Sensibilité             | Retention           |
| ----------------------------- | ------------ | ----------------------- | ------------------- |
| `first_name`                  | Identité     | 🟠 Moyen                | Anonymisable        |
| `last_name`                   | Identité     | 🟠 Moyen                | Anonymisable        |
| `email`                       | Contact      | 🔴 Élevé                | Anonymisable        |
| `phone`                       | Contact      | 🔴 Élevé                | Anonymisable        |
| `country`                     | Localisation | 🟡 Faible               | Anonymisable        |
| `password`                    | Auth         | 🔴 Élevé (hashé bcrypt) | Supprimable         |
| `role`                        | Système      | 🟡 Faible               | Conserver           |
| `kyc_passed_at`               | Compliance   | 🔴 Élevé                | **Non supprimable** |
| `verified_user`               | Système      | 🟡 Faible               | Conserver           |
| `email_verified_at`           | Auth         | 🟡 Faible               | Conserver           |
| `phone_verified_at`           | Auth         | 🟡 Faible               | Conserver           |
| `plan_id` / `plan_expires_at` | Commercial   | 🟡 Faible               | Anonymisable        |

### Données **non stockées** en DB (gérées par les PSP)

```
Numéros de carte bancaire    → Stripe (PCI-DSS compliant)
Données mobile money         → Kkiapay
Coordonnées bancaires        → PSP respectif
```

> GP-Valise ne stocke aucune donnée de paiement sensible (PCI-DSS).

### Données indirectes via relations

| Relation       | Données                           | Sensibilité   |
| -------------- | --------------------------------- | ------------- |
| `transactions` | Montants, provider_transaction_id | 🔴 Financier  |
| `payments`     | Statut paiement, provider data    | 🔴 Financier  |
| `audit_logs`   | Actions admin sur l'utilisateur   | 🔴 Compliance |
| `luggages`     | Description objets transportés    | 🟡 Faible     |
| `bookings`     | Trajets, historique               | 🟠 Moyen      |

---

## 🇪🇺 Droits RGPD — Implémentation

### Droit d'accès (Article 15)

L'utilisateur peut demander toutes ses données.

**Périmètre export :** `users` + `bookings` + `transactions` + `payments` + `luggages` + `trips`.

**Exclusions justifiées :**

- `audit_logs` relatifs aux actions admin (données de contrôle interne)
- `webhook_logs` (données techniques PSP)

---

### Droit de suppression (Article 17) — Contrainte financière

```
⚠️ SUPPRESSION COMPLÈTE IMPOSSIBLE si l'utilisateur a des transactions.
```

**Raison :** Les données financières et comptables doivent être conservées pour :

- conformité fiscale (7 ans en France, 10 ans au Maroc, 5 ans au Sénégal)
- intégrité du ledger double-entry
- traçabilité des litiges
- preuve audit

**Stratégie : Anonymisation différée**

```
Suppression demandée par l'utilisateur
  └─► Vérification : transactions existantes ?
       ├─► Non → suppression complète possible
       └─► Oui → anonymisation des données personnelles
                 + conservation des données financières
```

**Champs anonymisés :**

```php
$user->update([
    'first_name'        => 'Anonymized',
    'last_name'         => 'User',
    'email'             => "deleted_{$user->id}@anonymized.gpvalise",
    'phone'             => null,
    'country'           => null,
    'password'          => bcrypt(Str::random(64)),
    'email_verified_at' => null,
    'phone_verified_at' => null,
    // kyc_passed_at : conservé (compliance)
    // transactions : conservées (légal)
    // audit_logs : conservés (compliance)
]);
```

**Après anonymisation :**

- L'utilisateur ne peut plus se connecter
- Ses données personnelles ne sont plus identifiables
- Les données financières restent intactes et cohérentes

---

### Droit de rectification (Article 16)

**Modifiable par l'utilisateur :** `first_name`, `last_name`, `phone`, `country`.

**Non modifiable :** `email` (identifiant unique, lie les transactions) — processus de changement email à définir séparément.

**Non modifiable :** `kyc_passed_at`, `role` — données de compliance/système.

---

### Droit à la portabilité (Article 20)

**Format :** JSON ou CSV.
**Contenu :** Données fournies par l'utilisateur lui-même (pas les données système).

```json
{
  "user": { "first_name": "...", "last_name": "...", "email": "...", ... },
  "bookings": [...],
  "luggages": [...],
  "transactions": [{ "type": "CHARGE", "amount": 1500, "currency": "EUR", ... }]
}
```

---

## 🔐 KYC — Règles spécifiques

```
kyc_passed_at  = timestamp uniquement
Documents KYC  = stockés hors DB (S3 ou service KYC tiers)
```

**Retention `kyc_passed_at` :** Non supprimable même sur demande RGPD.
**Raison :** Preuve de vérification d'identité requise pour les obligations AML/KYC légales.

**Accès aux documents KYC (Phase 7+) :** Accès admin uniquement + audit log obligatoire.

---

## 📜 Durées de rétention

| Données                                 | Durée                                                  | Raison                 |
| --------------------------------------- | ------------------------------------------------------ | ---------------------- |
| Données personnelles (sans transaction) | Jusqu'à suppression demandée                           | RGPD                   |
| Données personnelles (avec transaction) | Anonymisation au premier jour du 8e mois après clôture | Droit comptable FR     |
| `kyc_passed_at`                         | 5-10 ans selon pays                                    | AML/KYC légal          |
| `transactions` + `ledger_entries`       | 7-10 ans                                               | Obligations comptables |
| `audit_logs`                            | 5 ans                                                  | Compliance / litiges   |
| `webhook_logs`                          | 1 an                                                   | Débogage opérationnel  |
| Logs applicatifs                        | 90 jours                                               | Opérationnel           |

---

## 🔒 Sécurité des données

### Mots de passe

```
Hashé bcrypt via Laravel Hash::make()
Jamais stocké en clair
Jamais loggué
```

### Tokens Sanctum

```
Hashés en DB
Expiration configurée
Révocation à la déconnexion
```

### Logs — données interdites

```
❌ email
❌ phone
❌ first_name / last_name
❌ kyc_passed_at
❌ provider_transaction_id complet
❌ token d'authentification
❌ données de paiement PSP
```

### Données en transit

```
HTTPS obligatoire (TLS 1.2+)
Headers de sécurité (HSTS, X-Content-Type-Options)
```

---

## 👥 Rôles et accès aux données personnelles

| Rôle        | Accès                                                     |
| ----------- | --------------------------------------------------------- |
| User        | Ses propres données uniquement                            |
| Traveler    | Données de ses bookings (expéditeur masqué partiellement) |
| Admin       | Accès complet + audit log obligatoire                     |
| Super Admin | Accès complet + audit log obligatoire                     |
| Modérateur  | Lecture limitée, pas de données financières               |

**Filament Admin :** Accès `users` table → champs sensibles masqués par défaut dans les vues (téléphone tronqué, etc.).

---

## 🇸🇳🇲🇦🇫🇷 Contexte multi-juridictions

| Pays            | Régulation applicable  | Durée rétention données financières |
| --------------- | ---------------------- | ----------------------------------- |
| France / Europe | RGPD                   | 7 ans (comptabilité)                |
| Maroc           | Loi 09-08              | 10 ans (archives comptables)        |
| Sénégal         | CDP + directives UEMOA | 5 ans                               |

**Règle pratique MVP :** Appliquer le maximum (10 ans) pour les données financières afin de couvrir toutes les juridictions.

---

## 🚫 Interdits

```
Stocker des données de carte bancaire en DB
Logger email, téléphone, ou données KYC
Supprimer des transactions ou ledger_entries sur demande RGPD
Supprimer des audit_logs
Exporter kyc_passed_at dans un export utilisateur standard
Utiliser les données personnelles à des fins non déclarées
```

---

## 📋 Checklist conformité

- [ ] Politique de confidentialité publiée et à jour
- [ ] Consentement explicite à la création de compte
- [ ] Processus de suppression / anonymisation implémenté
- [ ] Export des données utilisateur fonctionnel
- [ ] Accès KYC audité
- [ ] Logs sans PII
- [ ] Durées de rétention configurées
- [ ] DPO désigné (Phase 7 / ouverture publique)

---

## 🧠 Résumé exécutif

```
Données personnelles → anonymisables sur demande
Données financières  → non supprimables (compliance légale)
KYC                  → non supprimable, accès audité
Cartes bancaires     → jamais stockées (PSP gère)
Logs                 → zéro PII

Suppression demandée + transactions existantes
  → anonymisation (pas suppression)
```
