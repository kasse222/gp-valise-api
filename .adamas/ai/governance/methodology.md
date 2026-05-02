# 🔍 Audit Methodology — GP-Valise

## 🎯 Objectif

Ce fichier définit la méthode obligatoire pour analyser, auditer et améliorer le code du projet GP-Valise.

Aucune modification ne doit être faite sans audit préalable.

L’audit sert à :

- comprendre le contexte avant d’agir ;
- éviter les corrections dangereuses ;
- préserver la cohérence métier ;
- réduire la dette technique ;
- garantir la non-régression ;
- aligner chaque modification avec `.adamas`.

---

## 🧠 Principe fondamental

> Corriger sans comprendre = créer de la dette technique.

Un audit ne consiste pas seulement à dire si le code fonctionne.  
Il doit répondre à une question plus importante :

> Est-ce que ce code est cohérent, sécurisé, testable et aligné avec le domaine métier ?

---

## 🔁 Étapes obligatoires

### 1. Définition du périmètre

Avant toute analyse, préciser :

- module concerné ;
- classe ou fichier analysé ;
- use case métier ;
- flux impacté ;
- niveau de risque.

Exemples :

```txt
Périmètre : module Booking, use case ConfirmBooking.
Risque : transition métier + capacité + paiement.
```

````

```txt
Périmètre : module Payment, webhook refund.completed.
Risque : finance + async + idempotence.
```

---

### 2. Lecture des sources `.adamas`

Avant toute correction, consulter les fichiers nécessaires :

- `.adamas/ai/core/system-prompt.md`
- `.adamas/ai/core/constraints.md`
- `.adamas/ai/domain/architecture.md`
- `.adamas/ai/domain/overview.md`
- `.adamas/ai/domain/payment.md`
- `.adamas/ai/engineering/coding/standards.md`
- `.adamas/ai/engineering/review/checklist.md`

Selon le sujet :

- audit → `.adamas/ai/domain/audit.md`
- observability → `.adamas/ai/observability/*`
- security → `.adamas/ai/security/*`
- git → `.adamas/ai/engineering/git/git-workflow.md`

---

### 3. Analyse structurelle

Vérifier la séparation des responsabilités :

- Controller → orchestration HTTP uniquement ;
- FormRequest → validation HTTP simple ;
- Policy → autorisation uniquement ;
- Action → cas d’usage métier ;
- Service → logique transverse ;
- Enum → source de vérité des statuts/transitions ;
- Model → données + helpers locaux ;
- Resource → transformation de réponse ;
- Job → orchestration async ;
- Event/Listener → effets secondaires non critiques.

Questions à poser :

- La logique est-elle dans la bonne couche ?
- Une Action représente-t-elle bien un seul use case ?
- Un Service est-il vraiment transverse ?
- Le Controller contient-il une décision métier ?
- Le Model orchestre-t-il trop de choses ?

---

### 4. Analyse métier

Vérifier :

- cohérence du flow métier ;
- respect des transitions via Enum ;
- respect des invariants métier ;
- absence de duplication ;
- absence de contournement des règles ;
- alignement avec la roadmap MVP/v5.

Pour GP-Valise, vérifier particulièrement :

- Booking lifecycle ;
- capacité Trip / BookingItem / Luggage ;
- cohérence Booking ↔ Transaction ;
- exclusivité PAYOUT / REFUND ;
- règles de refund standard/admin ;
- audit obligatoire des actions sensibles.

---

### 5. Analyse financière

Obligatoire dès qu’un flux touche Payment, Transaction, Refund, Payout ou Fee.

Vérifier :

- Transaction = source de vérité financière ;
- aucun calcul financier dupliqué ;
- montants persistés, jamais recalculés après coup ;
- `PAYOUT` et `REFUND` mutuellement exclusifs ;
- `FEE` et `PAYMENT_FEE` séparées ;
- idempotence sur charges/refunds/payouts ;
- audit log créé pour toute action admin sensible ;
- transaction DB utilisée pour les opérations multi-modèles.

---

### 6. Analyse technique

Vérifier :

