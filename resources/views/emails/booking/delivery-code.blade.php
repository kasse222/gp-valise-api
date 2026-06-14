<x-mail::message>
# 📦 Votre colis est en route

Bonjour {{ $booking->recipient_name }},

Un colis vous est destiné et est actuellement en transit vers vous.

**Trajet :** {{ $trip->departure }} → {{ $trip->destination }}
**Date d'arrivée prévue :** {{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }}

---

## 🔐 Code de réception

Présentez ce code au voyageur au moment de la remise :

<x-mail::panel>
# {{ $deliveryCode }}
</x-mail::panel>

Ce code vous permet de récupérer votre colis en toute sécurité. Ne le partagez avec personne d'autre que le voyageur.

---

## 📱 QR Code

Vous pouvez également présenter votre QR code en vous rendant sur le lien ci-dessous :

<x-mail::button :url="$trackingUrl">
Voir mon QR Code
</x-mail::button>

---

En cas de problème ou si vous n'attendez pas de colis, contactez-nous immédiatement.

Merci,
**L'équipe Safe Move**
</x-mail::message>