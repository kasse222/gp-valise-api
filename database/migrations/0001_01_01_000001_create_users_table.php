<?php

use App\Enums\UserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('country')->nullable();
            $table->string('password');

            $table->unsignedTinyInteger('role')
                ->default(UserRoleEnum::SENDER->value)
                ->comment('Rôle utilisateur stocké en int et casté via UserRoleEnum');

            $table->boolean('verified_user')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('kyc_passed_at')->nullable();

            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete();

            $table->timestamp('plan_expires_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
            $table->index(['verified_user', 'kyc_passed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
