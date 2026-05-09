# 🧠 Decision Log — GP-Valise

## 🎯 Objectif

Ce fichier trace les décisions techniques et métier importantes du projet.

Il permet de :

- comprendre pourquoi un choix a été fait ;
- éviter de redébattre les mêmes sujets ;
- aligner les décisions futures avec les choix passés ;
- guider l'IA dans ses recommandations ;
- garder une mémoire technique exploitable en entretien.

> Une décision documentée vaut mieux qu'un bon code oublié.

---

## 🧾 Format d'une décision

```txt
## [DATE] — Titre court

### Contexte
Pourquoi la décision est nécessaire.

### Décision
Choix effectué.

### Alternatives considérées
Options rejetées et pourquoi.

### Conséquences
Impacts techniques / métier.

### Statut
- ✅ actif
- 🟡 en cours
- 🔴 à revoir
- ⬛ obsolète
```

---

# 📌 Décisions actives

---

## [2026-04] — Transaction = source de vérité financière

### Contexte

Besoin d'une cohérence financière forte avec paiements async, webhooks, retry et idempotence.

### Décision

La `Transaction` est la seule source de vérité financière.

- `Payment` = vue métier / workflow utilisateur
- `Transaction` = réalité comptable atomique

### Alternatives considérées

- Stocker l'état financier dans `Booking` ❌ : couplage fort, faible traçabilité.
- Dépendre du provider comme source de vérité ❌ : fragile, async non maîtrisé.

### Conséquences

- forte traçabilité financière ;
- découplage total du provider ;
- complexité légèrement plus élevée.

### Statut

✅ actif

---

## [2026-04] — Séparation FEE / PAYMENT_FEE

### Contexte

Besoin de distinguer le revenu plateforme du coût PSP pour calculer une rentabilité réelle.

### Décision

- `FEE` = revenu GP-Valise ;
- `PAYMENT_FEE` = coût externe PSP / banque.

### Alternatives considérées

- Fusionner les deux ❌ : perte de visibilité financière.
- Ignorer les frais PSP en MVP ❌ : rentabilité faussée.

### Conséquences

- modèle financier lisible ;
- prêt pour multi-pays / multi-PSP ;
- calcul réel du profit net : `profit_net = FEE - PAYMENT_FEE`.

### Statut

✅ actif

---

## [2026-04] — FEE dynamique via FeeCalculator

### Contexte

La commission peut dépendre du pays, du type utilisateur, du volume ou de règles futures.

### Décision

Introduire un `FeeCalculator` dédié, isolé des Actions.

Le `FeeCalculator` résout le taux.
Le `TransactionAmountCalculator` applique le calcul.

### Alternatives considérées

- Hardcoder le taux dans l'Action ❌.
- Config simple globale uniquement ❌.

### Conséquences

- extensibilité forte ;
- centralisation de la logique de taux ;
- testabilité améliorée.

### Statut

🟡 en cours — interface définie, implémentation à faire

---

## [2026-04] — Montants financiers persistés à la création

### Contexte

Recalculer les montants à la volée est dangereux : un changement de taux rendrait les transactions passées incohérentes.

### Décision

Les montants calculés sont persistés en base au moment de la création.
Ils ne sont jamais recalculés après coup.

### Alternatives considérées

- Recalcul à la volée ❌.
- Calcul uniquement à l'affichage ❌.

### Conséquences

- vérité historique garantie ;
- audit possible ;
- `TransactionAmountCalculator` sert à créer, pas à reconstruire l'historique.

### Statut

✅ actif

---

## [2026-04] — PAYMENT_FEE persistée dès le MVP

### Contexte

Les frais PSP impactent la rentabilité réelle. Les ignorer en MVP créerait une dette de données.

### Décision

`PAYMENT_FEE` est persistée dès le MVP, même si estimée.

- Source idéale : webhook PSP.
- Fallback MVP : configuration locale.

### Alternatives considérées

- Ignorer en MVP ❌.
- Calcul externe non persisté ❌.

### Conséquences

- meilleure visibilité financière ;
- prêt pour PSP réel ;
- pas de migration de données douloureuse.

### Statut

✅ actif

---

## [2026-04] — FEE créée au moment du PAYOUT

### Contexte

Éviter de prélever une commission sur un booking annulé, expiré ou remboursé avant livraison.

### Décision

La `FEE` est créée lors du `PAYOUT`, pas à la `CHARGE` ni à la confirmation.

### Alternatives considérées

- Créer à la `CHARGE` ❌.
- Créer à la confirmation ❌.

### Conséquences

