<x-mail::message>
# Mise à jour de votre litige

Bonjour,

Le statut de votre litige concernant la réservation **#{{ $booking->id }}** ({{ $booking->trip?->departure ?? '—' }} → {{ $booking->trip?->destination ?? '—' }}) a été mis à jour.

**Nouveau statut :** {{ $dispute->status?->label() ?? $dispute->status }}

@if($dispute->messages()->exists())
Un membre de l'équipe Safe Move a pris en charge votre dossier. Vous pouvez suivre l'évolution et communiquer directement depuis votre espace.
@endif

<x-mail::button :url="$dashboardUrl">
Voir le litige
</x-mail::button>

Si vous avez des questions, répondez directement à cet email ou contactez notre équipe.

Cordialement,<br>
L'équipe {{ config('app.name') }}

---
<small>Safe Move — Paiements sécurisés pour voyageurs et expéditeurs.</small>
</x-mail::message>