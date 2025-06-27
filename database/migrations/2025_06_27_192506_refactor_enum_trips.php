<?php

use App\Enums\TripTypeEnum;
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
        Schema::table('trips', function (Blueprint $table) {
            $table->tinyInteger('status')
                ->default(0)
                ->comment('Statut du trajet (enum int)') // Casté dans Trip.php
                ->change();

            $table->string('type_trip')
                ->default(TripTypeEnum::STANDARD->value)
                ->comment('Type de trajet (enum TripTypeEnum)')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('status')
                ->default('0')
                ->comment('Statut ancien (string)')
                ->change();

            $table->string('type_trip')
                ->default('standard')
                ->comment('Type ancien (string)')
                ->change();
        });
    }
};
