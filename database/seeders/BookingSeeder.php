<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Enums\BookingStatusEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $senders = User::where('role', 3)->get(); // SENDER
        $trips   = Trip::all();



        foreach ($senders as $sender) {
            for ($i = 0; $i < 2; $i++) {
                $trip = $trips->random();

                $status = fake()->randomElement(BookingStatusEnum::cases());

                Booking::create([
                    'user_id'       => $sender->id,
                    'trip_id'       => $trip->id,
                    'status'       => $status,
                    'comment'       => fake()->optional()->sentence,
                    'confirmed_at'  => $status === BookingStatusEnum::CONFIRMEE ? now() : null,
                    'completed_at'  => $status === BookingStatusEnum::TERMINE ? now()->addDays(2) : null,
                    'cancelled_at'  => $status === BookingStatusEnum::ANNULE ? now()->subDay() : null,
                ]);
            }
        }
    }
}
