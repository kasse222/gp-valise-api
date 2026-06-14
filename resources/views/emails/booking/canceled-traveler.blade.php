<x-mail::message>
# 🚫 Réservation annulée sur votre trajet

Bonjour {{ $booking->trip->user->first_name }},

L'expéditeur **{{ $sender->first_name }} {{ $sender->last_name }}** a annulé sa réservation sur votre trajet.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}
**Date :** {{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }}
**Raison :** {{ $booking->cancel_reason ?? 'Non précisée' }}

La capacité correspondante a été libérée sur votre trajet et est à nouveau disponible pour d'autres expéditeurs.

<x-mail::button :url="$dashboardUrl">
Voir mon trajet
</x-mail::button>

Merci,
**L'équipe Safe Move**
</x-mail::message>