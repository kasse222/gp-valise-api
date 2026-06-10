<x-mail::message>
# ✅ Identité vérifiée

Bonjour {{ $user->first_name }},

Bonne nouvelle ! Votre identité a été **vérifiée avec succès**.

Vous pouvez maintenant accéder à toutes les fonctionnalités de Safe Move :
- Publier des trajets
- Effectuer des paiements sécurisés
- Accéder à l'escrow

<x-mail::button :url="$dashboardUrl">
Accéder à mon espace
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>