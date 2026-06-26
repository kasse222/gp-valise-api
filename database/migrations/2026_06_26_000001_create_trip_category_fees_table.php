<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_category_fees', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            // Catégorie de l'article (enum LuggageCategoryEnum)
            $table->string('category', 20);

            // Forfait fixe en minor units (même devise que le trip)
            // Ex: 5000 = 5000 F CFA, 500 = 5.00 EUR
            $table->integer('fee')->unsigned();

            $table->timestamps();

            // Un GP ne peut définir qu'un forfait par catégorie par trajet
            $table->unique(['trip_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_category_fees');
    }
};
