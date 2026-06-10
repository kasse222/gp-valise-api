<x-mail::message>
# Dossier KYC reçu

Bonjour {{ $user->first_name }},

Nous avons bien reçu votre dossier de vérification d'identité.

Notre équipe va l'examiner dans les **24 à 48 heures**. Vous recevrez un email dès qu'une décision sera prise.

<x-mail::button :url="$dashboardUrl">
Voir mon profil
</x-mail::button>

Merci de votre confiance,
**L'équipe Safe Move**
</x-mail::message>