- requêtes optimisées ;
- absence de N+1 ;
- usage correct des transactions DB ;
- usage de `lockForUpdate()` sur opérations concurrentes ;
- gestion des exceptions métier ;
- pagination sur les listes ;
- index DB cohérents avec les filtres ;
- idempotence sur opérations critiques ;
- compatibilité async/job/webhook.

---

### 7. Analyse sécurité

Vérifier :

- Policy appliquée ;
- absence de bypass d’autorisation ;
- séparation ADMIN / SUPPORT_AGENT / USER ;
- aucune donnée sensible dans les logs ;
- signature HMAC sur webhooks ;
- validation correcte des entrées ;
- accès admin limité au strict nécessaire ;
- audit des opérations sensibles.

---

### 8. Analyse observabilité

Vérifier :

- présence du `correlation_id` sur requêtes API ;
- propagation dans jobs async si nécessaire ;
- logs structurés utiles ;
- absence de bruit ;
- webhook logs exploitables ;
- audit logs consultables ;
- capacité à reconstruire un incident.

Question clé :

```txt
Peut-on retrouver l’histoire complète d’une opération sensible ?
```

---

### 9. Analyse testabilité

Vérifier :

- Action testable isolément ;
- tests nominal / erreur / edge case ;
- tests HTTP pour routes, policies et codes de réponse ;
- tests déterministes ;
- pas de rôle random dans factories critiques ;
- couverture des cas d’idempotence ;
- couverture des cas de concurrence si applicable.

---

## 🧪 Classification des problèmes

### 🔴 Critique

- bug métier ;
- faille sécurité ;
- incohérence financière ;
- double payout/refund ;
- perte de traçabilité ;
- violation d’un invariant ;
- fuite de données sensibles.

### 🟠 Important

- mauvaise séparation des responsabilités ;
- duplication ;
- logique mal placée ;
- test manquant sur flux sensible ;
- index manquant sur endpoint filtré ;
- non-idempotence sur flow critique.

### 🟡 Amélioration

- lisibilité ;
- naming ;
- formatage ;
- optimisation mineure ;
- clarification documentaire.

---

## 🧠 Règle de décision

Avant toute correction :

1. Est-ce une violation `.adamas` ?
2. Est-ce un problème métier, financier, sécurité ou technique ?
3. Quel est l’impact réel ?
4. La correction réduit-elle la complexité ?
5. La correction est-elle testable ?
6. Le changement mérite-t-il une décision documentée ?

> Si la correction complexifie sans gain clair, ne pas corriger immédiatement.

---

## 🚫 Interdits

- proposer du code sans audit préalable ;
- corriger sans comprendre le flow complet ;
- ignorer les Enums ;
- contourner les Policies ;
- déplacer de la logique sans justification ;
- faire un refactor massif non demandé ;
- mélanger doc, feature et fix dans une même branche ;
- modifier un flux financier sans test ;
- logger des données sensibles.

---

## 🧰 Outils d’audit

L’audit manuel peut être complété par :

- Pest ;
- Laravel Pint / PHP-CS-Fixer ;
- PHPStan / Larastan ;
- Deptrac ;
- GitHub Actions ;
- `php artisan route:list` ;
- logs Laravel ;
- requêtes SQL / explain si nécessaire.

> Ces outils ne remplacent jamais l’analyse métier.

---

## ✅ Résultat attendu d’un audit

Un audit doit produire :

1. périmètre ;
2. contexte métier ;
3. problèmes classés par gravité ;
4. impact métier / technique / sécurité ;
5. recommandations ;
6. plan d’implémentation ;
7. tests à ajouter ou relancer ;
8. risques de régression ;
9. décision `.adamas` à documenter si nécessaire.

---

## 🛡️ Non-régression obligatoire

Chaque correction critique doit garantir :

- tous les tests existants passent ;
- au moins un test couvre le bug ou la règle ;
- aucune régression métier introduite ;
- les impacts DB/API sont identifiés ;
- les risques sont documentés.

---

## 🧠 Principe clé

> Un bon audit réduit la complexité.
> Un mauvais audit déplace les problèmes.

```

```
````
