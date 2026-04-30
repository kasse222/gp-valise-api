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

### 1. Définition du périmètre

Avant toute analyse, préciser :

- Module concerné (Booking, Transaction, Trip, etc.)
- Classe ou fichier analysé
- Use case métier

Exemple :

> Périmètre : module Booking, use case ConfirmBooking.

---

### 2. Analyse structurelle

Vérifier la séparation des responsabilités :

- Controller → appelle une Action
- Action → contient la logique métier
- Policy → gère uniquement l’accès
- FormRequest → validation simple uniquement
- Enum → source de vérité des règles métier
- Model → helpers locaux uniquement

---

### 3. Analyse métier

Vérifier :

- Cohérence du flow métier
- Respect des transitions via Enum
- Centralisation des règles métier
- Absence de duplication

---

### 4. Analyse technique

Vérifier :

- Requêtes optimisées (pas de N+1)
- Usage des transactions DB si nécessaire
- Gestion des exceptions métier
- Idempotence sur les opérations critiques
- Concurrence maîtrisée (`lockForUpdate`, etc.)

---

### 5. Analyse sécurité

Vérifier :

- Policy correctement appliquée
- Aucune fuite de données sensibles
- Pas de bypass des règles d’accès
- Validation correcte des entrées

---

### 6. Analyse testabilité

Vérifier :

- Action testable isolément
- Couverture des cas :
    - nominal
    - erreurs
    - edge cases
- Comportement déterministe

---

## 🧰 Outils d’audit

L’audit manuel est complété par :

- Laravel Pint / PHP-CS-Fixer
- PHPStan / Larastan
- Deptrac
- Pest
- GitHub Actions

> Ces outils ne remplacent jamais l’analyse métier.

---

## ✅ Checklist opérationnelle

👉 Voir : `.adamas/ai/capabilities/review/checklist.md`

---

## 🛡️ Non-régression

Chaque audit doit garantir :

- Tous les tests existants passent
- Aucune régression métier introduite
- Les tests manquants sont identifiés
- Toute correction critique est accompagnée d’un test

---

## 🧪 Classification des problèmes

### 🔴 Critique

- bug métier
- faille sécurité
- incohérence de statut
- perte de données

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

Avant toute correction :

1. Est-ce une violation des règles `.adamas` ?
2. Est-ce un problème métier ou technique ?
3. Quel est l’impact réel ?
4. La correction simplifie-t-elle le système ?

> Si la correction complexifie → ne pas corriger immédiatement.

---

## 🚫 Interdits

- Proposer du code sans audit préalable
- Corriger sans comprendre le flow complet
- Ignorer les Enums
- Déplacer de la logique sans justification

---

## ✅ Résultat attendu

Un audit doit produire :

1. Contexte et périmètre
2. Liste des problèmes (classés)
3. Impact métier/technique
4. Recommandations (sans code au départ)
5. Plan d’implémentation

---

## 🧠 Principe clé

> Un bon audit réduit la complexité.  
> Un mauvais audit déplace les problèmes.
