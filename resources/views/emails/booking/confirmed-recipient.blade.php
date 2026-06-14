<x-mail::message>
# 📦 Un colis arrive pour vous

Bonjour {{ $booking->recipient_name }},

{{ $sender->first_name }} {{ $sender->last_name }} vous envoie un colis via Safe Move.

---

## 🚀 Détails du trajet

| | |
|---|---|
| **De** | {{ $trip->departure }} |
| **Vers** | {{ $trip->destination }} |
| **Date d'arrivée prévue** | {{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }} |
| **Poids** | {{ $totalKg }} kg |

---

## ℹ️ Que se passe-t-il ensuite ?

Lorsque le colis arrivera à destination, vous recevrez un **code secret** et un **QR code** par email.

Présentez-les au voyageur pour récupérer votre colis en toute sécurité.

---

Vous n'attendez pas de colis ? Contactez-nous immédiatement.

Merci,
**L'équipe Safe Move**
</x-mail::message>