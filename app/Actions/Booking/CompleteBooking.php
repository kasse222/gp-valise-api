<?php

declare(strict_types=1);

namespace App\Actions\Booking;

/**
 * @deprecated Instant Booking
 *
 * CompleteBooking (CONFIRMEE → LIVREE via confirmation manuelle traveler) est remplacé par :
 *   - HandOverBooking  : CONFIRMEE → EN_TRANSIT (remise physique)
 *   - ConfirmDelivery  : EN_TRANSIT → LIVREE    (scan QR / code secret)
 *
 * Cette classe est conservée uniquement pour éviter de casser les références existantes
 * (tests, Filament admin). Elle sera supprimée en Phase 2.
 *
 * @see HandOverBooking
 * @see ConfirmDelivery
 */
class CompleteBooking extends ConfirmDelivery {}
