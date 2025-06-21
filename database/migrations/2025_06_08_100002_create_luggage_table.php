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

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('description')->nullable();
            $table->float('weight_kg')->default(0);
            $table->string('dimensions')->nullable();

            $table->string('pickup_city');
            $table->string('delivery_city');

            $table->date('pickup_date');
            $table->date('delivery_date');

            $table->enum('status', LuggageStatus::values())->default(LuggageStatus::EN_ATTENTE->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luggages');
    }
};
