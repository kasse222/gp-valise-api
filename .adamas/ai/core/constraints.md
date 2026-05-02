# 🚫 AI Constraints — GP-Valise

## 🎯 Objectif

Ce fichier définit les limites strictes que l’IA doit respecter lorsqu’elle intervient sur GP-Valise.

Ces contraintes existent pour éviter :

- hallucinations
- refactors dangereux
- dette technique
- incohérences métier
- corrections trop larges

---

## 📚 Source de vérité

Avant toute analyse, l’IA doit lire :

1. `.adamas/README.md`
2. `.adamas/ai/core/system-prompt.md`
3. `.adamas/ai/core/constraints.md`

Puis charger les modules nécessaires selon le contexte :

### 🧠 Domain (logique métier)

- `.adamas/ai/domain/business-logic.md`
- `.adamas/ai/domain/booking.md`
- `.adamas/ai/domain/transaction.md`

---

### ⚙️ Engineering (architecture + code)

- `.adamas/ai/engineering/architecture.md`
- `.adamas/ai/engineering/coding/method-rules.md`
- `.adamas/ai/engineering/coding/standards.md`
- `.adamas/ai/engineering/git/git-workflow.md`

---

### 🛡️ Security

- `.adamas/ai/security/access-control.md`
- `.adamas/ai/security/financial-rules.md`
- `.adamas/ai/security/webhook-security.md`

---

### 🔍 Observability

- `.adamas/ai/observability/correlation-id.md`
- `.adamas/ai/observability/logging.md`
- `.adamas/ai/observability/monitoring.md`

---

### 🧠 Governance

- `.adamas/ai/governance/audit.md`
- `.adamas/ai/governance/checklist.md`
- `.adamas/ai/governance/decision-log.md`

---

## 🚫 Interdits absolus

L’IA ne doit jamais :

- générer du code sans audit préalable
- valider un code simplement parce qu’il fonctionne
- ignorer les règles `.adamas`
- justifier artificiellement du code incorrect
- proposer un refactor massif
- modifier plusieurs couches sans justification forte
- créer une abstraction inutile
- ajouter une dépendance non demandée
- inventer des classes, méthodes ou fichiers absents
- contourner les Enums
- logger des données sensibles
- proposer une architecture incompatible avec la roadmap v5

---

## 🧱 Contraintes d’architecture

L’IA doit respecter :

- Controller = orchestration HTTP uniquement
- Action = use case métier complet
- Policy = autorisation uniquement
- FormRequest = validation simple uniquement
- Enum = source de vérité des statuts
- Model = données + helpers locaux
- Service = logique transverse uniquement

Références :

- `.adamas/ai/engineering/architecture.md`
- `.adamas/ai/engineering/coding/method-rules.md`

---

## 🔧 Scope des corrections

Toute correction doit être :

- ciblée
- atomique
- limitée à un problème principal
- compatible avec le MVP actuel
- accompagnée de tests

Si la correction est large → découper en étapes.

---

## 🚨 Priorisation obligatoire

1. 🔴 Bugs métier / sécurité / finance
2. 🟠 Mauvaise architecture
3. 🟠 Dette technique bloquante
4. 🟡 Tests manquants
5. 🟡 Lisibilité

---

## 🧪 Non-régression obligatoire

Chaque correction doit préciser :

- tests à lancer
- tests à ajouter
- risques de régression
- fichiers impactés

Aucune correction critique sans test.

---

## 🔐 Sécurité

Interdits :

- bypass Policy
- logique métier dans Controller
- accès non contrôlé aux données
- log de données sensibles
- modification financière sans audit

---

## 💳 Contraintes financières

Toujours respecter :

- Transaction = source de vérité
- Booking ≠ source financière
- CHARGE obligatoire avant confirmation
- pas de double charge
- pas de double payout
- pas de double refund
- pas de double fee
- PAYOUT ⊕ REFUND

Référence :

- `.adamas/ai/security/financial-rules.md`

---

## 🤖 Contraintes IA

L’IA doit :

- définir le périmètre
- poser des questions si besoin
- distinguer hypothèse vs fait
- expliquer l’impact métier
- proposer un plan avant code

---

## 📌 Principe clé

> L’IA doit réduire la complexité, pas la déplacer.
