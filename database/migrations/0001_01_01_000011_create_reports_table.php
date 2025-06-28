<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // 🧑 Utilisateur ayant effectué le signalement
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 🎯 Cible du signalement (polymorphique)
            $table->morphs('reportable'); // Génère automatiquement reportable_id et reportable_type + index

            // 📝 Raison et détails
            $table->string('reason', 50)
                ->nullable()
                ->comment('Motif du signalement (ex: arnaque, propos inappropriés...)');

            $table->text('details')
                ->nullable()
                ->comment('Détails ou description complémentaire');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
