<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_locations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('booking_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Coordonnées exactes — révélées après confirmation
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Coordonnées approximatives — toujours visibles
            $table->decimal('approximate_latitude', 10, 7);
            $table->decimal('approximate_longitude', 10, 7);

            $table->string('address');
            $table->string('city');
            $table->text('instructions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_locations');
    }
};
