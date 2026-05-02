# 🧠 Git Workflow — GP-Valise

---

## 🎯 Objectif

Définir une discipline Git professionnelle pour :

- garantir la traçabilité du code
- éviter les régressions
- structurer les livraisons
- simuler un environnement d’équipe

---

## 🌿 Branches

### Règle absolue

❌ Ne jamais travailler sur `main`

---

### Branch principale

```txt
main
```

- code stable
- toujours déployable
- validé (tests OK)

---

### Branches de travail

```txt
feature/xxx
fix/xxx
refactor/xxx
chore/xxx
docs/xxx
test/xxx
```

---

### Exemples

```txt
feature/payment-webhook
fix/refund-eligibility
refactor/booking-actions
docs/audit-log
```

---

## 🔁 Workflow standard

### 1. Synchronisation

```bash
git checkout main
git pull origin main
```

---

### 2. Création de branche

```bash
git checkout -b feature/nom-feature
```

---

### 3. Développement

- commits fréquents
- commits atomiques
- tests exécutés avant commit

---

### 4. Push

```bash
git push origin feature/nom-feature
```

---

### 5. Merge

- PR ou merge manuel
- uniquement si :
    - tests OK
    - code review OK

---

## ✍️ Convention de commit

### Format obligatoire

```txt
type(scope): description
```

---

### Types

| Type     | Usage                   |
| -------- | ----------------------- |
| feat     | nouvelle fonctionnalité |
| fix      | correction bug          |
| refactor | amélioration interne    |
| test     | ajout/modif tests       |
| docs     | documentation           |
| chore    | maintenance             |

---

### Exemples

```txt
feat(payment): add webhook handler with idempotence
fix(booking): prevent double confirmation
refactor(transaction): extract eligibility service
test(audit): add integrity chain tests
docs(adamas): update coding standards
```

---

### Règles

- description claire et concise
- expliquer le **pourquoi**, pas seulement le quoi
- éviter les commits vagues :

❌ `update code`
❌ `fix bug`

---

## 🧱 Commits atomiques

### Règle

Un commit = une responsabilité

---

### Exemple correct

```txt
feat(audit): add integrity hash chain

- add integrity_hash column
- add previous_hash column
- implement AuditLogIntegrityService
```

---

### Mauvais exemple

```txt
feat: update audit + fix booking + add test
```

---

## 🧪 Avant chaque commit

Checklist obligatoire :

- tests passent
- code formaté
- aucune erreur visible
- pas de `dd()`
- pas de debug oublié

---

## 🚫 Interdits

- commit sans test
- commit cassant `main`
- commit massif non lisible
- travailler directement sur main
- push du code non testé

---

## 🔁 Discipline de travail

### Toujours :

1. créer une branche
2. annoncer l’objectif
3. coder
4. tester
5. commit
6. push
7. résumer

---

## 🧠 Bonnes pratiques avancées

### Rebase (optionnel)

```bash
git pull --rebase origin main
```

👉 évite les merges inutiles

---

### Squash (avant merge)

- nettoyer les commits
- garder un historique propre

---

## 📊 Traçabilité

Chaque feature doit être traçable via :

- branche
- commits
- tests
- description claire

---

## 🧠 Principe clé

> Git n’est pas un backup.
> Git est une preuve de ton raisonnement.

---

## 🎯 Objectif réel

Avec ce workflow, ton repo devient :

- lisible par un recruteur
- crédible en production
- structuré comme une équipe senior

---

## 🚀 Niveau attendu

👉 Tu dois être capable de :

- expliquer chaque commit
- justifier chaque branche
- reconstruire une feature depuis l’historique

```

```
