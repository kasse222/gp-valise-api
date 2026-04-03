<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Enums\LuggageStatusEnum;
use Illuminate\Support\Facades\DB;

class DeleteBooking
{
    public function execute(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->with('bookingItems.luggage')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }

                $item->delete();
            }

            $booking->delete();
        });
    }
}
