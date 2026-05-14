# ✅ Review Checklist — GP-Valise

> Checklist obligatoire avant tout **PR · merge · audit IA**.

---

## 🔁 Couches — séparation des responsabilités

### Controller

- [ ] Une seule Action appelée par endpoint
- [ ] Aucune logique métier
- [ ] FormRequest utilisé pour la validation
- [ ] Policy appliquée (`$this->authorize()`)
- [ ] Exceptions converties en réponses HTTP correctes

### FormRequest

- [ ] Validation HTTP simple uniquement
- [ ] Aucune logique métier
- [ ] Pas de dépendance DB complexe

### Action

- [ ] Un seul use case métier
- [ ] Signature explicite avec types stricts
- [ ] Pas de `request()` ni `Auth::` directs
- [ ] `DB::transaction()` si multi-modèles ou opération financière
- [ ] `lockForUpdate()` si risque de concurrence
- [ ] Exception métier claire sur violation d'invariant
- [ ] Idempotence si risque de double exécution
- [ ] Pas de duplication d'un use case existant

### Policy

- [ ] Autorisation uniquement (rôle + ownership)
- [ ] Aucun effet de bord
- [ ] Aucune logique métier

### Enum

- [ ] Source de vérité des statuts
- [ ] `canTransitionTo()` utilisé pour les transitions
- [ ] Aucune string hardcodée
- [ ] Aucun accès DB

### Model

- [ ] Helpers locaux uniquement (sur ses propres données)
- [ ] Pas d'orchestration multi-modèles
- [ ] Pas de calcul financier
- [ ] Immutabilité respectée (AuditLog, LedgerEntry)

### Service

- [ ] Logique transverse uniquement
- [ ] N'appelle pas une Action
- [ ] Ne remplace pas un use case

### Job

- [ ] Appelle une Action (pas de logique métier)
- [ ] Retry/backoff configuré
- [ ] `correlation_id` transmis et conservé
- [ ] Logging + alerting Slack si échec critique

---

## 💰 Finance

- [ ] Aucun calcul financier hors `TransactionAmountCalculator`
- [ ] `Transaction` = source de vérité (pas Booking, pas Payment)
- [ ] `PAYOUT ⊕ REFUND` respecté
- [ ] Aucun double payout / double refund / double fee
- [ ] `CHARGE` obligatoire avant `CONFIRMEE`
- [ ] `canBeRefunded()` vérifié avant toute création de REFUND
- [ ] Montants en **integer minor units** (float interdit)
- [ ] Montants persistés à la création — jamais recalculés

---

## 🔒 Escrow

- [ ] Pas de payout immédiat à `LIVREE`
- [ ] `escrow_releasable_at` calculé à la livraison
- [ ] Guard `disputed_at IS NULL` vérifié avant release
- [ ] Guard `escrow_releasable_at <= now()` vérifié
- [ ] `ReleaseEscrowBatch` — idempotence garantie

---

## 📋 Dispute

- [ ] Séparation `booking.status` / `dispute.status` respectée
- [ ] Une seule dispute active par booking (unique constraint)
- [ ] `RESOLVED` est terminal — aucune transition possible
- [ ] Résolution = décision financière + audit log obligatoire
- [ ] Accès rôles respecté (expéditeur ≠ résolution)

---

## 🏦 Ledger

- [ ] `SUM(debits) = SUM(credits)` par transaction
- [ ] Écriture dans la même `DB::transaction()` que la Transaction financière
- [ ] Aucune `LedgerEntry` modifiée après création
- [ ] Pas de balance matérialisée sur `ledger_accounts`
- [ ] Guard `hasExistingEntries` vérifié (idempotence)

---

## 🔌 PSP isolation

- [ ] Payload PSP brut jamais dans le domaine
- [ ] Webhook normalisé via `WebhookProcessor` → `PaymentEventData`
- [ ] Provider sélectionné via `PaymentProviderResolver`
- [ ] Aucun PSP concret hardcodé dans Actions/Controllers/Models
- [ ] `FakePaymentProvider` absent de la config production

