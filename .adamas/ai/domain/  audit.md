# 🔍 Version enrichie (niveau senior)

````md
# 🔐 Audit Domain — GP-Valise

---

## 🎯 Objectif

Le module Audit garantit la **traçabilité irréfutable** des actions sensibles dans le système.

Il permet de :

- prouver les actions effectuées (compliance) ;
- analyser les incidents ;
- détecter les abus internes ;
- reconstruire l’historique métier et financier ;
- sécuriser les opérations critiques (refund, override, etc.).

---

## 🧠 Principe fondamental

> Un audit log est immuable, vérifiable et chaîné.

Un audit log doit répondre à :

```txt
qui a fait quoi, quand, pourquoi, et sur quoi
```
````

---

## 🧱 Structure d’un AuditLog

### Champs principaux

| Champ            | Description                                        |
| ---------------- | -------------------------------------------------- |
| `id`             | identifiant                                        |
| `actor_id`       | utilisateur ayant déclenché l’action               |
| `action`         | type d’action (ex: admin_refund_override)          |
| `auditable_type` | type d’objet impacté                               |
| `auditable_id`   | id de l’objet                                      |
| `metadata`       | snapshot JSON                                      |
| `reason`         | justification (obligatoire pour actions sensibles) |
| `created_at`     | timestamp                                          |

---

### Champs d’intégrité (niveau avancé)

| Champ            | Description           |
| ---------------- | --------------------- |
| `integrity_hash` | hash du log           |
| `previous_hash`  | hash du log précédent |

---

## 🔗 Chaînage (Integrity Chain)

Chaque log est lié au précédent :

```txt
log(n).previous_hash = log(n-1).integrity_hash
```

Puis :

```txt
integrity_hash = sha256(payload + previous_hash)
```

---

## 🔒 Immutabilité

Un AuditLog ne peut jamais être :

- modifié ;
- supprimé ;
- recalculé.

### Règles strictes

- aucune méthode `update()` autorisée ;
- aucune méthode `delete()` autorisée ;
- aucun `save()` si `$exists = true`.

---

## 🧮 Calcul du hash

Le hash est calculé à partir de :

```txt
actor_id
action
auditable_type
auditable_id
reason
metadata
created_at
previous_hash
```

Objectif :

- détecter toute modification ;
- garantir l’intégrité de la chaîne.

---

## 🔐 Service d’intégrité

Responsable :

```txt
AuditLogIntegrityService
```

Fonctions :

- `seal(log)` → ajoute hash + previous_hash ;
- `verifyLog(log)` → vérifie un log ;
- `verifyChainFrom(id)` → vérifie toute la chaîne.

---

## 🔁 Cycle de création

### Flow

```txt
Action métier → création AuditLog → seal() → persist
```

---

## ⚠️ Règles critiques

- un audit log est créé dans la même transaction DB que l’action critique ;
- aucun log ne doit être créé après coup ;
- aucune action sensible sans audit ;
- `reason` obligatoire pour toute action admin critique.

---

## 🔴 Actions obligatoirement auditables

- admin refund override ;
- modification de statut sensible ;
- intervention admin sur booking ;
- opérations financières exceptionnelles ;
- actions de sécurité (KYC, blocage, etc.).

---

## 🔁 Idempotence

Un audit log ne doit jamais être dupliqué.

Stratégies :

- création dans transaction DB ;
- éviter retry non contrôlé ;
- corrélation avec event_id si async.

---

## 🔍 Observabilité

Un audit log doit être corrélé avec :

- `correlation_id` ;
- logs applicatifs ;
- transactions ;
- webhook_logs.

Objectif :

```txt
retracer une action de bout en bout (API → job → DB)
```

---

## ⚖️ Lien avec la finance

Pour toute action financière critique :

- audit log obligatoire ;
- snapshot financier dans metadata ;
- cohérence avec Transaction.

Exemple :

```json
{
    "charge": 100,
    "fee": 10,
    "payout": 90,
    "refund": 0
}
```

---

## 🔐 Sécurité

- accès admin uniquement (Policy) ;
- lecture audit limitée aux rôles autorisés ;
- aucune donnée sensible exposée publiquement ;
- logs protégés contre modification.

---

## 📊 Consultation

Endpoints admin :

- GET /admin/audit-logs
- GET /admin/audit-logs/{id}

Fonctionnalités :

- filtres (actor, action, date, auditable_id) ;
- pagination ;
- lecture seule.

---

## 🧪 Testabilité

Doit être testé :

- création log ;
- immutabilité ;
- integrity hash ;
- chaîne complète ;
- détection de corruption ;
- audit obligatoire sur actions sensibles.

---

## ⚠️ Anti-patterns interdits

- modifier un audit log ;
- recalculer un hash après coup ;
- créer un audit hors transaction ;
- stocker des données critiques hors metadata ;
- ne pas tracer une action admin ;
- dépendre uniquement des logs Laravel (non fiables).

---

## 🔮 Extensions futures

- signature cryptographique (clé privée) ;
- export sécurisé (CSV, JSON) ;
- preuve légale (compliance) ;
- stockage externe immuable (S3, blockchain) ;
- audit multi-tenant ;
- historisation des lectures d’audit (qui consulte quoi).

---

## 🧠 Résumé exécutif

```txt
AuditLog = preuve

- immuable
- chaîné
- vérifiable
- traçable
```

---

## 🧠 Design intention

Le système d’audit de GP-Valise est conçu pour :

- protéger la plateforme ;
- sécuriser les opérations sensibles ;
- permettre une défense en cas de litige ;
- préparer une conformité réglementaire future.

> Sans audit fiable, un système financier n’est pas crédible.

```

```
