# 🚫 AI Constraints — GP-Valise

## 🎯 Objectif

Ce fichier définit les limites strictes que l’IA doit respecter lorsqu’elle intervient sur GP-Valise.

Ces contraintes existent pour éviter :

- les hallucinations
- les refactors dangereux
- la dette technique
- les incohérences métier
- les corrections trop larges

---

## 📚 Source de vérité

Avant toute analyse, l’IA doit lire :

1. `.adamas/README.md`
2. `.adamas/ai/core/system-prompt.md`
3. `.adamas/ai/core/constraints.md`
4. `.adamas/ai/context/architecture.md`
5. `.adamas/ai/context/method-rules.md`
6. `.adamas/ai/context/business-logic.md`
7. `.adamas/ai/context/payment-logic.md`

Puis charger uniquement la capability nécessaire :

- audit → `.adamas/ai/capabilities/audit/methodology.md`
- coding → `.adamas/ai/capabilities/coding/standards.md`
- review → `.adamas/ai/capabilities/review/checklist.md`

---

## 🚫 Interdits absolus

L’IA ne doit jamais :

- générer du code sans audit préalable
- valider un code simplement parce qu’il fonctionne
- ignorer les règles `.adamas`
- justifier artificiellement le code existant
- proposer un refactor massif
- modifier plusieurs couches sans justification forte
- créer une abstraction inutile
- ajouter une dépendance non demandée
- inventer des fichiers, classes ou méthodes absents du contexte
- contourner les Enums
- logger des données sensibles
- proposer une architecture incompatible avec la roadmap v5

---

## 🧱 Contraintes d’architecture

L’IA doit respecter :

- Controller = orchestration HTTP uniquement
- Action = use case métier complet
- Policy = autorisation uniquement
- FormRequest = validation d’entrée simple
- Enum = source de vérité des statuts
- Model = données + helpers locaux
- Service = logique transverse uniquement

Références :

- `.adamas/ai/context/architecture.md`
- `.adamas/ai/context/method-rules.md`

---

## 🔧 Scope des corrections

Toute correction proposée doit être :

- ciblée
- atomique
- limitée à un seul problème principal
- compatible avec l’état MVP actuel
- accompagnée des tests à lancer

Une correction ne doit pas modifier plusieurs modules ou plusieurs couches sans raison forte.

Si une correction est large, elle doit être découpée en étapes.

---

## 🚨 Priorisation obligatoire

L’IA doit traiter les problèmes dans cet ordre :

1. 🔴 Bugs métier, sécurité, incohérence financière
2. 🟠 Mauvaise séparation des responsabilités
3. 🟠 Dette technique bloquante
4. 🟡 Tests manquants importants
5. 🟡 Lisibilité, naming, formatage

Ne jamais corriger une amélioration esthétique si un problème critique existe.

---

## 🧪 Non-régression obligatoire

Toute proposition doit préciser :

- les tests existants à lancer
- les tests à ajouter si nécessaire
- les risques de régression
- les fichiers impactés

Aucune correction critique ne doit être acceptée sans test.

---

## 🔐 Sécurité

L’IA ne doit jamais proposer de :

- bypass Policy
- validation métier dans Controller
- accès direct non contrôlé aux données sensibles
- log de données KYC, paiement, identité ou token
- modification financière sans audit transactionnel

---

## 💳 Contraintes financières

Toute logique liée à Payment / Transaction doit respecter :

- Transaction = source de vérité financière
- Booking ne dépend jamais directement du provider
- CHARGE obligatoire avant confirmation
- pas de double charge
- pas de double payout
- pas de double refund
- pas de double fee
- PAYOUT et REFUND sont mutuellement exclusifs
- FEE = revenu plateforme
- PAYMENT_FEE = coût PSP / bancaire

Référence :

- `.adamas/ai/context/payment-logic.md`

---

## 🤖 Contraintes IA

L’IA doit :

- définir le périmètre avant analyse
- signaler les zones non vérifiables
- poser une question si le contexte manque
- distinguer problème réel et hypothèse
- expliquer l’impact métier ou technique
- proposer un plan avant code
- éviter toute correction “par style” sans impact réel

---

## 📌 Principe clé

> L’IA doit réduire la complexité, pas la déplacer.

Si une correction rend le système plus complexe sans gain métier, sécurité ou testabilité clair, elle ne doit pas être proposée.
