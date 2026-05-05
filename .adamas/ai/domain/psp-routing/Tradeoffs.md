## Tradeoffs

### 1. Resolver vs if/else

| Option   | Avantages                      | Inconvénients   |
| -------- | ------------------------------ | --------------- |
| Resolver | scalable, testable, centralisé | plus complexe   |
| if/else  | simple                         | non maintenable |

👉 Choix : resolver

---

### 2. providerStatus brut

Chaque PSP a son propre vocabulaire :

- Stripe → succeeded
- Kkiapay → success
- Fake → completed

👉 Décision :

```txt
On garde providerStatus brut → mapping côté domaine
```

Avantage :

- flexibilité
- extensibilité multi-PSP

---

### 3. Fallback FakeProvider

Avantage :

- stabilité tests
- environnement local simple

Risque :

- dangereux en production

👉 Évolution prévue :

```txt
Interdire FakeProvider en production
```

---

### 4. Montants

- domaine → float lisible
- provider → integer (centimes)

👉 compromis actuel, à refactor avec ledger futur

---

### 5. Provider dans Action (temporaire)

Certains appels utilisent encore FAKE en dur.

👉 accepté en phase MVP
👉 doit disparaître avec resolver complet

---

### 🧠 Conclusion

Le système privilégie :

- découplage
- extensibilité
- robustesse

au détriment de la simplicité immédiate

```

```
