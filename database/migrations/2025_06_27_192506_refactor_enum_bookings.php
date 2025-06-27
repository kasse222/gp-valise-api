<?php

use App\Enums\BookingStatusEnum;
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
        Schema::table('bookings', function (Blueprint $table) {
            // Si la colonne existait en string : on la convertit
            $table->tinyInteger('status')
                ->default(BookingStatusEnum::EN_ATTENTE->value)
                ->comment('Statut de réservation (enum BookingStatusEnum)')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status')
                ->default('en_attente')
                ->comment('Statut textuel ancien (rollback)')
                ->change();
        });
    }
};
