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
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->onDelete('cascade');

            $table->foreignId('luggage_id')
                ->constrained('luggages')
                ->onDelete('cascade');

            $table->foreignId('trip_id')
                ->constrained('trips')
                ->onDelete('cascade');

            $table->float('kg_reserved')->nullable();
            $table->decimal('price', 8, 2)->nullable();

            $table->timestamps();

            $table->unique(['booking_id', 'luggage_id']);

            // ✅ Contraintes SQL – seulement si MySQL 8.0+ ou PostgreSQL
            $table->check('kg_reserved IS NULL OR kg_reserved >= 0');
            $table->check('price IS NULL OR price >= 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
