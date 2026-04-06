<?php

use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->nullOnDelete();

            $table->string('type', 50)
                ->default(TransactionTypeEnum::CHARGE->value)
                ->index();

            $table->decimal('amount', 10, 2);

            $table->string('currency', 10)
                ->default(CurrencyEnum::EUR->value);

            $table->string('method', 50)
                ->nullable()
                ->default(PaymentMethodEnum::CARTE_BANCAIRE->value);

            $table->string('status', 30)
                ->default(TransactionStatusEnum::PENDING->value)
                ->index();

            $table->string('provider_transaction_id')
                ->nullable()
                ->index();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
