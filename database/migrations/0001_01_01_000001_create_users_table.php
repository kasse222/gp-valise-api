<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identité & authentification
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('country')->nullable();
            $table->string('password');

            // Enum rôle utilisateur (enum natif Laravel)
            $table->unsignedTinyInteger('role')->default(0)->comment('Enum UserRoleEnum');

            // Vérifications
            $table->boolean('verified_user')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('kyc_passed_at')->nullable();

            // Gestion de plan
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete();

            $table->timestamp('plan_expires_at')->nullable();

            // Auth & timestamps
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
