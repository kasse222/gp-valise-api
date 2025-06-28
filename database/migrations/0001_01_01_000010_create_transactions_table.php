<?php

use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // 👤 Utilisateur concerné
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 🔁 Lien optionnel avec une réservation
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->nullOnDelete();

            // 💰 Montant + devise
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)
                ->default(CurrencyEnum::EUR->value);

            // 💳 Méthode et statut (via enums)
            $table->string('method', 50)
                ->nullable()
                ->default(PaymentMethodEnum::CARTE_BANCAIRE->value);

            $table->unsignedTinyInteger('status')
                ->default(PaymentStatusEnum::EN_ATTENTE->value);

            // ✅ Date de traitement
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
