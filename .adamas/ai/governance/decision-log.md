Voici une version corrigée et enrichie pour :

```txt
.adamas/ai/governance/decision-log.md
```

J’ai surtout corrigé la décision `.adamas`, devenue obsolète avec ta nouvelle structure.

````md
# 🧠 Decision Log — GP-Valise

## 🎯 Objectif

Ce fichier trace les décisions techniques et métier importantes du projet.

Il permet de :

- comprendre pourquoi un choix a été fait ;
- éviter de redébattre les mêmes sujets ;
- aligner les décisions futures avec les choix passés ;
- guider l’IA dans ses recommandations ;
- garder une mémoire technique exploitable en entretien.

> Une décision documentée vaut mieux qu’un bon code oublié.

---

## 🧾 Format d’une décision

```txt
## [DATE] — Titre court

### Contexte
Pourquoi la décision est nécessaire.

### Décision
Choix effectué.

### Alternatives considérées
Options rejetées et pourquoi.

### Conséquences
Impacts techniques / métier.

### Statut
- ✅ actif
- 🟡 en cours
- 🔴 à revoir
- ⬛ obsolète
```
````

---

# 📌 Décisions actives

---

## [2026-04] — Transaction = source de vérité financière

### Contexte

Besoin d’une cohérence financière forte avec paiements async, webhooks, retry et idempotence.

### Décision

La `Transaction` est la seule source de vérité financière.

- `Payment` = vue métier / workflow utilisateur
- `Transaction` = réalité comptable atomique

### Alternatives considérées

- Stocker l’état financier dans `Booking` ❌ : couplage fort, faible traçabilité.
- Dépendre du provider comme source de vérité ❌ : fragile, async non maîtrisé.

### Conséquences

- forte traçabilité financière ;
- découplage total du provider ;
- complexité légèrement plus élevée.

### Statut

✅ actif

---

## [2026-04] — Séparation FEE / PAYMENT_FEE

### Contexte

Besoin de distinguer le revenu plateforme du coût PSP pour calculer une rentabilité réelle.

### Décision

- `FEE` = revenu GP-Valise ;
- `PAYMENT_FEE` = coût externe PSP / banque.

### Alternatives considérées

- Fusionner les deux ❌ : perte de visibilité financière.
- Ignorer les frais PSP en MVP ❌ : rentabilité faussée.

### Conséquences

- modèle financier lisible ;
- prêt pour multi-pays / multi-PSP ;
- calcul réel du profit net : `profit_net = FEE - PAYMENT_FEE`.

### Statut

✅ actif

---

## [2026-04] — FEE dynamique via FeeCalculator

### Contexte

La commission peut dépendre du pays, du type utilisateur, du volume ou de règles futures.

### Décision

Introduire un `FeeCalculator` dédié, isolé des Actions.

Le `FeeCalculator` résout le taux.
Le `TransactionAmountCalculator` applique le calcul.

### Alternatives considérées

- Hardcoder le taux dans l’Action ❌.
- Config simple globale uniquement ❌.

### Conséquences

- extensibilité forte ;
- centralisation de la logique de taux ;
- testabilité améliorée.

### Statut

🟡 en cours — interface définie, implémentation à faire

---

## [2026-04] — Montants financiers persistés à la création

### Contexte

Recalculer les montants à la volée est dangereux : un changement de taux rendrait les transactions passées incohérentes.

### Décision

Les montants calculés sont persistés en base au moment de la création.

Ils ne sont jamais recalculés après coup.

### Alternatives considérées

- Recalcul à la volée ❌.
- Calcul uniquement à l’affichage ❌.

### Conséquences

- vérité historique garantie ;
- audit possible ;
- contestation possible ;
- `TransactionAmountCalculator` sert à créer, pas à reconstruire l’historique.

### Statut

✅ actif

---

## [2026-04] — PAYMENT_FEE persistée dès le MVP

### Contexte

Les frais PSP impactent la rentabilité réelle. Les ignorer en MVP créerait une dette de données.

