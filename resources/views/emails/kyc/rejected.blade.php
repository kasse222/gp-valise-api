<x-mail::message>
# Dossier KYC non validé

Bonjour {{ $user->first_name }},

Nous n'avons pas pu valider votre dossier de vérification d'identité.

@if($reason)
**Motif :** {{ $reason }}
@endif

Vous pouvez soumettre un nouveau dossier en vous assurant que :
- La pièce d'identité est lisible et non expirée
- La photo est nette et bien éclairée
- Le document est complet (recto/verso si applicable)

<x-mail::button :url="$dashboardUrl">
Soumettre un nouveau dossier
</x-mail::button>

Merci de votre compréhension,
**L'équipe Safe Move**
</x-mail::message>