<?php

use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
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
                ->onDelete('cascade');

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->onDelete('set null');

            $table->decimal('amount', 10, 2);

            $table->enum('currency', CurrencyEnum::values())->default(CurrencyEnum::EUR->value);
            $table->string('method', 50)
                ->default(PaymentMethodEnum::CARTE_BANCAIRE->value); // 🟢 Enum Laravel >= 9.19

            $table->unsignedTinyInteger('status')
                ->default(\App\Enums\PaymentStatusEnum::EN_ATTENTE->value); // 🟢 Enum numérique

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
