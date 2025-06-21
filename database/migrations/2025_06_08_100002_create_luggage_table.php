<?php


use App\Enums\LuggageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('luggages', function (Blueprint $table) {
            $table->id();

            // 🔐 Relation avec l’utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 📦 Description et poids
            $table->string('description')->nullable();
            $table->float('weight_kg')->default(0);

            // 📏 Dimensions normalisées
            $table->float('length_cm')->nullable();
            $table->float('width_cm')->nullable();
            $table->float('height_cm')->nullable();

            // 🛣️ Villes et dates
            $table->string('pickup_city');
            $table->string('delivery_city');
            $table->dateTime('pickup_date');    // ↔️ datetime plus précis
            $table->dateTime('delivery_date');

            // 🚦 Statut de la valise
            $table->enum('status', LuggageStatus::values())
                ->default(LuggageStatus::EN_ATTENTE->value);

            // 🛰️ Optionnel : identifiant de suivi unique
            $table->uuid('tracking_id')->unique()->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luggages');
    }
};
