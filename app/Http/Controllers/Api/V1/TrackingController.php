<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Models\Luggage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

final class TrackingController extends Controller
{

    public function __invoke(string $trackingId): JsonResponse
    {
        $luggage = Luggage::with([
            'user:id,first_name,last_name',
        ])
            ->where('tracking_id', $trackingId)
            ->first();

        if (! $luggage) {
            return response()->json([
                'message' => 'Colis introuvable.',
            ], 404);
        }

        // Récupérer le BookingItem → Booking → Trip
        $bookingItem = \App\Models\BookingItem::with([
            'booking.trip:id,departure,destination,date,flight_number,status',
            'booking.statusHistories',
        ])
            ->where('luggage_id', $luggage->id)
            ->latest()
            ->first();

        $booking = $bookingItem?->booking;
        $trip    = $booking?->trip;

        // Statut public lisible
        $statusMap = [
            'en_paiement' => ['label' => 'En attente de paiement',   'step' => 1, 'color' => 'amber'],
            'confirmee'   => ['label' => 'Réservation confirmée',     'step' => 2, 'color' => 'blue'],
            'en_transit'  => ['label' => 'Colis en transit ✈️',       'step' => 3, 'color' => 'indigo'],
            'livree'      => ['label' => 'Livré — en attente retrait', 'step' => 4, 'color' => 'emerald'],
            'termine'     => ['label' => 'Livraison terminée ✅',      'step' => 5, 'color' => 'emerald'],
            'annule'      => ['label' => 'Réservation annulée',       'step' => 0, 'color' => 'red'],
            'expiree'     => ['label' => 'Réservation expirée',       'step' => 0, 'color' => 'gray'],
            'en_litige'   => ['label' => 'Litige en cours',           'step' => 3, 'color' => 'red'],
            'remboursee'  => ['label' => 'Remboursé',                 'step' => 0, 'color' => 'gray'],
        ];

        $bookingStatus = $booking ? $booking->status->value : null;
        $statusInfo    = $statusMap[$bookingStatus] ?? ['label' => 'Statut inconnu', 'step' => 0, 'color' => 'gray'];

        // Historique public (sans données sensibles)
        $history = $booking?->statusHistories
            ->map(fn($h) => [
                'label'      => $statusMap[$h->new_status->value ?? $h->new_status]['label'] ?? $h->new_status,
                'changed_at' => $h->changed_at,
            ])
            ->values()
            ->toArray() ?? [];

        return response()->json([
            'tracking_id'   => $luggage->tracking_id,
            'description'   => $luggage->description,
            'weight_kg'     => $luggage->weight_kg / 10,
            'content_items' => $luggage->content_items ?? [],
            'status'        => $statusInfo,
            'trip' => $trip ? [
                'departure'     => $trip->departure,
                'destination'   => $trip->destination,
                'date'          => $trip->date,
                'flight_number' => $trip->flight_number,
            ] : null,
            'history'       => $history,
        ]);
    }
}
