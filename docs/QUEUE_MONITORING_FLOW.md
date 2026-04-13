# 📡 QUEUE MONITORING FLOW — GP-VALISE

## 🎯 Objectif

Mettre en place une supervision intelligente des queues afin de :

- détecter les problèmes réels (pas seulement techniques)
- comprendre la nature de la pression système
- éviter les diagnostics simplistes
- préparer le scaling et la résilience

---

## 🧠 Philosophie

Une queue "haute" ne signifie pas forcément un problème.

👉 Il faut distinguer :

- un pic de trafic normal
- un manque de capacité
- un job lent
- un bug applicatif (retry storm)

---

## 🧱 Architecture globale

```text
Scheduler / Command
   → MonitorQueueHealth
      → QueueHealthService
         → collect metrics
         → detect retry storm
         → assess pressure
            → classification
               → log critique
               → SendSlackAlert (queue low)
```

---

## ⚙️ Commande principale

```bash
php artisan monitoring:queues
```

### Options configurables

- `--high-threshold`
- `--failed-jobs-threshold`
- `--high-age-threshold`
- `--window`

👉 Permet d’ajuster les seuils **sans modifier le code**

---

## 📊 Métriques collectées

### 1. Taille des queues

- `high`
- `default`
- `low`

👉 Permet de détecter un backlog

---

### 2. Âge du plus vieux job

Mesuré en secondes.

👉 Indicateur critique :

- backlog faible + âge élevé = problème caché

---

### 3. Failed jobs récents

Fenêtre glissante (ex: 15 min)

👉 Indique instabilité système

---

### 4. Retry storm

Détection basée sur :

- regroupement par type de job
- identification du job dominant
- seuil de répétition

👉 Permet de détecter un bug ciblé

---

## 🧠 Classification intelligente

Le système ne retourne pas juste des métriques.

👉 Il produit un **diagnostic métier**

## 🖼️ Exemples visuels

### Horizon — séparation des queues et capacité workers

La supervision des queues est observable directement dans Horizon.

Cet écran permet de visualiser :

- la séparation `high / default / low`
- le nombre de `processes` alloués par supervisor
- le backlog courant
- le `wait time` estimé
- l’impact direct du scaling sur la queue `high`

#### Exemple — charge simulée avec supervisor-high à 5 workers

![Horizon queue monitoring](docs/images/horizon-dashboard-queues.png)

### Lecture

Dans ce test de charge :

- `SimulateHeavyJob` est dispatché sur la queue `high`
- le backlog monte fortement lors d’un burst de 1000 jobs
- Horizon montre que l’augmentation de `supervisor-high` réduit le `wait time`
- aucun `failed job` n’est observé pendant cette simulation

👉 Cette capture illustre un point clé :

> un backlog élevé n’indique pas forcément un bug applicatif ;  
> il peut révéler simplement une limite de capacité workers.

### Option complémentaire — alerting Slack

Si une alerte Slack a été documentée dans le projet, elle peut être référencée ici ou dans `OBSERVABILITY_FLOW.md` pour illustrer la réaction du système à un incident réel.

## ![Slack alert example](docs/images/slack-alert-monitoring-example.png)

### 🟢 `healthy`

- aucun signal critique

👉 Action : aucune

---

### 🔵 `traffic_spike`

- backlog élevé
- âge faible

👉 Interprétation :

pic de trafic absorbable

👉 Action :

observer, ne pas scaler immédiatement

---

### 🟡 `slow_processing`

- backlog faible
- âge élevé

👉 Interprétation :

job lent ou bloquant

👉 Action :

- profiler le job
- découper le traitement
- ajuster timeout

---

### 🟠 `retry_storm_pressure`

- backlog élevé
- retry storm détecté

👉 Interprétation :

problème applicatif ou provider

👉 Action :

- identifier le job dominant
- corriger la cause
- ajuster retry/backoff

---

### 🔴 `capacity_pressure`

- backlog élevé
- âge élevé
- pas de retry storm

👉 Interprétation :

manque de workers

👉 Action :

- augmenter `maxProcesses`
- scaler Horizon
- vérifier CPU/RAM

---

## 🚨 Déclenchement d’alerte

Une alerte est déclenchée si :

- backlog dépasse seuil
- failed_jobs dépasse seuil
- retry storm détecté
- âge dépasse seuil

---

## 🔔 Flow d’alerting

```text
MonitorQueueHealth
   → Log::critical
   → SendSlackAlert (queue low)
      → SlackNotifier
```

### Principes

- alerting async
- aucune dépendance réseau bloquante
- contexte riche fourni

---

## 📦 Contexte envoyé dans l’alerte

- tailles des queues
- âge du plus vieux job
- failed jobs
- retry storm (job dominant)
- classification
- recommandation

👉 Permet de comprendre un incident **sans SSH**

---

## 🔄 Interaction avec les queues

### Répartition actuelle

- `high` → webhooks paiement (critique)
- `default` → logique métier standard
- `low` → alerting et tâches secondaires

### Principe

👉 isoler les charges critiques

---

## ⚠️ Limites actuelles

- pas de persistance historique des métriques
- pas de dashboard graphique
- seuils statiques
- pas d’auto-scaling automatique

---

## 🚀 Évolutions prévues

### Court terme

- stockage métriques (DB / Redis)
- historisation des incidents
- amélioration Slack

---

### Moyen terme

- dashboard (Grafana-like)
- corrélation logs + métriques
- seuils dynamiques

---

### Avancé (niveau senior)

- auto-scaling workers
- throttling dynamique
- circuit breaker
- pause dispatch en cas de retry storm
- priorisation dynamique des queues

---

## 🧠 Résultat

Le système GP-Valise ne fait pas seulement du monitoring.

👉 Il fait du **diagnostic opérationnel intelligent**

---

## 🎯 Résumé

| Signal                  | Signification      |
| ----------------------- | ------------------ |
| backlog élevé           | charge ou problème |
| âge élevé               | latence réelle     |
| retry storm             | bug ou provider    |
| combinaison des signaux | diagnostic réel    |

---

### Point d’architecture important

Pour une queue critique avec SLA faible (ex: webhook paiement), la priorité logique (`high`) ne suffit pas à elle seule.

Il faut également :

- une capacité workers adaptée
- des jobs courts
- une isolation claire des traitements critiques
- une observation continue du backlog et du wait time

## 🔥 Conclusion

Ce système permet de passer de :

❌ "la queue est pleine"

👉 à

✅ "voici exactement le type de problème et quoi faire"

---

👉 C’est la base d’un backend SaaS réellement exploitable en production.

```

```
