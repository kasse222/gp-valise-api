# ADAMAS — Context Engineering Layer (GP-Valise)

## 🎯 Objectif

Ce dossier constitue la **source de vérité technique interne** du projet GP-Valise.

Il sert à :

- Auditer le code existant
- Garantir la cohérence architecturale
- Réduire les hallucinations de l’IA
- Standardiser les décisions techniques
- Accélérer le développement de manière contrôlée

---

## ⚠️ Règle fondamentale

Avant toute modification de code :

> L’IA ou le développeur doit produire un audit.

Aucune implémentation ne doit être faite sans :

1. Analyse du contexte
2. Vérification des règles existantes
3. Validation des impacts

---

## 🧠 Philosophie d’ingénierie

Le projet suit une approche :

- **Action-driven architecture**
- **Domain-driven (light)**
- **Enums comme source de vérité métier**
- **Séparation stricte des responsabilités**

Flux standard :

HTTP Request → Controller → FormRequest (validation)
↓
Action (use case)
↓
┌────────────┼────────────┐
↓ ↓ ↓
Model(s) Enum(s) Event(s)
↓
Transaction DB
↓
HTTP Response (ou Job)

---