- meilleure cohérence escrow ;
- refund v1 simplifié ;
- commission uniquement sur livraison effective.

### Statut

✅ actif

---

## [2026-04] — Refund post-livraison : admin override avec audit

### Contexte

Un refund après livraison est exceptionnel, mais peut être nécessaire en cas de litige avéré.

### Décision

Autoriser un refund admin override uniquement si :

- admin uniquement ;
- booking `LIVREE` ou `EN_LITIGE` ;
- `CHARGE` completed ;
- aucun `REFUND` existant ;
- aucun `PAYOUT` existant ;
- raison obligatoire ;
- audit log obligatoire dans la même transaction DB.

Action dédiée : `AdminRefundTransaction`.

### Alternatives considérées

- Blocage total ❌ : faiblesse produit en cas de litige réel.
- Endpoint admin sans contrainte ❌ : risque financier majeur.

### Conséquences

- invariant `PAYOUT ⊕ REFUND` maintenu ;
- traçabilité complète ;
- défense possible en cas de contestation.

### Statut

✅ actif

---

## [2026-04] — Refund limité en MVP

### Contexte

Le refund partiel, multi-refund et compensation post-payout sont complexes.

### Décision

MVP :

- refund total uniquement ;
- un seul refund par booking ;
- `PAYMENT_FEE` non remboursable ;
- `refund_possible = CHARGE - FEE`.

### Alternatives considérées

- Refund partiel ❌.
- Refund automatique post-livraison ❌.

### Conséquences

- système stable ;
- moins de risque ;
- base saine pour phases suivantes.

### Statut

✅ actif

---

## [2026-04] — Webhook idempotent via event_id

### Contexte

Les providers peuvent envoyer plusieurs fois le même événement.

### Décision

Idempotence basée sur `event_id` :

- stocké dans `webhook_logs` ;
- vérifié avant traitement ;
- combiné avec `lockForUpdate()`.

### Alternatives considérées

- Pas d'idempotence ❌.
- Idempotence faible ❌.

### Conséquences

- évite double refund / double traitement ;
- robustesse async ;
- sécurité financière.

### Statut

✅ actif

---

## [2026-04] — Booking indépendant du provider PSP

### Contexte

Le provider de paiement peut changer. Le `Booking` ne doit pas dépendre de Stripe, CMI, Wave ou autre.

### Décision

Le `Booking` ne référence jamais directement un provider.
Le lien se fait via `Transaction`.

### Alternatives considérées

- Lier Booking au provider ❌.

### Conséquences

- découplage ;
- multi-PSP ready ;
- meilleure testabilité.

### Statut

✅ actif

---

## [2026-05] — Architecture .adamas v2 modulaire

### Contexte

La structure initiale `.adamas/ai/context` et `.adamas/ai/capabilities` devenait trop floue avec la montée en maturité du projet.

### Décision

Adopter une structure v2 séparant clairement :

```txt
.adamas/ai/
├── core/              → prompt système, contraintes IA
├── domain/            → vérité métier
├── engineering/       → règles de code, review, git
├── governance/        → méthodologie, décisions
├── observability/     → correlation_id, logging, monitoring
└── security/          → règles sécurité et finance sensible
```

### Alternatives considérées

- Garder `context/` ❌ : trop vague.
- Fichier unique ❌ : non maintenable.

### Conséquences

- meilleure lisibilité ;
- IA mieux guidée ;
- séparation claire entre métier, méthode, code, sécurité et observabilité.

### Statut

✅ actif

---

## [2026-05] — AuditLog append-only

### Contexte

Les actions sensibles admin doivent être consultables et défendables en cas de litige.

### Décision

`AuditLog` est append-only :

- pas d'`updated_at` ;
- update/delete interdits au niveau Model ;
- suppression d'un actor/user ne supprime pas les logs ;
- consultation admin uniquement.

### Alternatives considérées

- AuditLog modifiable ❌.
- AuditLog supprimable avec user ❌.

### Conséquences

- historique durable ;
- meilleure conformité ;
- traçabilité renforcée.

### Statut

✅ actif

---

## [2026-05] — AuditLog integrity hash chain

### Contexte

Un audit append-only protège contre les modifications applicatives, mais pas contre une modification directe en base.

### Décision

Ajouter une chaîne d'intégrité :

- `integrity_hash` ;
- `previous_hash` ;
- `AuditLogIntegrityService`.

Chaque hash couvre les champs critiques du log et le hash précédent.
`seal()` est appelé à chaque création d'AuditLog en production.

```bash
# Vérification complète de la chaîne
app(\App\Services\AuditLogIntegrityService::class)->verifyChainFrom(0)
# => true
```

