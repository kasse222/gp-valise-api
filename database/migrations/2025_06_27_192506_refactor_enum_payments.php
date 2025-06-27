<?php

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
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
        Schema::table('payments', function (Blueprint $table) {
            $table->tinyInteger('status')
                ->default(PaymentStatusEnum::EN_ATTENTE->value)
                ->comment('Statut paiement (enum PaymentStatusEnum)')
                ->change();

            $table->string('method')
                ->default(PaymentMethodEnum::CARTE_BANCAIRE->value)
                ->comment('Moyen de paiement (enum PaymentMethodEnum)')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')
                ->default('0')
                ->comment('Ancien statut paiement')
                ->change();

            $table->string('method')
                ->default('carte')
                ->comment('Ancien moyen paiement')
                ->change();
        });
    }
};
