# 🔔 Notifications — GP-Valise

> Document de design Phase 7. À lire avant toute implémentation de notification.

---

## 🎯 Objectif

Informer les acteurs des événements métier qui les concernent, sans coupler la logique de notification au domaine.

```
Domaine       → Event dispatché
Event         → Listener (notification side-effect)
Listener      → Notification (email / websocket / push)
```

**Contrainte fondamentale :** Les notifications sont des **effets secondaires non critiques**. Elles ne doivent jamais bloquer un flow métier.

---

## 🧠 Principe d'architecture

```
✅ Event → Listener → Notification (async via queue)
❌ Notification déclenchée directement dans une Action
❌ Notification bloquante (sync) dans un flow financier
❌ Données sensibles dans les payloads de notification
```

---

## 👥 Acteurs et canaux

| Acteur     | Canal principal  | Canal secondaire       |
| ---------- | ---------------- | ---------------------- |
| Expéditeur | Email            | Websocket (temps réel) |
| Voyageur   | Email            | Websocket (temps réel) |
| Admin      | Slack (existant) | Email                  |

---

## 📋 Matrice des notifications

### Booking

| Événement                            | Expéditeur | Voyageur | Admin |
| ------------------------------------ | ---------- | -------- | ----- |
| Booking confirmé (CHARGE COMPLETED)  | ✅ Email   | ✅ Email | —     |
| Booking expiré                       | ✅ Email   | —        | —     |
| Booking livré                        | ✅ Email   | —        | —     |
| Booking remboursé (REFUND COMPLETED) | ✅ Email   | —        | —     |

### Escrow

| Événement                       | Expéditeur | Voyageur | Admin |
| ------------------------------- | ---------- | -------- | ----- |
| Payout libéré (escrow release)  | —          | ✅ Email | —     |
| Payout complété                 | —          | ✅ Email | —     |
| Escrow bloqué (dispute ouverte) | —          | ✅ Email | —     |

### Dispute

| Événement                                | Expéditeur           | Voyageur             | Admin            |
| ---------------------------------------- | -------------------- | -------------------- | ---------------- |
| Dispute ouverte                          | ✅ Email             | ✅ Email             | ✅ Slack + Email |
| Statut changé (UNDER*REVIEW, WAITING*\*) | ✅ Websocket         | ✅ Websocket         | —                |
| Message reçu                             | ✅ Websocket + Email | ✅ Websocket + Email | —                |
| Dispute résolue                          | ✅ Email             | ✅ Email             | —                |
| Dispute non assignée > SLA               | —                    | —                    | ✅ Slack         |
| Dispute ESCALATED                        | —                    | —                    | ✅ Slack + Email |

---

## 🏗️ Architecture cible

### Events existants (à enrichir avec Listeners)

```php
BookingConfirmed         → NotifyBookingConfirmed
BookingDelivered         → NotifyBookingDelivered
BookingExpired           → NotifyBookingExpired
TransactionCreated       → (selon type)
DisputeStatusChanged     → NotifyDisputeStatusChanged
DisputeMessageAdded      → NotifyDisputeMessageAdded
```

### Nouveaux Events Phase 7

```php
EscrowReleased           → NotifyPayoutReleased (voyageur)
RefundCompleted          → NotifyRefundCompleted (expéditeur)
DisputeUnassignedSLA     → NotifyAdminDisputeSLA
```

### Responsabilités

| Composant    | Responsabilité                             |
| ------------ | ------------------------------------------ |
| Event        | Transporter les données métier nécessaires |
| Listener     | Router vers le bon canal de notification   |
| Notification | Formater le message pour le canal          |
| Queue        | Isolation async — ne bloque pas le flow    |

---

## 🔒 Contraintes critiques

### Données sensibles

```
❌ Jamais dans un payload de notification :
  - Montants exacts (sauf résumé formaté)
  - Données KYC
  - Tokens
  - provider_transaction_id
  - Clés internes

✅ Autorisé :
  - booking_id (référence)
  - Montant formaté en devise ("15.00€")
  - Statut lisible ("Votre remboursement a été traité")
  - Lien vers l'interface (sans token dans l'URL)
```

### Idempotence

Les notifications doivent être idempotentes — un Event dispatché deux fois ne doit pas produire deux emails identiques.

```php
// Guard via cache ou DB flag
if ($this->alreadyNotified($event->booking->id, $notificationType)) {
    return;
}
```

### Resilience

```
✅ Notification failure → log warning, pas d'exception bloquante
✅ Queue séparée pour notifications (ne pas polluer la queue financière)
✅ Retry limité (3 max) — un email manqué n'est pas un incident critique
```

---

## 📡 Websocket — Phase 7

**Canal recommandé :** Laravel Reverb ou Pusher.

**Events temps réel prioritaires :**

- Nouveau message dispute
- Changement statut dispute
- Statut booking mis à jour

**Règle :** Le websocket est un canal de confort, pas une source de vérité. L'état réel reste en DB.

---

## 📧 Email — templates

| Template            | Destinataire                  | Trigger                         |
| ------------------- | ----------------------------- | ------------------------------- |
| `booking-confirmed` | Expéditeur                    | BookingConfirmed                |
| `booking-delivered` | Expéditeur + Voyageur         | BookingDelivered                |
| `payout-released`   | Voyageur                      | EscrowReleased                  |
| `refund-completed`  | Expéditeur                    | RefundCompleted                 |
| `dispute-opened`    | Expéditeur + Voyageur + Admin | DisputeStatusChanged (OPEN)     |
| `dispute-message`   | Destinataire du message       | DisputeMessageAdded             |
| `dispute-resolved`  | Expéditeur + Voyageur         | DisputeStatusChanged (RESOLVED) |

---

## 🧪 Tests obligatoires

| Scénario                                                | Type    |
| ------------------------------------------------------- | ------- |
| Event dispatché → Listener appelé                       | Feature |
| Notification envoyée au bon acteur                      | Feature |
| Notification non envoyée si RESOLVED (plus de messages) | Feature |
| Idempotence (double dispatch → un seul email)           | Feature |
| Données sensibles absentes du payload                   | Unit    |
| Failure notification → flow non bloqué                  | Feature |

---

## 🚫 Interdits

```
Notification synchrone dans une Action financière
Données sensibles dans les emails ou payloads websocket
Notification bloquant le flow métier si elle échoue
Queue notifications partagée avec queue financière critique
Lien avec token d'authentification dans l'URL email
```

---

## 📈 Roadmap

```
Phase 7a — Email basique
  booking-confirmed, payout-released, refund-completed, dispute-opened

Phase 7b — Websocket temps réel
  dispute messages, statut dispute

Phase 7c — Notifications avancées
  SLA alerts, digest quotidien admin, push mobile
```

---

## 🧠 Résumé

```
Notification = effet secondaire async
Jamais bloquant · Jamais critique · Toujours idempotent
```
