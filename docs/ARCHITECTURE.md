# 🧠 ARCHITECTURE GP-VALISE

## 1. Principe directeur

GP-Valise suit une architecture orientée **cas d’usage**.

L’objectif n’est pas seulement de produire une API fonctionnelle, mais un backend **robuste, lisible, testable et évolutif**, capable de supporter un modèle SaaS transactionnel avec logique métier forte, traitements asynchrones et supervision opérationnelle. :contentReference[oaicite:0]{index=0}

Chaque couche a une responsabilité claire et limitée.

---

## 2. Répartition des responsabilités

### Couche HTTP

- `Controller` = orchestration HTTP uniquement
- `FormRequest` = validation d’entrée HTTP
- `Resource` = formatage des réponses API
- `Policy` = contrôle d’accès / autorisation

### Couche métier

- `Action` = un cas d’usage métier complet
- `Model` = relations, helpers métier, invariants locaux
- `Enum` = états, transitions, comportements métier purs
- `Validator` = validation métier réutilisable hors HTTP

### Couche asynchrone / runtime

- `Job` = traitement asynchrone, découplé du cycle HTTP
- `Service` = logique transverse rare ou intégration technique
- `Notifier` = intégration d’alerting externe
- `Command` = supervision, batchs planifiés, diagnostics runtime

---

## 3. Convention d’architecture retenue

### Convention principale

Le flux nominal doit rester :

```text
Controller → Action → Model / Enum / Validator
```

Avec :

- `Policy` pour l’autorisation
- `FormRequest` pour la validation d’entrée
- `Resource` pour la sortie API

### Convention async

Pour les traitements asynchrones critiques :

```text
Controller / Event Source → Job → Action → Model / Enum / Validator
```

Cette convention permet de garder :

- un contrôleur léger
- une logique métier centralisée
- un code testable indépendamment du transport HTTP ou queue

---

## 4. Interdits structurants

Les règles suivantes cadrent l’architecture du projet :

- pas de logique métier dans les `Controllers`
- pas de logique métier dans les `Policies`
- pas d’accès base de données dans les `Enums`
- pas de duplication entre `Action` et `Service`
- pas de dépendance directe à un provider externe dans les contrôleurs
- pas de side effects critiques non isolés
- pas de dépendance réseau synchrone dans le chemin de supervision critique

Ces règles visent à éviter les dérives classiques des projets Laravel qui grossissent mal : logique noyée dans les contrôleurs, services fourre-tout, duplication métier et couplage transport/métier.

---

## 5. Décisions métier et techniques déjà prises

## 📦 Booking lifecycle

Le module `Booking` repose sur une vraie **state machine métier**.

### Flow principal

```text
EN_ATTENTE → EN_PAIEMENT → CONFIRMEE
                     ↓
                  EXPIREE

CONFIRMEE → LIVREE → TERMINE

LIVREE → EN_LITIGE → REMBOURSEE
```

### Règles structurantes

1. Création d’un booking → `EN_PAIEMENT`
    - bloque temporairement la capacité
    - définit `payment_expires_at`

2. Paiement validé + confirmation métier → `CONFIRMEE`
    - réservation confirmée
    - kg définitivement consommés

3. Paiement expiré / non finalisé → `EXPIREE`
    - libération des ressources réservées
    - bagages remis en `EN_ATTENTE`

4. Livraison → `LIVREE`
    - déclenchement possible du payout

5. Clôture → `TERMINE`

6. Litige → `EN_LITIGE`
    - possibilité de refund selon les règles métier

### Décisions appliquées

- `BookingService` supprimé
- `BookingController` refactoré
- transitions centralisées dans `BookingStatusEnum`
- historique des statuts piloté par les transitions métier
- batch d’expiration isolé et idempotent
- events métier homogénéisés :
    - `BookingConfirmed`
    - `BookingCanceled`
    - `BookingDelivered`
    - `BookingExpired`

Cette structuration est déjà visible dans l’architecture du contrôleur, du modèle et de l’enum de booking.

---

## 6. Concurrence, locking et idempotence

La réservation et le paiement sont traités comme des zones critiques.

### Objectifs

- éviter le surbooking
- éviter la double réservation
- éviter les side effects dupliqués
- garantir un comportement stable en cas de retry ou replay

