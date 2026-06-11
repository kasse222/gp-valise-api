<x-mail::message>
# ⚠️ Litige ouvert

Bonjour {{ $booking->user->first_name }},

Un litige a été ouvert sur la réservation **#{{ $booking->id }}**.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}

Notre équipe va examiner votre dossier dans les plus brefs délais. Vous pouvez suivre l'avancement depuis votre espace.

<x-mail::button :url="$dashboardUrl">
Voir le litige
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>
