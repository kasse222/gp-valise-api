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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Utilisateur ayant signalé
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Cible du signalement (polymorphique)
            $table->unsignedBigInteger('reportable_id');
            $table->string('reportable_type');

            $table->string('reason')->nullable();   // Ex : “contenu inapproprié”, “arnaque”
            $table->text('details')->nullable();    // Détails du signalement

            $table->timestamps();

            // Index utile pour perf sur morphs
            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
