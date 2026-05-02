<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action', 100);

            //contrôle total (mieux que morphs() pour prod)
            $table->string('auditable_type', 150);
            $table->unsignedBigInteger('auditable_id');

            $table->json('metadata')->nullable();
            $table->string('reason', 255)->nullable();

            //  INTEGRITY
            $table->string('integrity_hash', 64)->nullable();
            $table->string('previous_hash', 64)->nullable();

            $table->timestamp('created_at')->useCurrent();

            //  INDEX STRATÉGIQUES
            $table->index('actor_id');
            $table->index('action');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');

            // 🔥 perf filtres API
            $table->index(['action', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
