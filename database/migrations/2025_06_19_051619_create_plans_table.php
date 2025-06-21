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

            $table->string('name');
            $table->enum('type', PlanTypeEnum::values())->default(PlanTypeEnum::FREE->value);

            $table->unsignedFloat('price')->default(0);
            $table->json('features')->nullable(); // 🧠 penser à valider format JSON

            $table->unsignedInteger('duration_days')->nullable(); // null = illimité ?
            $table->unsignedFloat('discount_percent')->nullable();
            $table->timestamp('discount_expires_at')->nullable();

            $table->boolean('is_active')->default(true);

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