### Décision

`PAYMENT_FEE` est persistée dès le MVP, même si estimée.

- Source idéale : webhook PSP.
- Fallback MVP : configuration locale.

### Alternatives considérées

- Ignorer en MVP ❌.
- Calcul externe non persisté ❌.

### Conséquences

- meilleure visibilité financière ;
- prêt pour PSP réel ;
- pas de migration de données douloureuse.

### Statut

✅ actif

---

## [2026-04] — FEE créée au moment du PAYOUT

### Contexte

Éviter de prélever une commission sur un booking annulé, expiré ou remboursé avant livraison.

### Décision

La `FEE` est créée lors du `PAYOUT`, pas à la `CHARGE` ni à la confirmation.

### Alternatives considérées

- Créer à la `CHARGE` ❌.
- Créer à la confirmation ❌.

### Conséquences

- meilleure cohérence escrow ;
- refund v1 simplifié ;
- commission uniquement sur livraison effective.

### Statut

✅ actif

---

## [2026-04] — Refund post-livraison : admin override avec audit

### Contexte

Un refund après livraison est exceptionnel, mais peut être nécessaire en cas de litige avéré.

### Décision

Autoriser un refund admin override uniquement si :

- admin uniquement ;
- booking `LIVREE` ou `EN_LITIGE` ;
- `CHARGE` completed ;
- aucun `REFUND` existant ;
- aucun `PAYOUT` existant ;
- raison obligatoire ;
- audit log obligatoire dans la même transaction DB.

Action dédiée : `AdminRefundTransaction`.

### Alternatives considérées

- Blocage total ❌ : faiblesse produit en cas de litige réel.
- Endpoint admin sans contrainte ❌ : risque financier majeur.

### Conséquences

- invariant `PAYOUT ⊕ REFUND` maintenu ;
- traçabilité complète ;
- défense possible en cas de contestation.

### Statut

✅ actif

---

## [2026-04] — Refund limité en MVP

### Contexte

Le refund partiel, multi-refund et compensation post-payout sont complexes.

### Décision

MVP :

- refund total uniquement ;
- un seul refund par booking ;
- `PAYMENT_FEE` non remboursable ;
- `refund_possible = CHARGE - FEE`.

### Alternatives considérées

- Refund partiel ❌.
- Refund automatique post-livraison ❌.

### Conséquences

- système stable ;
- moins de risque ;
- base saine pour v5.

### Statut

✅ actif

---

## [2026-04] — Webhook idempotent via event_id

### Contexte

Les providers peuvent envoyer plusieurs fois le même événement.

### Décision

Idempotence basée sur `event_id` :

- stocké dans `webhook_logs` ;
- vérifié avant traitement ;
- combiné avec `lockForUpdate()`.

### Alternatives considérées

- Pas d’idempotence ❌.
- Idempotence faible ❌.

### Conséquences

- évite double refund / double traitement ;
- robustesse async ;
- sécurité financière.

### Statut

✅ actif

---

## [2026-04] — Booking indépendant du provider PSP

### Contexte

Le provider de paiement peut changer. Le `Booking` ne doit pas dépendre de Stripe, CMI, Wave ou autre.

### Décision

Le `Booking` ne référence jamais directement un provider.
Le lien se fait via `Transaction`.

### Alternatives considérées

- Lier Booking au provider ❌.

### Conséquences

- découplage ;
- multi-PSP ready ;
- meilleure testabilité.

### Statut

✅ actif

---

## [2026-05] — Architecture .adamas v2 modulaire

### Contexte

La structure initiale `.adamas/ai/context` et `.adamas/ai/capabilities` devenait trop floue avec la montée en maturité du projet.

### Décision

Adopter une structure v2 séparant clairement :

```txt
.adamas/ai/
├── core/              → prompt système, contraintes IA
├── domain/            → vérité métier
├── engineering/       → règles de code, review, git
├── governance/        → méthodologie, décisions
├── observability/     → correlation_id, logging, monitoring
└── security/          → règles sécurité et finance sensible
```

