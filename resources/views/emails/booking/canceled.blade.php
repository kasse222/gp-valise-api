<x-mail::message>
# 🚫 Réservation annulée

Bonjour {{ $booking->user->first_name }},

Votre réservation **#{{ $booking->id }}** a été annulée.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}

Si vous avez effectué un paiement, un remboursement sera traité sous 3 à 5 jours ouvrés.

<x-mail::button :url="$searchUrl">
Rechercher un trajet
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>