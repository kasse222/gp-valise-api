<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('slug', 60)->unique();   // ex: escrow_eur
            $table->string('name', 120);             // ex: Fonds clients bloqués (EUR)
            $table->string('type', 20);              // ASSET | LIABILITY | REVENUE | EXPENSE
            $table->string('currency', 3);           // EUR | XOF
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['type', 'currency']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_accounts');
    }
};
