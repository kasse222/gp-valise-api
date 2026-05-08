<?php

declare(strict_types=1);

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

            $table->integer('kg_reserved')->nullable();  // ← grammes : 25000 = 25kg

            $table->integer('price')->nullable();        // ← centimes : 1500 = 15.00€

            $table->timestamps();

            $table->unique(['booking_id', 'luggage_id'], 'booking_luggage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
