# 🧠 ARCHITECTURE GP-VALISE

## 1. Principe directeur

GP-Valise suit une architecture orientée cas d’usage.

Chaque couche a une responsabilité claire et limitée.

### Répartition des responsabilités

- `Controller` = orchestration HTTP uniquement
- `FormRequest` = validation d’entrée HTTP
- `Policy` = autorisation / contrôle d’accès
- `Action` = un cas d’usage métier complet
- `Validator` = validation métier réutilisable hors HTTP
- `Enum` = règles d’état, transitions et comportement métier pur
- `Service` = orchestration transverse rare
- `Job` = traitement asynchrone et découplé
- `Notifier` = intégration d’alerting externe (Slack, email, etc.)

---

## 2. Interdits

Les règles suivantes structurent le projet :

- pas de logique métier dans les `Controllers`
- pas de logique métier dans les `Policies`
- pas d’accès base de données dans les `Enums`
- pas de duplication entre `Action` et `Service`
- pas de `Service` pour un use case métier simple et isolé
- pas de dépendance directe à un provider externe dans les contrôleurs

---

## 3. Convention d’architecture retenue

### Convention principale

- injection d’instance dans le contrôleur
- appel uniforme des use cases via `execute(...)`

### Cible

Le flux standard doit être :

`Controller -> Action -> Model / Enum / Validator`

avec :

- `Policy` pour l’accès
- `FormRequest` pour la validation HTTP
- `Resource` pour la réponse API

### Flux async retenu

Pour les traitements asynchrones critiques :

`Controller / Event Source -> Job -> Action -> Model / Enum / Validator`

---

## 4. Décisions déjà prises

## 📦 Booking lifecycle (règle métier)

1. Création → `EN_PAIEMENT`
    - bloque temporairement les kg
    - définit `payment_expires_at`

2. Paiement validé + confirmation métier → `CONFIRMEE`
    - confirme la réservation
    - kg définitivement utilisés

3. Paiement échoué / expiré
    - booking → `PAIEMENT_ECHOUE` ou `EXPIREE` selon le cas
    - kg libérés
    - luggage remis `EN_ATTENTE`

4. Livraison → `LIVREE` puis `TERMINE`

### Booking

- `BookingService` supprimé
- `BookingController` refactoré
- usage plus cohérent du route model binding
- actions appelées par injection d’instance
- flow paiement introduit : `EN_ATTENTE -> EN_PAIEMENT -> CONFIRMEE`
- expiration automatique des bookings en attente de paiement
- batch d’expiration isolé et idempotent
- events métier alignés : `BookingConfirmed`, `BookingCanceled`, `BookingDelivered`, `BookingExpired`
- listeners de logs homogénéisés (`booking.*`)
- création directe d’historique de statut supprimée au profit des transitions métier uniquement
- tests métier, events et batch alignés

### Transaction

- `TransactionService` supprimé
- `TransactionController` aligné sur le pattern Action-first
- `CreateTransaction` et `RefundTransaction` centralisent les invariants métier
- création de transaction autorisée uniquement pour un booking :
    - appartenant à l’utilisateur
    - en `EN_PAIEMENT`
    - non expiré
    - sans transaction existante
- `UpdateTransactionRequest` supprimé
- eager loading global implicite supprimé du modèle `Transaction`
- events métier alignés : `TransactionCreated`, `TransactionRefunded`
- listeners de logs homogènes (`transaction.*`)

### Payment

- module réaligné sur le pattern Action-first
- `PaymentController` refactoré pour utiliser des actions injectées
- lecture sortie du controller via `ListPayments` et `GetPaymentDetails`
- `CreatePayment` et `UpdatePayment` convertis en actions d’instance
- `Payment` reste pour l’instant une couche métier simple côté produit/UI
- `Transaction` reste la couche financière la plus robuste du système
- évolution future envisagée : modèle de type escrow / marketplace géré progressivement

### Trip

