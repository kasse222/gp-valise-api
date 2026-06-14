<x-mail::message>
# 📦 Nouveau colis à transporter

Bonjour {{ $booking->trip->user->first_name }},

Un expéditeur vient de confirmer une réservation sur votre trajet. Le paiement a été validé — le colis est prêt à être remis.

---

## 📋 Détails du colis

| | |
|---|---|
| **Trajet** | {{ $trip->departure }} → {{ $trip->destination }} |
| **Date départ** | {{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }} |
| **Poids** | {{ $totalKg }} kg |
| **Contenu** | {{ $booking->bookingItems->first()?->description ?? 'Non précisé' }} |

---

## 📞 Contact expéditeur

| | |
|---|---|
| **Nom** | {{ $sender->first_name }} {{ $sender->last_name }} |
| **Téléphone** | {{ $sender->phone }} |

---

## 📦 Destinataire à destination

| | |
|---|---|
| **Nom** | {{ $booking->recipient_name }} |
| **Téléphone** | {{ $booking->recipient_phone }} |

Le destinataire recevra un QR code et un code secret au moment de la remise physique. Il devra vous le présenter pour valider la livraison.

<x-mail::button :url="$dashboardUrl">
Voir ma réservation
</x-mail::button>

Merci,
**L'équipe Safe Move**
</x-mail::message>