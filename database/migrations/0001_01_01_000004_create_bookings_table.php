<?php

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('status')
                ->default(BookingStatusEnum::EN_ATTENTE->value)
                ->comment('Statut de réservation stocké en int et casté via BookingStatusEnum');

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('payment_expires_at')->nullable();

            $table->text('comment')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'payment_expires_at'], 'bookings_status_payment_expires_at_index');
            $table->index(['user_id', 'trip_id', 'status'], 'bookings_user_trip_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
