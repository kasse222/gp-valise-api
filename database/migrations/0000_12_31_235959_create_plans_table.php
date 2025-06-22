<?php

use App\Enums\PlanTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->enum('type', PlanTypeEnum::values())->default(PlanTypeEnum::FREE->value);

            $table->decimal('price', 8, 2)->unsigned()->default(0);               // ✅ corrigé
            $table->json('features')->nullable();                                // ✅ ajouté
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('discount_percent', 5, 2)->unsigned()->nullable();   // ✅ corrigé
            $table->timestamp('discount_expires_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