### Alternatives considérées

- Hash simple par ligne ❌ : détecte une modification isolée mais pas une rupture de chaîne.
- Pas de hash ❌ : insuffisant pour un système fintech-like.

### Conséquences

- détection de modification frauduleuse ;
- preuve technique forte ;
- base pour signature cryptographique future.

### Statut

✅ actif

---

## [2026-05] — Correlation ID pour observabilité

### Contexte

Les flux async rendent les incidents difficiles à reconstruire sans identifiant de corrélation.

### Décision

Introduire un `correlation_id` propagé sur tout le flow :

```txt
HTTP request
→ X-Correlation-ID (header réponse)
→ logs Laravel
→ ProcessPaymentWebhook Job
→ webhook_logs.correlation_id
→ transactions.correlation_id
→ audit_logs.correlation_id
```

### Alternatives considérées

- Logs classiques sans corrélation ❌.
- Dépendre uniquement des IDs métiers ❌ : insuffisant pour tracer un flux complet.

### Conséquences

- traçabilité complète API → Job → DB ;
- débogage plus rapide ;
- préparation à une observabilité distribuée.

### Statut

✅ actif

---

## [2026-05] — AdminRefundTransaction restreint à EN_LITIGE uniquement

### Contexte

La spec `.adamas/ai/domain/payment.md` indique que le refund admin override
est autorisé pour les statuts `LIVREE` ou `EN_LITIGE`. Le code actuel
`AdminRefundTransaction::execute()` refuse si le statut n'est pas `EN_LITIGE`.

### Décision

Restreindre volontairement à `EN_LITIGE` en MVP.
Un booking `LIVREE` avec payout déclenché serait de toute façon bloqué
par l'invariant `PAYOUT ⊕ REFUND`. Mais un booking `LIVREE` sans payout
reste techniquement remboursable — ce cas est exclu délibérément en MVP
pour limiter les opérations admin à risque.

### Alternatives considérées

- Autoriser `LIVREE` + `EN_LITIGE` comme la spec : plus conforme, mais expose
  à des remboursements sur des bookings livrés sans litige formalisé.
- Blocage total post-livraison : trop restrictif.

### Conséquences

- Remboursement admin sur `LIVREE` impossible sans passer par `EN_LITIGE`.
- Workflow : forcer le booking en `EN_LITIGE` avant tout remboursement admin.
- Décision à réévaluer quand le dispute system sera implémenté.

### Statut

✅ actif — réévaluer avec dispute system (Phase 6)

---

## [2026-05] — Routing PSP corridor Sénégal-Maroc-Europe

### Contexte

GP-Valise opère sur le corridor Sénégal-Maroc avec des utilisateurs européens.
Les moyens de paiement dominants varient fortement selon le pays :

- Sénégal / CEDEAO : Orange Money, Wave (mobile money)
- Maroc : cash pickup via agences (Wafacash)
- Europe : cartes bancaires (Stripe)

Stripe seul est insuffisant — il ne supporte pas le mobile money,
qui est la norme locale sur le corridor principal.

### Décision

Routing PSP par pays / devise / méthode :

| Marché           | Provider retenu          | Moyens couverts    |
| ---------------- | ------------------------ | ------------------ |
| Sénégal / CEDEAO | Kkiapay ou KAYBIC Africa | Orange Money, Wave |
| Maroc            | Wafacash                 | Cash pickup        |
| Europe           | Stripe                   | Cartes bancaires   |

Architecture cible :

```php
class PaymentProviderResolver
{
    public function resolve(string $country, string $currency, string $method): PaymentProvider
    {
        return match(true) {
            $currency === 'XOF'                          => app(KkiapayProvider::class),
            $currency === 'MAD' && $method === 'cash'    => app(WafacashProvider::class),
            in_array($country, ['FR', 'BE', 'DE', 'ES']) => app(StripeProvider::class),
            default                                       => app(KkiapayProvider::class),
        };
    }
}
```

### Providers écartés

**CinetPay** — non retenu.
Risque opérationnel élevé : des médias spécialisés ont rapporté une cyberattaque
et un backlog de paiements supérieur à 1M$ envers les commerçants.
Insuffisant pour un système financier qui doit inspirer confiance dès le départ.

**GeniusPay** — à risque / non retenu en Phase 1.
Positionnement intéressant mais manque de recul sur la stabilité opérationnelle.
À réévaluer en Phase 2 si les retours terrain sont positifs.

**Stripe** — exclu du corridor Afrique.
Pas de support natif mobile money (Orange Money, Wave).
Repositionné en fallback Europe uniquement.

