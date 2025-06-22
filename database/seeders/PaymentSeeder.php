<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::inRandomOrder()->take(20)->get();

        foreach ($bookings as $booking) {
            $status = fake()->randomElement(PaymentStatusEnum::cases());
            $method = fake()->randomElement(PaymentMethodEnum::cases());

            Payment::create([
                'user_id'     => $booking->user_id,
                'booking_id'  => $booking->id,
                'amount'      => fake()->randomFloat(2, 20, 500),
                'method'      => $method->value,
                'status'      => $status->value,
                'paid_at'     => $status->isSuccess() ? Carbon::now()->subDays(rand(0, 15)) : null,
            ]);
        }
    }
}
