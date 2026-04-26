<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('luggage_id')
                ->constrained('luggages')
                ->cascadeOnDelete();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->float('kg_reserved')
                ->nullable()
                ->comment('Poids réservé en kg pour cette valise dans le trajet');

            $table->decimal('price', 8, 2)
                ->nullable()
                ->comment('Montant total en € pour cet envoi');

            $table->timestamps();

            $table->unique(['booking_id', 'luggage_id'], 'booking_luggage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
