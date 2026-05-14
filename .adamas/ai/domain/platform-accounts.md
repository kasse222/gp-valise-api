# 🏦 Platform Accounts — GP-Valise

> Comptes de trésorerie internes de la plateforme, utilisés pour le routing financier multi-corridor.

---

## 🎯 Objectif

`platform_accounts` représente les **comptes opérationnels** de GP-Valise, un par corridor (devise × pays × provider).

Ils servent à :

- router les fonds vers le bon PSP selon la devise et le pays
- suivre la position de trésorerie par corridor
- abstraire le routing financier du domaine métier

---

## ⚠️ Distinction critique : platform_accounts vs ledger_accounts

|          | `platform_accounts`                      | `ledger_accounts`                      |
| -------- | ---------------------------------------- | -------------------------------------- |
| Rôle     | Routing opérationnel + position treasury | Comptabilité double-entry              |
| Balance  | **Matérialisée** (`balance` integer)     | **Calculée** via `SUM(ledger_entries)` |
| Règle    | Balance autorisée ici                    | Balance matérialisée **interdite** là  |
| Mutation | `credit()` / `debit()`                   | Immuable après création                |

> La règle "balance matérialisée interdite" s'applique **uniquement à `ledger_accounts`**.
> `platform_accounts.balance` est intentionnellement matérialisée pour le routing opérationnel.

---

## 🧱 Structure

```php
platform_accounts
  id
  name              string       → ex: "GP-Valise Sénégal XOF"
  currency          string(3)    → CurrencyEnum (EUR, XOF)
  country_code      string(2)    → ISO 3166-1 (SN, FR, MA)
  provider          string(50)?  → nullable → ex: "kkiapay", "stripe"
  is_active         boolean      → default true
  balance           bigInteger   → integer minor units (centimes / francs)
  metadata          json?        → infos complémentaires (account_id PSP, etc.)
  created_at / updated_at
```

**Contrainte unique :** `(currency, country_code, provider)`
→ Un seul compte actif par corridor + provider.

---

## 🗺️ Comptes existants (Phase 3)

| Nom                   | Devise | Pays     | Provider |
| --------------------- | ------ | -------- | -------- |
| GP-Valise Europe EUR  | EUR    | FR/BE/DE | stripe   |
| GP-Valise Sénégal XOF | XOF    | SN       | kkiapay  |
| GP-Valise Maroc EUR   | EUR    | MA       | stripe   |

---

## 🔗 Relation avec Transaction

```php
// transactions.platform_account_id → FK nullable → platform_accounts
```

Chaque transaction peut être liée à un compte plateforme.
`nullable` : transactions legacy Phase 1-2 sans account lié.

---

## 💰 Représentation monétaire

```
integer minor units — même convention que tout le système
EUR : centimes   → 1500 = 15.00€
XOF : francs     → 150000 = 1500 XOF
```

---

## ⚙️ Méthodes du Model

```php
public function credit(int $amount): void
{
    $this->increment('balance', $amount);
}

public function debit(int $amount): void
{
    if ($amount > $this->balance) {
        throw new \RuntimeException(
            "Insufficient balance on platform account [{$this->id}]."
        );
    }
    $this->decrement('balance', $amount);
}
```

### ⚠️ Contrainte d'utilisation critique

`increment()` et `decrement()` sont atomiques au niveau SQL, mais **ne sont pas protégés contre les race conditions** si appelés en dehors d'une transaction DB avec lock.

**Règle obligatoire :** `credit()` et `debit()` doivent **toujours** être appelés depuis une Action sous `DB::transaction()` avec `lockForUpdate()` sur le compte concerné.

```php
// ✅ Pattern correct
DB::transaction(function () use ($account, $amount) {
    $account = PlatformAccount::where('id', $account->id)
        ->lockForUpdate()
        ->firstOrFail();

    $account->debit($amount);
});

// ❌ Dangereux — race condition possible
$account->debit($amount);
```

---

## 🔁 Routing PSP

Le `platform_account_id` sur une Transaction est résolu au moment de la création via `PaymentProviderResolver`.

```
PaymentProviderResolver::resolve(country, currency, method)
  → sélectionne KkiapayProvider ou StripeProvider
  → identifie le platform_account correspondant
  → lie la Transaction au bon compte
```

### Table de routing (Phase 3)

| Pays     | Devise | Méthode      | Provider | Compte                |
| -------- | ------ | ------------ | -------- | --------------------- |
| SN       | XOF    | mobile_money | kkiapay  | GP-Valise Sénégal XOF |
| FR/BE/DE | EUR    | card         | stripe   | GP-Valise Europe EUR  |
| MA       | EUR    | card         | stripe   | GP-Valise Maroc EUR   |
| fallback | —      | —            | fake     | null (dev/test)       |

---

## 🔒 Invariants

```
Un PlatformAccount inactif (is_active = false) ne doit pas recevoir
de nouvelles transactions.

balance >= 0 à tout moment (RuntimeException si debit > balance).

Unicité (currency, country_code, provider) garantie par contrainte DB.
```

---

## 🔍 Lien avec le Ledger

`platform_accounts` et `ledger_accounts` sont deux systèmes **complémentaires** :

```
platform_accounts  →  routing opérationnel (quel PSP, quelle balance dispo)
ledger_accounts    →  comptabilité (SUM debits = SUM credits, vérité comptable)
```

La balance de `platform_accounts` est une vue opérationnelle rapide.
La vérité comptable reste dans `ledger_entries`.

En cas de divergence → la vérité est dans le ledger.

---

## 🚫 Interdits

```
Appeler credit() / debit() hors DB::transaction() avec lockForUpdate()
Modifier balance directement via UPDATE SQL sans passer par le Model
Créer un PlatformAccount avec is_active = false pour une transaction active
Utiliser platform_account_id comme source de vérité financière
  → c'est Transaction qui est la source de vérité
Laisser un account sans provider en production
  → provider nullable est réservé aux tests / MVP initial
```

---

## 🧪 Tests obligatoires

| Scénario                                    | Attendu          |
| ------------------------------------------- | ---------------- |
| `credit()` incrémente la balance            | Correct          |
| `debit()` décrémente la balance             | Correct          |
| `debit()` avec montant > balance            | RuntimeException |
| Double `debit()` concurrent (lockForUpdate) | Un seul exécuté  |
| Routing résout le bon account par corridor  | Correct          |
| Transaction liée au bon platform_account_id | Correct          |

---

## 🔮 Évolution Phase 7+

```
Phase 7 — Réserves multi-devises
  reserve_balance  →  fonds bloqués / réservés
  available_balance = balance - reserve_balance

Phase 7 — Réconciliation
  balance DB ↔ balance PSP réelle
  Alerte si divergence > seuil

Phase 7 — Multi-tenant
  platform_account_id obligatoire sur toutes les transactions
  (nullable supprimé)
```

---

## 🧠 Résumé exécutif

```
platform_accounts = compte opérationnel par corridor

1 compte  = 1 (devise × pays × provider)
balance   = position treasury matérialisée (opérationnel)
            ≠ ledger_accounts (comptabilité pure)

Toute mutation de balance passe par DB::transaction() + lockForUpdate()
Transaction.platform_account_id = lien de routing

La vérité financière reste : Transaction + LedgerEntry
```
