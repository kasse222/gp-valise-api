<?php

use App\Enums\LuggageStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('luggages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');

            $table->string('description')->nullable();
            $table->float('weight_kg')->default(0);

            $table->float('length_cm')->nullable();
            $table->float('width_cm')->nullable();
            $table->float('height_cm')->nullable();

            $table->string('pickup_city');
            $table->string('delivery_city');

            $table->dateTime('pickup_date');
            $table->dateTime('delivery_date');

            $table->boolean('is_fragile')->default(false);
            $table->boolean('insurance_requested')->default(false);

            $table->string('status', 30)
                ->default(LuggageStatusEnum::EN_ATTENTE->value)
                ->comment('Statut métier (enum casté dans le modèle)');

            $table->uuid('tracking_id')->nullable()->unique();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luggages');
    }
};
