<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dispute_id')
                ->constrained('disputes')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('body');
            $table->json('attachments')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['dispute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
    }
};
