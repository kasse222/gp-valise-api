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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->nullOnDelete();

            $table->decimal('amount', 10, 2);

            $table->string('currency', 10)
                ->default(CurrencyEnum::EUR->value);

            $table->string('method', 50)
                ->default(PaymentMethodEnum::CARTE_BANCAIRE->value);

            $table->unsignedTinyInteger('status')
                ->default(PaymentStatusEnum::EN_ATTENTE->value);

            $table->uuid('payment_reference')->unique();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