### Conséquences

- `PaymentProvider` reste une interface — routing au runtime via `PaymentProviderResolver`
- `platform_accounts` nécessaire pour gérer XOF / MAD / EUR (Phase 3)
- Change XOF → MAD géré en interne par la plateforme
- Implémentation sandbox Kkiapay prévue en Phase 2

### Statut

🟡 en cours — sandbox Kkiapay à implémenter (Phase 2)

---

## [2026-05] — Roadmap produit en 6 phases

### Contexte

Le projet nécessite une feuille de route claire pour éviter de mélanger
les enjeux MVP, PSP réel, escrow, ledger et disputes.

### Décision

```txt
Phase 1 — MVP démontrable          ✅ terminé
  FakeProvider / sandbox
  PaymentProvider interface
  Transactions propres
  correlation_id + audit logs
  DemoSeeder + README + démo

Phase 2 — Routing PSP réel         🟡 en cours
  PaymentProviderResolver
  config PSP par pays/devise/method
  Kkiapay sandbox
  docs PSP dans .adamas

Phase 3 — platform_accounts        ⏳ à venir
  table platform_accounts
  comptes EUR / XOF / MAD
  routing financier automatique

Phase 4 — Escrow avancé            ⏳ à venir
  fonds reçus / bloqués
  payout différé
  refund conditionnel
  dispute bloque payout

Phase 5 — Ledger interne           ⏳ à venir
  écritures comptables internes
  soldes / mouvements liés
  historique complet
  audit financier

Phase 6 — Dispute system complet   ⏳ à venir
  ouverture litige
  preuves / arbitrage admin
  décision refund / payout / compensation
  audit obligatoire
```

### Conséquences

- chaque phase est démontrable indépendamment ;
- pas de `platform_accounts` obligatoire avant Phase 3 ;
- PSP réel sandbox possible dès Phase 2 sans entité juridique.

### Statut

✅ actif

---

## [2026-04] — platform_accounts : migration progressive

### Contexte

Besoin futur de multi-comptes bancaires : Maroc, Sénégal, Europe, PSP multiples.

### Décision

Migration progressive :

1. MVP : user technique plateforme par devise ;
2. Phase 3 : table `platform_accounts` avec colonnes `provider`, `country_code`, `currency`, `account_type`, `is_active`, `metadata` ;
3. Futur : `transactions.user_id` → `platform_account_id`.

### Alternatives considérées

- Migration directe ❌.
- Ignorer multi-comptes ❌.

### Conséquences

- faible risque immédiat ;
- base multi-pays posée ;
- compatible roadmap Phase 3.

### Statut

🟡 en cours — table à créer en Phase 3

---

## [2026-05] — Bug C3 : CONFIRMEE → REMBOURSEE absent de allowedTransitions()

### Contexte

`HandlePaymentWebhook::handleSuccess()` est appelé quelle que soit la source
du refund — standard (`RefundTransaction`) ou admin (`AdminRefundTransaction`).
Il tente `$booking->transitionTo(REMBOURSEE)` sur réception de `refund.completed`.

`BookingStatusEnum::allowedTransitions()` ne définissait que `EN_LITIGE → REMBOURSEE`.
La transition `CONFIRMEE → REMBOURSEE` était absente.

Conséquence : le webhook recevait `refund.completed`, tentait `CONFIRMEE → REMBOURSEE`,
levait une `DomainException`, marquait le webhook `FAILED`, retryait 5 fois,
échouait définitivement. Le provider avait remboursé. La `Transaction` était `COMPLETED`.
Le `Booking` restait `CONFIRMEE`. Incohérence financière garantie.

### Décision

Ajouter `CONFIRMEE → REMBOURSEE` dans `allowedTransitions()` de `BookingStatusEnum`.
Formaliser `canBeRefunded()` comme garde-fou obligatoire avant toute création de `REFUND` :

```php
public function canBeRefunded(): bool
{
    return in_array($this, [
        self::CONFIRMEE,
        self::EN_LITIGE,
    ], true);
}
```

Documenter la table complète des transitions autorisées dans `booking.md`.

### Alternatives considérées

- Handler séparé par source de refund ❌ : duplication, risque de divergence.
- Vérifier la source dans `handleSuccess()` ❌ : couplage inverse, fragile.

### Conséquences

- `handleSuccess()` fonctionne correctement pour les deux chemins de refund ;
- `canBeRefunded()` bloque toute création de `REFUND` sur un statut invalide ;
- table des transitions désormais explicite et testable dans `booking.md` ;
- test du scénario complet ajouté : webhook `refund.completed` sur booking `CONFIRMEE`.

