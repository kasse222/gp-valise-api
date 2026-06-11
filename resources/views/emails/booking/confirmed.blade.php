<x-mail::message>
# 🎉 Paiement confirmé

Bonjour {{ $booking->user->first_name }},

Votre paiement pour la réservation **#{{ $booking->id }}** a été confirmé avec succès.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}
**Date :** {{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }}

Le voyageur a été notifié. Vous recevrez les détails de remise du colis après confirmation.

<x-mail::button :url="$dashboardUrl">
Voir ma réservation
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>
