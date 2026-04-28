# 🔍 Audit Methodology — GP-Valise

## 🎯 Objectif

Ce fichier définit la méthode obligatoire pour analyser, auditer et améliorer le code du projet.

> Aucune modification ne doit être faite sans passer par cette méthodologie.

---

## 🧠 Principe fondamental

> Corriger sans comprendre = créer de la dette technique.

Chaque intervention doit être précédée d’un diagnostic structuré.

---

## 🔁 Étapes obligatoires

### 1. Identification du contexte

- Module concerné (Booking, Transaction, Trip, etc.)
- Objectif du code analysé (use case métier)
- Acteurs impliqués (user, traveler, system, etc.)

---

### 2. Analyse structurelle

Vérifier :

- Controller → appelle-t-il une Action ?
- Action → contient-elle la logique métier ?
- Policy → gère-t-elle uniquement l’accès ?
- FormRequest → validation simple uniquement ?
- Enum → règles métier respectées ?
- Model → helpers simples uniquement ?

---

### 3. Analyse métier

Vérifier :

- Le flow métier est-il cohérent ?
- Les transitions de statut passent-elles par l’Enum ?
- Les règles métier sont-elles centralisées ?
- Y a-t-il duplication de logique ?

---

### 4. Analyse technique

Vérifier :

- Requêtes optimisées (pas de N+1)
- Usage correct des transactions DB si nécessaire
- Gestion des exceptions métier
- Idempotence pour les opérations critiques
- Concurrence maîtrisée (`lockForUpdate`, etc.)

---

### 5. Analyse sécurité

Vérifier :

- Policy appliquée correctement
- Données sensibles protégées
- Pas de bypass des règles d’accès
- Validation correcte des entrées

---

### 6. Analyse testabilité

Vérifier :

- L’Action est-elle testable isolément ?
- Les tests couvrent-ils :
    - cas nominal
    - erreurs
    - edge cases
- Le comportement est-il déterministe ?

---

## 📌 Périmètre d’audit

Un audit peut porter sur :

- un fichier
- une classe
- une Action
- un module complet
- un use case métier
- une couche technique

Le périmètre doit être annoncé avant l’analyse.

Exemple :

> Périmètre : module Booking, use case ConfirmBooking.

---

## 🧰 Outils d’audit recommandés

L’audit manuel peut être complété par :

- Laravel Pint / PHP-CS-Fixer : formatage du code
- PHPStan / Larastan : analyse statique
- Deptrac : vérification des dépendances entre couches
- Pest : tests automatisés
- GitHub Actions : non-régression CI

Ces outils doivent être exécutés avant toute proposition de correction.

## Ils permettent de détecter des problèmes structurels ou techniques, mais ne remplacent jamais l’analyse métier, qui reste prioritaire.

## ✅ Checklist rapide

Avant toute correction, vérifier :

- [ ] Le périmètre d’audit est clair
- [ ] La responsabilité de chaque couche est respectée
- [ ] Les Enums sont utilisés comme source de vérité
- [ ] Les Policies ne contiennent pas de logique métier
- [ ] Les Actions sont testables isolément
- [ ] Les requêtes sont raisonnablement optimisées
- [ ] Les cas critiques sont couverts par tests
- [ ] La correction ne complexifie pas inutilement le système

---

## 🛡️ Non-régression

Chaque audit doit vérifier que :

- les tests existants passent toujours
- les changements proposés n’introduisent pas de régression métier
- les tests manquants sont identifiés
- une correction critique doit être accompagnée d’un test

## 🧪 Classification des problèmes

Chaque problème doit être classé :

### 🔴 Critique

- bug métier
- faille sécurité
- incohérence de statut
- perte de données possible

### 🟠 Important

- mauvaise séparation des responsabilités
- duplication
- logique mal placée

### 🟡 Amélioration

- lisibilité
- naming
- optimisation mineure

---

## 🧠 Règle de décision

Avant toute correction, répondre :

1. Est-ce une violation des règles d’architecture ?
2. Est-ce un problème métier ou technique ?
3. Quel est l’impact réel ?
4. Est-ce que la correction simplifie ou complexifie le système ?

---

## 🚫 Interdits pendant l’audit

- proposer du code immédiatement
- corriger sans comprendre le flow complet
- ignorer les Enums
- déplacer de la logique sans justification

---

## ✅ Résultat attendu d’un audit

Un audit doit produire :

1. Analyse du contexte
2. Liste des problèmes (classés)
3. Explication des impacts
4. Proposition de correction (sans code au début)
5. Plan d’implémentation

---

## ⚙️ Interaction avec l’IA

L’IA doit :

- suivre strictement ces étapes
- poser des questions si le contexte est incomplet
- prioriser la compréhension
- proposer des améliorations justifiées

---

## 🧠 Principe clé

> Un bon audit réduit la complexité.
> Un mauvais audit déplace les problèmes.