### Principes retenus

- utilisation de `DB::transaction(...)`
- verrouillage pessimiste (`lockForUpdate()`) sur les ressources critiques
- relecture base sous transaction
- guards métier idempotents
- transitions centralisées

### Exemples de situations couvertes

- double clic utilisateur
- retry HTTP
- scheduler rejoué
- batch relancé
- job queue relancé après incident
- webhook reçu plusieurs fois

La documentation métier détaillée sur ce sujet reste portée par `BOOKING_FLOW.md`.

---

## 7. Sémantique de capacité

Le modèle `Trip` ne se contente pas d’une capacité statique.

### Règle métier retenue

Un trajet considère comme kg occupés :

- les bookings `CONFIRMEE`
- les bookings `EN_PAIEMENT` non expirés

Ne comptent pas :

- les bookings expirés
- les bookings annulés
- les bookings finaux inactifs

Cette règle permet d’aligner la capacité réellement bloquée avec le métier et de mieux représenter un paiement en cours comme une réservation temporairement active. La logique de calcul est portée côté `Trip`, avec `kgReserved()`, `kgDisponible()` et `canAcceptKg()`.

---

## 8. Module Transaction

## Financial boundary hardening

Le domaine financier a été recentré sur `Transaction`.

### Ce qui est vrai maintenant

- `Transaction` est la seule source de vérité financière métier et publique.
- Les écritures publiques sur `Payment` ont été supprimées.
- `PaymentController` est read-only.
- Les flux `charge`, `refund` et `payout` passent par les actions transactionnelles dédiées.

### Mapping des flux

- charge → `CreateTransaction`
- refund → `RefundTransaction` + `HandlePaymentWebhook`
- payout → `CreatePayoutTransaction` + `CreatePayoutAfterBookingDelivered`

### Bénéfice

- réduction du risque d’incohérence financière
- suppression des chemins publics dangereux
- clarification forte de la frontière métier

## 9. Paiement asynchrone

Le système suit un modèle **asynchrone inspiré des PSP modernes**.

### Principes

- abstraction par `PaymentProvider`
- provider fake pour simulation locale
- finalisation réelle par webhook
- séparation claire entre paiement métier et transaction financière

### Provider de simulation

`FakePaymentProvider` permet de simuler :

- `completed`
- `pending`
- `failed`

pour :

- `charge`
- `refund`
- `payout`

Cela sert à tester l’architecture sans coupler immédiatement le système à Stripe ou à un PSP réel.

---

## 10. Webhook processing

Le traitement webhook est isolé dans une chaîne asynchrone dédiée.

### Flow retenu

```text
WebhookController
   → ProcessPaymentWebhook (queue high)
      → HandlePaymentWebhook
         → Transaction / Booking updates
         → WebhookLog
         → alerting si échec définitif
```

### Décisions clés

- `ProcessPaymentWebhook` est routé sur la queue `high`
- retries contrôlés avec `tries`, `backoff()` et `retryUntil()`
- distinction entre erreur retryable et erreur définitive
- log critique en cas d’échec final
- alerte Slack déclenchée de manière asynchrone

Le job `ProcessPaymentWebhook` est explicitement prioritaire et dispose de backoff progressif.

### Idempotence webhook

`HandlePaymentWebhook` applique une idempotence par `event_id` :

- si l’event a déjà été loggé, il est ignoré
- si la transaction est déjà finalisée, elle n’est pas retraitée
- les événements non supportés sont marqués `ignored`
- les refunds sont finalisés par `refund.completed` / `refund.failed`

Cette logique évite les doubles traitements, même en cas de replay provider ou de retry queue.

---

## 11. Observabilité et supervision

La supervision n’est plus limitée aux logs applicatifs classiques.

Le projet introduit une vraie logique de **diagnostic opérationnel**.

### Monitoring webhook

Une commande dédiée `monitoring:webhooks` collecte :

- nombre de webhooks traités
- webhooks ignorés
- webhooks échoués
- failed jobs liés aux webhooks

Si les seuils sont dépassés :

- log critique
- dispatch d’une alerte Slack
- fallback email possible

Cette commande reste simple, mais utile pour un premier niveau d’observabilité ciblé sur les paiements async.

