<x-mail::message>
# ❌ Réservation refusée

Bonjour {{ $booking->user->first_name }},

Votre réservation **#{{ $booking->id }}** a été refusée par le voyageur.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}

Ne vous inquiétez pas, d'autres voyageurs sont disponibles pour votre destination.

<x-mail::button :url="$searchUrl">
Rechercher un autre trajet
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>