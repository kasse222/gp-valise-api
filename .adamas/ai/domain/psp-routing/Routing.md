## Routing

Le routing PSP permet de sélectionner dynamiquement le provider selon le contexte métier.

---

### 🔁 Entrées

Le routing se base sur :

- country (ISO)
- method (PaymentMethodEnum)

---

### ⚙️ Configuration

```php
config/payment_providers.php
```

Exemple :

```php
'routing' => [
    'SN' => [
        'mobile_money' => 'kkiapay',
        'card' => 'kkiapay',
    ],
    'MA' => [
        'card' => 'stripe',
    ],
    'FR' => [
        'card' => 'stripe',
    ],
],
```

---

### 🧠 Résolution

```php
$provider = $resolver->resolve($request);
```

Étapes :

1. Normalisation country (`trim + uppercase`)
2. Recherche mapping (country + method)
3. Fallback vers provider par défaut
4. Résolution via container Laravel

---

### 🔁 Fallback

```php
'default' => 'fake'
```

👉 garantit un comportement stable en dev/test.

---

### 🚀 Extension future

Le routing pourra évoluer vers :

- routing par devise
- routing par montant
- routing par disponibilité provider
- failover multi-PSP

```

```
