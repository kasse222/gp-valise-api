## Tradeoffs

L’architecture paiement privilégie :

- découplage ;
- résilience ;
- extensibilité multi-PSP ;
- cohérence financière ;

au prix d’une complexité plus élevée.

---

## 1. Resolver vs if/else

| Option         | Avantages                                  | Inconvénients                  |
| -------------- | ------------------------------------------ | ------------------------------ |
| Resolver dédié | scalable, centralisé, testable, extensible | plus complexe                  |
| if/else inline | simple au départ                           | couplage fort, non maintenable |

### Décision

👉 utilisation d’un `PaymentProviderResolver`.

Le domaine ne choisit jamais directement un provider.

---

## 2. providerStatus brut vs status unifié

Chaque PSP possède son propre vocabulaire :

| Provider | Exemple     |
| -------- | ----------- |
| Stripe   | `succeeded` |
| Kkiapay  | `success`   |
| Fake     | `completed` |

### Décision

```txt
Le providerStatus brut est conservé.
Le mapping métier se fait dans le domaine.
```

### Pourquoi

Avantages :

- extensibilité multi-PSP ;
- debug plus simple ;
- audit plus fidèle au provider ;
- évite la perte d’information.

Inconvénients :

- nécessité d’un mapping interne ;
- légère complexité supplémentaire.

---

## 3. Normalisation obligatoire des webhooks

Les payloads PSP sont tous différents :

- Stripe → `type`
- Kkiapay → `event`
- certains providers → aucun `event_id`

### Décision

Tous les providers doivent produire :

```php
PaymentEventData
```

### Tradeoff

| Approche                  | Avantages                                  | Inconvénients         |
| ------------------------- | ------------------------------------------ | --------------------- |
| normalisation centralisée | domaine stable, providers interchangeables | couche supplémentaire |
| payload brut PSP          | simple au départ                           | couplage critique     |

### Conséquence

Le domaine ne dépend jamais du format d’un PSP externe.

---

## 4. Fallback FakeProvider

### Décision

```php
'default' => 'fake'
```

### Avantages

- stabilité tests ;
- environnement local simple ;
- onboarding rapide ;
- CI déterministe.

### Risques

- dangereux en production ;
- peut masquer un mauvais routing ;
- faux sentiment de sécurité.

### Évolution prévue

```txt
FakeProvider interdit en production.
```

---

## 5. Montants float vs integer

### Décision finale — Phase 3D

Migration complète vers integer minor units.

| Domaine          | Unité              | Exemple       |
| ---------------- | ------------------ | ------------- |
| Montants argent  | centimes (integer) | 1500 = 15.00€ |
| Poids (capacity) | grammes (integer)  | 25000 = 25kg  |
| Poids (luggage)  | kg × 10 (integer)  | 25 = 2.5kg    |
| Dimensions       | cm (integer)       | 60 = 60cm     |

### Avantages

- arithmetic déterministe
- zéro erreur d'arrondi
- fintech-grade
- ledger-compatible

### Status : DONE — Phase 3D

## 6. Provider hardcodé en MVP

Certaines Actions utilisent encore :

```php
PaymentProviderEnum::FAKE
```

### Décision

Accepté temporairement pendant la phase MVP.

### Pourquoi

- accélération du développement ;
- simplification des tests ;
- architecture resolver encore en transition.

### Dette technique

Tout hardcoding provider devra disparaître.

Objectif cible :

```txt
resolver obligatoire partout
```

---

## 7. Webhook verification : hash secret vs API verification

Certains PSP utilisent :

- signature HMAC ;
- hash secret ;
- callback verification API ;
- whitelist IP.

Kkiapay mélange plusieurs approches dans sa documentation. :contentReference[oaicite:0]{index=0}

### Décision actuelle

Architecture générique :

```txt
verifyWebhook() appartient au provider.
```

Chaque provider décide :

- comment vérifier ;
- quels headers utiliser ;
- s’il faut appeler une API externe.

### Avantages

- flexibilité maximale ;
- adaptation provider-specific ;
- domaine découplé.

### Inconvénients

- logique sécurité distribuée ;
- tests plus complexes.

---

## 8. Event sourcing complet vs modèle transactionnel simple

### Alternative envisagée

Construire un vrai ledger/event sourcing dès le MVP.

### Décision

Refusé en Phase 1.

Le système repose actuellement sur :

```txt
Transaction = source de vérité financière
```

sans ledger complet.

### Pourquoi

Le coût de complexité était trop élevé pour un MVP démontrable.

### Conséquences

Avantages :

- développement rapide ;
- compréhension plus simple ;
- employabilité immédiate.

Inconvénients :

- certaines limites comptables ;
- reconciliation future plus complexe.

---

## 🧠 Philosophie globale

```txt
Le système préfère :

résilience
+
traçabilité
+
découplage

plutôt que :

simplicité court terme.
```

---

## 🔥 Principe fondamental

```txt
Un provider peut changer.
Le domaine doit survivre au changement.
```
