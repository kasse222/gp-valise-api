<x-mail::message>
# Votre remboursement a été effectué

Bonjour,

Nous vous confirmons que votre remboursement a bien été traité.

**Détails du remboursement :**

| | |
|---|---|
| Réservation | #{{ $booking?->id ?? '—' }} |
| Trajet | {{ $booking?->trip?->departure ?? '—' }} → {{ $booking?->trip?->destination ?? '—' }} |
| Montant remboursé | {{ number_format($transaction->amount / ($transaction->currency?->hasSubunit() ? 100 : 1), $transaction->currency?->hasSubunit() ? 2 : 0, ',', ' ') }} {{ $transaction->currency?->value ?? '' }} |
| Date | {{ $transaction->processed_at?->format('d/m/Y à H:i') ?? now()->format('d/m/Y') }} |

Le remboursement sera crédité sur votre moyen de paiement initial sous **3 à 5 jours ouvrés** selon votre banque ou opérateur Mobile Money.

<x-mail::button :url="$dashboardUrl">
Voir ma réservation
</x-mail::button>

Si vous n'avez pas reçu votre remboursement après 5 jours ouvrés, contactez-nous à **support@safemove.tech**.

Cordialement,<br>
L'équipe {{ config('app.name') }}

---
<small>Safe Move — Paiements sécurisés pour voyageurs et expéditeurs.</small>
</x-mail::message>