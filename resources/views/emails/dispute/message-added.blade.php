<x-mail::message>
# Nouveau message dans votre litige

Bonjour,

Vous avez reçu un nouveau message concernant le litige sur la réservation **#{{ $dispute->booking_id }}**
@if($dispute->booking) ({{ $dispute->booking->trip?->departure ?? '—' }} → {{ $dispute->booking->trip?->destination ?? '—' }}) @endif.

---

**Message de {{ $message->author?->first_name ?? 'l\'équipe Safe Move' }} :**

{{ $message->body }}

---

@if($message->attachments && count($message->attachments) > 0)
*{{ count($message->attachments) }} pièce(s) jointe(s) — consultez votre espace pour les visualiser.*
@endif

<x-mail::button :url="$dashboardUrl">
Répondre dans le fil de discussion
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}

---
<small>Safe Move — Paiements sécurisés pour voyageurs et expéditeurs.</small>
</x-mail::message>