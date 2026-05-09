<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained('ledger_accounts')
                ->cascadeOnDelete();

            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();

            $table->string('direction', 6);          // DEBIT | CREDIT
            $table->integer('amount');               // minor units — centimes
            $table->string('currency', 3);           // EUR | XOF
            $table->string('description')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'direction']);
            $table->index('transaction_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
