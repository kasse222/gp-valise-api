<x-mail::message>
# ✅ Réservation acceptée

Bonjour {{ $booking->user->first_name }},

Bonne nouvelle ! Votre réservation **#{{ $booking->id }}** a été acceptée par le voyageur.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}
**Date :** {{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }}
**Poids réservé :** {{ number_format($booking->items->sum('kg_reserved') / 1000, 1) }} kg

Vous pouvez maintenant procéder au paiement. Le lien expire dans **30 minutes**.

<x-mail::button :url="$paymentUrl">
Payer maintenant
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>