---

## 🔁 Idempotence

- [ ] Webhook idempotent (`event_id` UNIQUE + `lockForUpdate()`)
- [ ] Payout idempotent (guards `TransactionEligibilityService`)
- [ ] Refund idempotent (guards `TransactionEligibilityService`)
- [ ] Ledger entries idempotentes (`hasExistingEntries`)

---

## 🔐 Audit

- [ ] Audit log créé pour toutes les actions admin critiques
- [ ] `seal()` appelé **immédiatement après `create()`** dans la même `DB::transaction()`
- [ ] Aucun `save()` sur `AuditLog` existant hors de `seal()`
- [ ] `integrity_hash` + `previous_hash` présents
- [ ] Audit log lié à la résolution de dispute

---

## 🔐 Sécurité

- [ ] Policy appliquée sur tous les endpoints sensibles
- [ ] Pas de fuite de données (filtre utilisateur)
- [ ] Validation correcte des entrées
- [ ] Aucun bypass d'autorisation

---

## 🔍 Observabilité

- [ ] `correlation_id` présent : HTTP · logs · jobs · webhooks
- [ ] Logs structurés avec contexte
- [ ] Aucune donnée sensible loggée

---

## ⚡ Performance

- [ ] Pas de N+1 (`with()` utilisé si nécessaire)
- [ ] Pagination sur les listes
- [ ] Index DB cohérents avec les filtres

---

## 🧪 Testabilité

- [ ] Tests Action complets (nominal + erreurs + edge cases)
- [ ] Idempotence testée
- [ ] Guards escrow testés
- [ ] Guards dispute testés
- [ ] Transitions Enum testées
- [ ] `SUM(debits) = SUM(credits)` testé sur les flows ledger
- [ ] Aucun `dd()` / `dump()` restant

### Tests de référence

| Sujet                                          | Fichier                      |
| ---------------------------------------------- | ---------------------------- |
| Idempotence webhook                            | `HandlePaymentWebhookTest`   |
| Alignement `scopeReservable` / `gramsReserved` | `CapacitySemanticsTest`      |
| Chaîne d'intégrité audit                       | `AdminRefundTransactionTest` |
| Bug C3 — `CONFIRMEE → REMBOURSEE`              | `HandlePaymentWebhookTest`   |
| Guards escrow                                  | `ReleaseEscrowBatchTest`     |
| Invariants dispute                             | `ResolveDisputeTest`         |

---

## 🧪 Avant merge

- [ ] Tous les tests passent (`make test`)
- [ ] Laravel Pint OK
- [ ] PHPStan / Larastan OK
- [ ] Pas de régression visible
- [ ] Branche propre, commits atomiques
- [ ] `decision-log.md` mis à jour si décision importante

---

## 🚦 Gravité des problèmes

| Niveau          | Description                                | Action                |
| --------------- | ------------------------------------------ | --------------------- |
| 🔴 Critique     | Bug métier / finance / escrow / sécurité   | Bloquer immédiatement |
| 🟠 Important    | Mauvaise architecture / dette bloquante    | PR refusée            |
| 🟡 Amélioration | Lisibilité / performance / tests manquants | Commentaire           |

---

## 📌 Règle de décision

1. Violation `.adamas` ?
2. Impact réel sur le système ?
3. Problème métier ou technique ?
4. La correction simplifie-t-elle ?

---

## 🚫 Bloquants absolus

```
Code sans audit préalable
Statuts en string (Enum obligatoire)
Logique métier dans Controller ou Policy
Idempotence ignorée sur flux financier
Concurrence ignorée sur opération critique
Audit absent sur action admin sensible
Float pour money ou poids
Payout immédiat à LIVREE
```

---

> Si la checklist n'est pas respectée, le code n'est pas prêt.
