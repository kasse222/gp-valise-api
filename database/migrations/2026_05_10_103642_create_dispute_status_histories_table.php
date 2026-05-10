<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dispute_id')
                ->constrained('disputes')
                ->cascadeOnDelete();

            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['dispute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_status_histories');
    }
};
