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

            // ✅ On utilise une string simple pour l’enum, pas l’objet lui-même
            $table->string('status', 30)
                ->default(BookingStatusEnum::EN_ATTENTE->value) // valeur string ici (ex: 'en_attente')
                ->comment('Statut de réservation (enum casté dans le modèle BookingStatusEnum)');

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('comment')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'trip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
