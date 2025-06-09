<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('luggage_id')->constrained('luggages')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->enum('status', [
                'en_attente',   // réservation faite, en attente d’action du voyageur
                'accepte',      // acceptée par le voyageur
                'refuse',       // refusée par le voyageur
                'termine',      // le transport a été effectué
                'annule',       // annulée par une des parties
            ])->default('en_attente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
