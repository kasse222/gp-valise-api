<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->unique() // 1 dispute active par booking
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->string('status', 30)->default('open');

            $table->foreignId('opened_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reason');
            $table->text('resolution')->nullable();
            $table->string('decision', 20)->nullable(); // refund | payout

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('opened_by');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