### Fichiers impactés

- `app/Enums/BookingStatusEnum.php`
- `tests/Feature/Transaction/Actions/...` (scénario complet webhook)
- `.adamas/ai/domain/booking.md` (table transitions + canBeRefunded)
- `.adamas/ai/domain/payment.md` (note handleSuccess agnostique à la source)

### Statut

✅ actif — corrigé en [2026-05]

---

## [2026-05] — Pattern create() → seal() dans la même DB::transaction()

### Contexte

`AdminRefundTransaction` crée un `AuditLog` puis doit le sceller via
`AuditLogIntegrityService::seal()`. La question d'implémentation : où et quand
appeler `seal()` par rapport à la transaction DB ?

Appeler `seal()` après la fermeture de `DB::transaction()` signifie que le log
est déjà persisté quand le hash est calculé. Le `save()` subséquent viole
l'immutabilité de l'audit log et crée une fenêtre de corruption si le process
est interrompu entre les deux.

### Décision

`seal()` est appelé immédiatement après `create()`, à l'intérieur de la même
`DB::transaction()`, avant le `return`. `AuditLogIntegrityService` est injecté
en constructeur readonly de l'Action (auto-résolu par le container Laravel).

Pattern canonique :

```php
public function __construct(
    private readonly AuditLogIntegrityService $auditLogIntegrityService,
) {}

// Dans DB::transaction() :
$auditLog = AuditLog::query()->create([...]);
$this->auditLogIntegrityService->seal($auditLog);
return $refund;
```

Règle dérivée : aucun `save()` sur un `AuditLog` existant (`$exists === true`)
n'est autorisé en dehors de `seal()`.

### Alternatives considérées

- `seal()` après fermeture de transaction ❌ : fenêtre de corruption, violation d'immutabilité.
- Observer Model pour auto-seal ❌ : couplage implicite, difficile à tester isolément.

### Conséquences

- atomicité garantie : refund + audit log + hash dans une seule transaction ;
- si la transaction DB échoue, aucun log partiel n'est persisté ;
- pattern documenté dans `__audit.md` comme règle d'architecture ;
- deux tests ajoutés : premier log (previous_hash null), chaîne de deux logs.

### Fichiers impactés

- `app/Actions/Transaction/AdminRefundTransaction.php`
- `tests/Feature/Transaction/Actions/AdminRefundTransactionTest.php`
- `.adamas/ai/governance/audit.md` (pattern + règle critique)

### Statut

✅ actif — implémenté en [2026-05]

---

# 🔮 Décisions à documenter

## Phase 2

- Choix final entre Kkiapay et KAYBIC Africa après test sandbox
- Format webhook Kkiapay vs HandlePaymentWebhook existant

## Phase 3

- Structure exacte de `platform_accounts`
- Stratégie de change XOF → MAD

## Phase 4

- Mécanisme de blocage escrow
- Conditions de libération du payout

## Phase 5

- Structure du ledger interne
- `parent_transaction_id` actif ou non

## Phase 6

- Workflow arbitrage litige
- Conditions de compensation post-payout

---

## 🧠 Principe clé

> Une décision non documentée est une dette future.
> Une décision documentée est un accélérateur.

## [2026-05] — Fondation PSP routing typée

### Contexte

La Phase 2 nécessite de préparer l’intégration de PSP réels sans coupler le domaine métier à Stripe, Kkiapay ou un autre provider.

Avant cette décision, certaines Actions échangeaient encore des `array` ou un ancien `PaymentResult` avec le provider, ce qui rendait le contrat fragile et difficile à étendre.

### Décision

Mettre en place une fondation PSP typée basée sur :

- `PaymentProvider` comme contrat unique ;
- `PaymentProviderResolver` pour sélectionner le provider ;
- `config/payment_providers.php` pour déclarer le routing ;
- DTOs stricts :
    - `PaymentRequestData`
    - `PaymentResponseData`
    - `RefundRequestData`
    - `PaymentEventData`
    - `WebhookVerificationData`
- providers préparés :
    - `FakePaymentProvider`
    - `KkiapayProvider`
    - `StripeProvider`

Le routing actuel est :

```txt
SN + mobile_money → Kkiapay
SN + card         → Kkiapay
MA + card         → Stripe
FR + card         → Stripe
fallback          → FakeProvider
```

### Alternatives considérées