- `UpdateTrip` créé
- `TripController` partiellement refactoré
- `index()` extrait vers `ListTrips`
- `show()` extrait vers `GetTripDetails`

---

## 5. Runtime / infrastructure applicative

### Containers

Le runtime est désormais séparé en containers dédiés :

- `app` = PHP-FPM / application web
- `horizon` = workers de queue
- `scheduler` = exécution planifiée des commandes Laravel
- `nginx` = reverse proxy
- `redis` = backend queue/cache
- `mysql` = base de données

### Principe retenu

Un process principal par container.

Cette approche remplace le lancement manuel de workers et améliore :

- la lisibilité
- la résilience
- le redémarrage automatique
- la préparation à la production

---

## 6. Stratégie de queues

### Objectif

Prioriser les traitements critiques, éviter la saturation et préparer la montée en charge.

### Queues retenues

- `high`
    - webhooks paiement
    - événements financiers critiques
    - traitements nécessitant une faible latence

- `default`
    - logique métier standard
    - traitements applicatifs normaux

- `low`
    - tâches non critiques
    - logs async, emails, exports, tâches de confort futures

### Décision appliquée

- `ProcessPaymentWebhook` est désormais routé sur la queue `high`
- Horizon utilise des supervisors distincts par niveau de priorité
- la visibilité de `high / default / low` est opérationnelle dans le dashboard Horizon

### Bénéfices

- isolation des charges
- réduction de l’effet “noisy neighbor”
- meilleure lisibilité des métriques
- base prête pour le scaling réel

---

## 7. Observabilité et alerting

### Monitoring existant

Une commande dédiée surveille la santé des webhooks :

- volume traité
- volume ignoré
- volume en échec
- présence de `failed_jobs` liés aux webhooks

### Alerting critique

Le projet introduit un service dédié :

- `SlackNotifier`

Utilisation actuelle :

- alerte critique sur échec définitif de `ProcessPaymentWebhook`
- alerte critique sur dépassement de seuil détecté par `monitoring:webhooks`
- fallback email conservé

### Limite actuelle connue

L’intégration réelle Slack reste **à finaliser** :

- le code d’intégration est prêt
- les tests sont verts
- les appels sont correctement instrumentés
- mais le webhook entrant Slack actuellement configuré est invalide (`404 no_team`)

Conséquence :

- la fondation technique d’alerting est prête
- la livraison réelle vers Slack dépend d’un **nouveau webhook valide**

---

## 8. État actuel

### Modules bien alignés

- Booking
- Transaction
- Payment
- Webhook async
- Monitoring webhook
- Alerting critique (fondation prête)
- Queue strategy (`high / default / low`)
- Horizon sécurisé

### Modules partiellement alignés

- Trip

### Modules encore à refactorer / enrichir

- Plan
- Report
- finalisation observabilité avancée
- correction du problème de `route:cache` observé en runtime
- finalisation de l’intégration Slack réelle avec un webhook valide

---

## 9. Direction métier à porter progressivement

### Payment vs Transaction

- `Payment` = couche métier produit / cycle de paiement côté plateforme
- `Transaction` = mouvements financiers unitaires et traçables

### Direction cible

Le projet évolue vers un modèle marketplace / escrow simple :

1. l’expéditeur paie la plateforme
2. la plateforme conserve temporairement les fonds
3. le voyageur livre le colis
4. la plateforme libère les fonds au voyageur
5. un remboursement reste possible en cas d’échec ou de litige

### Types financiers cibles

- `charge`
- `payout`
- `refund`
- `fee`

Cette évolution doit se faire par étapes, sans refactor destructif global.

---

## 10. Priorités à venir

- finaliser l’alignement de `Trip`
- corriger le bug `route:cache`
- finaliser Slack avec un webhook valide
- enrichir l’observabilité :
    - saturation queue `high`
    - temps d’attente anormaux
    - backlog
- préparer le scaling réel des workers en production
