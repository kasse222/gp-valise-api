# 🧠 ARCHITECTURE GP-VALISE

## Responsabilités

- Controller = orchestration HTTP uniquement
- FormRequest = validation d’entrée HTTP
- Policy = autorisation / contrôle d’accès
- Action = un cas d’usage métier complet
- Validator = validation métier réutilisable, hors HTTP
- Enum = règles d’état et transitions
- Service = orchestration transverse rare, utilisée seulement si plusieurs actions partagent la même logique

## Interdits

- logique métier dans Controller
- logique métier dans Policy
- accès DB dans Enum
- duplication entre Action et Service