- Garder les `array` dans les Actions ❌ : contrat faible, erreurs runtime.
- Appeler directement Stripe/Kkiapay dans les Actions ❌ : couplage fort.
- Mettre le routing dans les Controllers ❌ : mauvaise séparation des responsabilités.
- Intégrer Kkiapay immédiatement sans foundation ❌ : risque de dette technique.

### Conséquences

- Les Actions ne manipulent plus de payload provider brut.
- Le domaine reste indépendant des PSP.
- Le système devient prêt pour multi-provider.
- Les tests du resolver couvrent :
    - routing Sénégal mobile money ;
    - routing Maroc carte ;
    - fallback fake ;
    - pays lowercase ;
    - provider manquant ;
    - provider ne respectant pas le contrat.

- Les providers réels restent à implémenter en sandbox dans les phases suivantes.

### Fichiers impactés

- `app/Contracts/Payments/PaymentProvider.php`
- `app/Data/Payments/*`
- `app/Enums/PaymentProviderEnum.php`
- `app/Enums/PaymentMethodEnum.php`
- `app/Enums/PaymentOperatorEnum.php`
- `app/Services/Payments/PaymentProviderResolver.php`
- `app/Services/Payments/FakePaymentProvider.php`
- `app/Services/Payments/KkiapayProvider.php`
- `app/Services/Payments/StripeProvider.php`
- `config/payment_providers.php`
- `tests/Unit/Payments/*`
- `.adamas/ai/domain/psp-routing/*`

### Statut

✅ actif — Phase 2A validée avec 263 tests / 682 assertions

## [2026-05] — Normalisation webhook avant dispatch

### Contexte

Le `WebhookController` passait initialement le payload brut PSP directement
à `ProcessPaymentWebhook`. `HandlePaymentWebhook` devait alors interpréter
des formats hétérogènes selon le provider.

Kkiapay envoie `transactionId` / `event` / `isPaymentSucces`.
Le domaine attend `event_id` / `event_type` / `provider_transaction_id`.
Couplage PSP → domaine garanti sans normalisation.

### Décision

Normalisation obligatoire avant dispatch :

```txt
WebhookController
  → WebhookProcessor::process()
      → verifyWebhook()
      → normalizeWebhook() → PaymentEventData
  → ProcessPaymentWebhook::dispatch(payload normalisé)
```

Payload dispatché = vocabulaire domaine uniquement :
`event_id`, `event_type`, `provider`, `provider_transaction_id`,
`provider_status`, `amount`, `currency`, `metadata`, `raw_payload`.

### Alternatives considérées

- Normalisation dans `HandlePaymentWebhook` ❌ : couplage PSP dans le domaine.
- Middleware HTTP ❌ : accès limité au provider résolu.
- Normalisation dans le Job ❌ : dépendance PSP dans la queue.

### Conséquences

- `HandlePaymentWebhook` est agnostique au PSP ;
- `WebhookProcessor` est testable unitairement sans HTTP ;
- ajout d'un nouveau PSP = implémenter `normalizeWebhook()` uniquement ;
- route `/webhooks/{providerKey}` remplace `/webhooks/payment`.

### Fichiers impactés

- `app/Services/Payments/WebhookProcessor.php` (nouveau)
- `app/Contracts/Payments/WebhookProcessorContract.php` (nouveau)
- `app/Contracts/Payments/PaymentProviderResolverContract.php` (nouveau)
- `app/Http/Controllers/Api/V1/WebhookController.php`
- `app/Actions/Payment/HandlePaymentWebhook.php`
- `app/Data/Payments/PaymentEventData.php` (eventType ajouté)
- `routes/api.php`

### Statut

✅ actif — implémenté en [2026-05]

---

## [2026-05] — FakeProvider interdit en production

### Contexte

`FakePaymentProvider` simule les paiements sans appel PSP réel.
Aucun guard n'empêchait son utilisation en production si le routing
ou la config pointait vers lui.

### Décision

Double protection :

1. `FakePaymentProvider::charge()` et `refund()` lèvent `RuntimeException`
   si `app()->environment('production')`.
2. `PaymentProviderResolver::resolve()` et `resolveByKey()` lèvent
   `RuntimeException` si `$providerKey === 'fake'` en production.

Le guard dans le provider est la dernière ligne de défense.
Le guard dans le resolver coupe court avant instanciation.

### Alternatives considérées

- Guard uniquement en config ❌ : contournable par erreur de config.
- Guard uniquement dans le resolver ❌ : provider instanciable directement.
- Supprimer FakeProvider en production ❌ : casse les pipelines CI/CD.

### Conséquences

- `FakeProvider` reste disponible en `testing` et `local` ;
- double protection indépendante ;
- test unitaire couvre le scénario production.

