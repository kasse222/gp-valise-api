<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('luggages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete(); // 🧍 Expéditeur supprimé = valises supprimées

            $table->string('description')->nullable();
            $table->decimal('weight_kg', 5, 2); // max 999.99 kg
            $table->string('dimensions')->nullable(); // ex: "55x40x20"

            $table->string('pickup_city');
            $table->string('delivery_city');
            $table->date('pickup_date');
            $table->date('delivery_date');

            $table->string('status')->default('en_attente'); // enum possible
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luggages');
    }
};