### Alternatives considérées

- Garder `context/` ❌ : trop vague.
- Garder `capabilities/` pour coding/review ❌ : moins explicite qu’engineering.
- Fichier unique ❌ : non maintenable.

### Conséquences

- meilleure lisibilité ;
- IA mieux guidée ;
- documentation plus proche d’un vrai repo d’équipe ;
- séparation claire entre métier, méthode, code, sécurité et observabilité.

### Statut

✅ actif

---

## [2026-05] — AuditLog append-only

### Contexte

Les actions sensibles admin doivent être consultables et défendables en cas de litige.

### Décision

`AuditLog` est append-only :

- pas d’`updated_at` ;
- update/delete interdits ;
- suppression d’un actor/user ne supprime pas les logs ;
- consultation admin uniquement.

### Alternatives considérées

- AuditLog modifiable ❌.
- AuditLog supprimable avec user ❌.

### Conséquences

- historique durable ;
- meilleure conformité ;
- traçabilité renforcée.

### Statut

✅ actif

---

## [2026-05] — AuditLog integrity hash chain

### Contexte

Un audit append-only protège contre les modifications applicatives, mais pas contre une modification directe en base.

### Décision

Ajouter une chaîne d’intégrité :

- `integrity_hash` ;
- `previous_hash` ;
- `AuditLogIntegrityService`.

Chaque hash couvre les champs critiques du log et le hash précédent.

### Alternatives considérées

- Hash simple par ligne ❌ : détecte une modification isolée mais pas une rupture de chaîne.
- Pas de hash ❌ : insuffisant pour un système fintech-like.

### Conséquences

- détection de modification frauduleuse ;
- preuve technique plus forte ;
- base pour signature cryptographique future.

### Statut

✅ actif

---

## [2026-05] — Correlation ID pour observabilité

### Contexte

Les flux async rendent les incidents difficiles à reconstruire sans identifiant de corrélation.

### Décision

Introduire un `correlation_id` propagé dans :

- requêtes HTTP ;
- réponses API ;
- logs Laravel ;
- jobs ;
- webhooks.

### Alternatives considérées

- Logs classiques sans corrélation ❌.
- Dépendre uniquement des IDs métiers ❌ : insuffisant pour tracer un flux complet.

### Conséquences

- meilleure investigation incident ;
- débogage plus rapide ;
- préparation à une observabilité distribuée ;
- démo technique forte pour employabilité.

### Statut

🟡 en cours — DB propagation à finaliser

---

## [2026-04] — platform_accounts : migration progressive

### Contexte

Besoin futur de multi-comptes bancaires : Maroc, Sénégal, Europe, PSP multiples.

### Décision

Migration progressive :

1. MVP : user technique plateforme par devise ;
2. ensuite : table `platform_accounts` avec `user_id` nullable ;
3. futur : `transactions.user_id` → `platform_account_id`.

### Alternatives considérées

- Migration directe ❌.
- Ignorer multi-comptes ❌.

### Conséquences

- faible risque immédiat ;
- base multi-pays posée ;
- compatible roadmap v5.

### Statut

🟡 en cours — table à créer

---

# 🔮 Décisions à venir

## Escrow avancé

- retenue de fonds conditionnelle ;
- libération en plusieurs étapes ;
- dispute system compatible.

## Dispute system

- workflow litige structuré ;
- arbitrage manuel / automatique ;
- compensation post-payout.

## Platform accounts

- comptes par pays / devise ;
- routing financier automatique ;
- intégration PSP réels.

## Ledger interne

- `parent_transaction_id` ;
- journal financier complet ;
- reporting financier.

---

## 🧠 Principe clé

> Une décision non documentée est une dette future.
> Une décision documentée est un accélérateur.

```

À noter : tu devrais garder **une seule décision `.adamas` active**. L’ancienne structure `context/capabilities/memory` doit être remplacée par cette décision v2.
```