### Fichiers impactés

- `app/Services/Payments/FakePaymentProvider.php`
- `app/Services/Payments/PaymentProviderResolver.php`
- `tests/Unit/Payments/FakePaymentProviderTest.php`

### Statut

✅ actif — implémenté en [2026-05]

````

## [2026-05] — Confirmation automatique après paiement PSP

### Contexte

Deux logiques coexistaient dans le système :
- `ConfirmBooking` = confirmation manuelle par le voyageur (guards utilisateur + capacité)
- `HandlePaymentWebhook::handleChargeSuccess()` = confirmation automatique par preuve PSP

La question métier : le voyageur doit-il valider explicitement après que l'expéditeur a payé ?

### Décision

En MVP : `transaction.success` → `Booking CONFIRMEE` automatiquement.
Le paiement PSP vaut acceptation implicite du voyageur.

`ConfirmBooking` reste disponible comme action manuelle (admin / edge case)
mais n'est plus le chemin principal en production Kkiapay.

### Pourquoi

- simplification du flow expéditeur
- réduction de la complexité escrow
- réduction des états intermédiaires
- accélération du MVP
- cohérence avec les marketplaces classiques (le voyageur a publié = accord implicite)

### Limites

- le voyageur ne valide pas explicitement après paiement
- aucun mécanisme de refus voyageur en MVP
- si le voyageur veut refuser : passage manuel en `EN_LITIGE`

### Évolution future — Phase 4 (Escrow)

```txt
EN_PAIEMENT
→ EN_ATTENTE_VALIDATION  (déclenché par transaction.success)
→ CONFIRMEE              (voyageur accepte, délai X heures)
→ ANNULE + REFUND        (voyageur refuse)
````

Implique : nouveau statut DB, nouvelle action `AcceptBooking` / `RejectBooking`,
refacto `HandlePaymentWebhook`, window de validation avec expiration.

### Alternatives considérées

- Double validation immédiate ❌ : trop complexe pour MVP, 280 tests à revalider.
- Blocage côté voyageur sans remboursement ❌ : inacceptable produit.

### Conséquences

- `transaction.success` → `CONFIRMEE` est le seul chemin automatique en production
- `ConfirmBooking` garde ses guards mais n'est plus le chemin principal
- la transition `EN_PAIEMENT → CONFIRMEE` reste valide dans `BookingStatusEnum`
- à réévaluer obligatoirement en Phase 4 (Escrow avancé)

### Statut

✅ actif — MVP
⏳ à réévaluer — Phase 4 Escrow

````
## [2026-05] — Migration integer units — Phase 3D

### Contexte

Le domaine utilisait `float` et `decimal` pour les montants financiers,
les poids et les dimensions. Incompatible avec une architecture fintech-grade :
erreurs d'arrondi, arithmetic non déterministe, incompatibilité ledger future.

### Décision

Migration complète vers integer minor units sur toutes les colonnes critiques :

| Domaine          | Colonne              | Avant          | Après                          |
| ---------------- | -------------------- | -------------- | ------------------------------ |
| Finance          | transactions.amount  | decimal(10,2)  | bigInteger (centimes)          |
| Capacité trajet  | trips.capacity       | float          | integer (grammes)              |
| Prix trajet      | trips.price_per_kg   | decimal(8,2)   | integer (centimes/kg)          |
| Poids item       | booking_items.kg_reserved | float     | integer (grammes)              |
| Prix item        | booking_items.price  | decimal(8,2)   | integer (centimes)             |
| Poids valise     | luggages.weight_kg   | float          | integer (kg×10, 25 = 2.5kg)   |
| Dimensions       | luggages.dimensions  | float          | integer (cm)                   |

Règles de conversion :

```txt
centimes  : 1500 = 15.00€
grammes   : 25000 = 25kg
kg × 10   : 25 = 2.5kg (précision 0.1kg)
cm entiers: 60 = 60cm
````

Renommages Trip model :

```txt
kgReserved()   → gramsReserved(): int
kgDisponible() → gramsDisponible(): int
canAcceptKg()  → canAcceptGrams(int $grams): bool
```

DB engine migré : MySQL 8.0 → PostgreSQL 16 Alpine.
Note PostgreSQL : `unsignedInteger` non portable → `integer` avec contrainte applicative.

### Alternatives considérées

- Conserver float en MVP ❌ : dette irréversible sur les calculs financiers.
- Money Value Object ❌ : sur-ingénierie pour le MVP.
- Migration progressive ❌ : incohérence temporaire dangereuse.

### Conséquences

