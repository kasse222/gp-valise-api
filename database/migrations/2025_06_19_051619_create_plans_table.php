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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: "Premium Mensuel"
            $table->unsignedInteger('price'); // en centimes (ex: 999 = 9.99€)
            $table->json('features')->nullable(); // JSON dynamique ex: {"priority_booking":true}
            $table->unsignedInteger('duration_days')->default(30); // durée en jours
            $table->boolean('is_active')->default(true); // visibilité publique
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