### Monitoring queues

Une commande dédiée `monitoring:queues` s’appuie sur `QueueHealthService`.

Elle observe plusieurs signaux :

- backlog des queues `high / default / low`
- âge du plus vieux job
- nombre de `failed_jobs` récents
- détection d’un retry storm

Le but n’est pas seulement de savoir “si la queue est haute”, mais de qualifier **la nature de la pression système**.

### Classification intelligente

Le système distingue :

- `healthy`
- `traffic_spike`
- `slow_processing`
- `retry_storm_pressure`
- `capacity_pressure`

Cette classification sert à produire un diagnostic exploitable pour l’incident review ou le scaling, avec `reason` et `recommended_action`.

---

## 12. Alerting

Le projet introduit un service dédié d’alerting externe :

- `SlackNotifier`

### Principes retenus

- aucune dépendance Slack directe dans le cœur métier
- formatage centralisé des messages
- logs locaux en cas d’erreur Slack
- déclenchement asynchrone via job

### Flux

```text
Command / Job critique
   → SendSlackAlert (queue low)
      → SlackNotifier
         → webhook Slack
```

Le job `SendSlackAlert` est volontairement routé sur la queue `low`, afin de ne pas polluer les traitements critiques. Le notifier gère les cas :

- alertes désactivées
- webhook absent
- réponse HTTP non-success
- exception réseau.

---

## 13. Stratégie de queues

### Objectif

Prioriser les traitements critiques, éviter les interférences entre charges et préparer la montée en charge.

### Queues retenues

#### `high`

Pour les traitements critiques à faible latence :

- webhooks paiement
- opérations financières critiques
- traitements nécessitant une forte priorité

#### `default`

Pour la logique métier applicative standard :

- jobs non critiques mais normaux
- traitements usuels du backend

#### `low`

Pour les traitements secondaires :

- alerting Slack
- logs async
- tâches de confort
- futures tâches non critiques

### Bénéfices

- isolation des charges
- limitation de l’effet noisy neighbor
- meilleure lecture opérationnelle
- base saine pour le scaling Horizon

Cette stratégie est maintenant cohérente entre architecture, monitoring et alerting.

---

## 14. Runtime / infrastructure applicative

Le runtime est séparé en containers dédiés :

- `app` = PHP-FPM / API
- `horizon` = workers de queue
- `scheduler` = exécution planifiée des commandes Laravel
- `nginx` = reverse proxy
- `redis` = backend queue / cache
- `mysql` = persistance

### Principe retenu

Un process principal par container.

### Bénéfices

- meilleure lisibilité du runtime
- séparation claire des responsabilités système
- redémarrage plus simple
- meilleure préparation à un déploiement réel

Cette approche remplace les workers lancés manuellement et rapproche l’environnement local d’un runtime plus crédible côté prod.

---

## 15. État d’alignement des modules

### Modules bien alignés

- Booking
- Transaction
- Payment async / webhook
- Monitoring webhook
- Monitoring queue
- Alerting critique
- Queue strategy
- Runtime containerisé

### Modules partiellement alignés

- Trip

### Modules encore à enrichir / refactorer

- Plan
- Report
- observabilité persistée
- dashboard métier
- simulation de charge
- auto-protection système

---

## 16. Direction cible

Le projet évolue progressivement vers un modèle **marketplace / escrow simple**.

### Cible métier

1. l’expéditeur paie la plateforme
2. la plateforme conserve temporairement les fonds
3. le voyageur livre le colis
4. la plateforme déclenche le payout
5. le refund reste possible en cas d’échec ou de litige

### Cible technique

- renforcer l’observabilité
- mesurer la pression réelle sur les workers
- documenter les seuils de saturation
- introduire des protections actives :
    - throttling
    - circuit breaker
    - pause dispatch ciblée
    - backoff intelligent

---

## 17. Priorités à venir

- finaliser l’alignement de `Trip`
- enrichir `AUDIT.md`
- créer `QUEUE_MONITORING_FLOW.md`
- persister certaines métriques de supervision
- simuler une montée en charge réaliste
- raisonner sur la saturation workers / Horizon
- préparer les mécanismes d’auto-protection système

---