- `TransactionAmountCalculator` : toutes méthodes retournent `int`
- `BookingValidator.validateCapacity` : grammes
- `TripResource` : `kgDisponible` → `gramsDisponible`, suppression de `round()`
- `CanBeReserved` : `gramsDisponible()`
- `ConfirmBooking` : `gramsReserved()`
- Tous les tests migrés vers integer amounts
- 308 tests / 798 assertions — tout vert

### Statut

✅ actif — Phase 3D complété

---

## [2026-05] — Escrow release — Phase 4 foundation

### Contexte

`LIVREE` déclenchait un payout immédiat via `CreatePayoutAfterBookingDelivered`.
Aucun délai de protection pour l'expéditeur en cas de litige post-livraison.
Architecture fragile : payout immédiat = impossible de rembourser si problème constaté après.

### Décision

```txt
LIVREE ≠ payout immédiat
LIVREE = début de période escrow (48h par défaut)
```

Nouveaux champs `bookings` :

| Champ                  | Type               | Rôle                              |
| ---------------------- | ------------------ | --------------------------------- |
| `delivered_at`         | timestamp nullable | Moment de livraison               |
| `escrow_releasable_at` | timestamp nullable | delivered_at + escrow_delay_hours |
| `disputed_at`          | timestamp nullable | Bloque escrow si renseigné        |

Invariant payout :

```txt
booking.status = LIVREE
AND escrow_releasable_at <= now()
AND disputed_at IS NULL
AND charge COMPLETED EXISTS
AND no REFUND EXISTS
AND no PAYOUT EXISTS
AND no FEE EXISTS
```

Architecture :

```txt
CompleteBooking → LIVREE + markDelivered()
CreatePayoutAfterBookingDelivered → vide (commenté)
scheduler hourly → escrow:release-payouts
→ ReleaseEscrowBatch::execute()
→ ReleaseEscrowPayoutJob::dispatch($booking)
→ CreatePayoutTransaction::execute()
```

Config :

```env
GPVALISE_ESCROW_DELAY_HOURS=48
```

### Alternatives considérées

- Job différé `dispatch()->delay(48h)` ❌ : fragile si worker redémarre, job perdu.
- Double validation expéditeur + voyageur ❌ : friction trop élevée MVP.
- Payout immédiat maintenu ❌ : aucune protection post-livraison.

### Conséquences

- `TransactionEligibilityService.canCreatePayout()` : + `isEscrowReleasable()`
- `CreatePayoutAfterBookingDelivered` : vidé — plus de payout immédiat
- `CompleteBookingTest` : payout non créé immédiatement, escrow timestamps vérifiés
- `CreatePayoutTransactionTest` : + cas escrow non libérable + dispute active
- 318 tests / 812 assertions — tout vert
- Dispute system (Phase 4 suite) : `disputed_at` setter + `OpenDispute` action

### Pourquoi scheduler plutôt que job différé

Un job différé `delay(48h)` est perdu si le worker redémarre.
Le scheduler horaire est idempotent, rejouable, observable via Horizon.
`ReleaseEscrowBatch` re-scanne et est sans effet si le payout existe déjà.

### Statut

✅ actif — Phase 4 foundation complétée
⏳ Phase 4 suite — dispute system + OpenDispute action

---

## [2026-05] — PostgreSQL 16 comme DB principale

### Contexte

MySQL 8.0 était utilisé en dev. Pour un système fintech-grade avec escrow,
ledger et transactions complexes, PostgreSQL offre de meilleures garanties :
MVCC strict, types stricts (uuid, bigint), meilleur support des contraintes.

### Décision

Migrer vers PostgreSQL 16 Alpine en dev/prod.
Tests : SQLite in-memory conservé (performance, isolation).

Points d'attention PostgreSQL identifiés :

- `Str::random()` → `Str::uuid()` dans les factories (uuid strict)
- `unsignedInteger` non portable → `integer` avec contrainte applicative
- Séparation `Schema::table` + `renameColumn` en étapes distinctes

### Alternatives considérées

- Garder MySQL 8.0 ❌ : moins adapté à l'évolution ledger.
- Utiliser PostgreSQL uniquement en prod ❌ : divergence env dev/prod dangereuse.

### Conséquences

- `docker-compose.yml` : MySQL → PostgreSQL 16 Alpine
- `Dockerfile` : `pdo_mysql` → `pdo_pgsql` + `libpq-dev`
- `.env.docker` : `DB_CONNECTION=pgsql`
- `PaymentFactory` : uuid corrigé

### Statut

✅ actif — Phase 3C complétée

```

```
