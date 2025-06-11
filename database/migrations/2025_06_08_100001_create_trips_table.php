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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete(); // 🔁 Si le voyageur supprime son compte

            $table->string('departure');     // Ville de départ
            $table->string('destination');   // Ville d’arrivée
            $table->date('date');            // Date du voyage
            $table->integer('capacity');     // Capacité totale (en kg ou % volume)
            $table->string('status')->default('actif'); // actif / complet / annulé
            $table->string('flight_number')->nullable(); // ✈ Pour les trajets aériens

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
