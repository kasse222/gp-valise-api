<x-mail::message>
# 📦 Colis livré

Bonjour {{ $booking->user->first_name }},

Votre colis pour la réservation **#{{ $booking->id }}** a été marqué comme livré par le voyageur.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}

Si vous avez bien reçu votre colis, aucune action n'est requise. Le paiement du voyageur sera libéré automatiquement sous **48 heures**.

En cas de problème, vous pouvez ouvrir un litige depuis votre espace.

<x-mail::button :url="$dashboardUrl">
Voir ma réservation
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>