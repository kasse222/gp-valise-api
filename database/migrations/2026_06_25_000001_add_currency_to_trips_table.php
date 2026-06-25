<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la devise de libellé sur le trajet.
 *
 * NULLABLE SANS DEFAULT : devise manquante = erreur visible à la création
 * et au paiement. Aucune devise ne doit jamais être devinée sur de l'argent réel.
 * Un trajet = une devise. Charge + escrow + payout restent dans cette devise.
 * Aucune conversion inter-devises.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->string('currency', 3)->nullable()->after('price_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};
