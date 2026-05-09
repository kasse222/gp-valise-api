# Ledger Strategy — GP-Valise

## Principe fondamental

Double-entry accounting.
Chaque mouvement financier génère deux écritures symétriques.

Invariant absolu :

```txt
∀ transaction : SUM(debits) = SUM(credits)
```

## Devise

Phase 5 MVP : EUR + XOF.
MAD : exclu pour l'instant.

## Balance

Calculée via SUM(ledger_entries) — pas de balance matérialisée.
Plus sûr, moins de bugs de synchronisation.

```sql
SELECT SUM(CASE WHEN direction = 'CREDIT' THEN amount ELSE -amount END)
FROM ledger_entries
WHERE account_id = ?
```

---

## Comptes (ledger_accounts)

```txt
TYPE ASSET
──────────────────────────────────────────────────────
external_psp_clearing_eur   Fonds reçus/envoyés via PSP (EUR)
external_psp_clearing_xof   Fonds reçus/envoyés via PSP (XOF)
escrow_eur                  Fonds clients bloqués en escrow (EUR)
escrow_xof                  Fonds clients bloqués en escrow (XOF)

TYPE LIABILITY
──────────────────────────────────────────────────────
payable_voyageur_eur        Montants dus aux voyageurs (EUR)
payable_voyageur_xof        Montants dus aux voyageurs (XOF)

TYPE REVENUE
──────────────────────────────────────────────────────
revenue_fees_eur            Commissions GP-Valise (EUR)
revenue_fees_xof            Commissions GP-Valise (XOF)

TYPE EXPENSE
──────────────────────────────────────────────────────
expense_psp_eur             Frais PSP (EUR)
expense_psp_xof             Frais PSP (XOF)
```

---

## Flows d'écritures

### CHARGE — fonds reçus, bloqués en escrow

```txt
DEBIT   external_psp_clearing_eur   10000
CREDIT  escrow_eur                  10000
```

Pas de reconnaissance revenue ici.
Les fonds sont reçus et bloqués.
La commission n'est pas encore due.

---

### PAYOUT RELEASE — après délai escrow, sans dispute

```txt
DEBIT   escrow_eur                  10000
CREDIT  payable_voyageur_eur         9000
CREDIT  revenue_fees_eur             1000
```

C'est ici que :

- la dette voyageur est reconnue
- le revenu plateforme est reconnu
- l'escrow est libéré

---

### PAYOUT PAID — dette voyageur soldée

```txt
DEBIT   payable_voyageur_eur         9000
CREDIT  external_psp_clearing_eur    9000
```

Le voyageur a été payé via PSP.
La dette est soldée.

---

### PAYMENT_FEE — coût PSP comptabilisé

```txt
DEBIT   expense_psp_eur               200
CREDIT  external_psp_clearing_eur     200
```

Comptabilisé séparément de la FEE plateforme.

---

### REFUND BEFORE PAYOUT RELEASE — remboursement avant escrow libéré

```txt
DEBIT   escrow_eur                  10000
CREDIT  external_psp_clearing_eur   10000
```

Les fonds retournent au PSP.
Aucune commission reconnue — le booking n'a pas abouti.

---

## Pourquoi ce modèle

```txt
CHARGE         = fonds reçus et bloqués
PAYOUT RELEASE = dette voyageur + revenu plateforme reconnus
PAYOUT PAID    = dette voyageur soldée
REFUND         = annulation escrow sans reconnaissance revenue
```

Cohérence escrow stricte :
tant que le délai n'est pas écoulé,
aucune dette voyageur ni revenue fee n'est reconnue.

---

## Structure tables

### ledger_accounts

```sql
id
slug          unique (ex: escrow_eur, revenue_fees_xof)
name          lisible
type          ASSET | LIABILITY | REVENUE | EXPENSE
currency      EUR | XOF
is_active     boolean
created_at
```

### ledger_entries

```sql
id
transaction_id    FK → transactions (nullable pour entrées manuelles)
account_id        FK → ledger_accounts
direction         DEBIT | CREDIT
amount            integer (minor units)
currency          EUR | XOF
description       string nullable
created_at
```

---

## Triggers de création

| Transaction type | Flow déclenché            |
| ---------------- | ------------------------- |
| CHARGE COMPLETED | CHARGE flow               |
| PAYOUT PENDING   | PAYOUT RELEASE flow       |
| PAYOUT COMPLETED | PAYOUT PAID flow          |
| PAYMENT_FEE      | PAYMENT_FEE flow          |
| REFUND COMPLETED | REFUND BEFORE PAYOUT flow |

---

## Composants cibles

```
LedgerAccount model
LedgerEntry model
LedgerWriter service     → crée les écritures
LedgerReader service     → calcule les balances
CreateLedgerEntries action (appelée depuis CreatePayoutTransaction, etc.)
```

---

## Invariants

- SUM(debits) = SUM(credits) par transaction
- Aucune écriture modifiable après création
- Toujours dans la même DB::transaction() que la Transaction financière
- currency des entries = currency du compte

---

## Anti-patterns interdits

- balance matérialisée sur ledger_accounts (Phase 5)
- écriture ledger hors DB::transaction()
- modifier une LedgerEntry existante
- créer des entries sans transaction_id (sauf ajustements admin explicites)
- calcul financier dans LedgerWriter

```

---
```
