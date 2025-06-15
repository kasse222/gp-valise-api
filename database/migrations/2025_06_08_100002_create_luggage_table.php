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
        Schema::create('luggages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->float('weight_kg', 5, 1);
            $table->string('dimensions');
            $table->string('pickup_city');
            $table->string('delivery_city');
            $table->date('pickup_date');
            $table->date('delivery_date');
            $table->timestamps();
            $table->enum('status', [
                'en_attente',   // Valise créée, pas encore assignée à un trajet
                'reservee',     // Un booking a été créé
                'livree',       // La valise a été transportée et livrée
                'annulee',      // Annulée par l’expéditeur ou le système
            ])->default('en_attente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luggage');
    }
};
