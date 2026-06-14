<x-mail::message>
# 🚫 Réservation annulée

Bonjour {{ $booking->user->first_name }},

Votre réservation **#{{ $booking->id }}** a été annulée.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}
**Raison :** {{ $booking->cancel_reason ?? 'Non précisée' }}

---

## 💰 Remboursement

@if($refundRate === 100)
Vous serez remboursé **intégralement (100%)** sous 3 à 5 jours ouvrés.
@elseif($refundRate === 70)
Conformément à nos conditions d'annulation (moins de 48h avant le départ), vous serez remboursé à **70%** du montant payé sous 3 à 5 jours ouvrés.
@elseif($refundRate === 0)
En raison d'un no-show, **aucun remboursement** ne sera effectué conformément à nos conditions générales.
@else
Un remboursement de **{{ $refundRate }}%** sera traité sous 3 à 5 jours ouvrés.
@endif

<x-mail::button :url="$searchUrl">
Rechercher un trajet
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>