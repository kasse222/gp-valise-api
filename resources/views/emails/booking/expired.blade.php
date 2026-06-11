<x-mail::message>
# ⏱ Délai de paiement expiré

Bonjour {{ $booking->user->first_name }},

Le délai de paiement pour votre réservation **#{{ $booking->id }}** a expiré.

**Trajet :** {{ $booking->trip->departure }} → {{ $booking->trip->destination }}

Vous pouvez créer une nouvelle réservation si le trajet est encore disponible.

<x-mail::button :url="$searchUrl">
Rechercher un trajet
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>