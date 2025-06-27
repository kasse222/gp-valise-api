<?php

use App\Enums\UserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role')
                ->default(UserRoleEnum::SENDER->value)
                ->comment('Rôle utilisateur (enum UserRoleEnum)')
                ->change();

            $table->unsignedBigInteger('plan_id')
                ->nullable()
                ->comment('Abonnement (FK plans)')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('role')->default(3)->comment('Rôle (int brut)')->change();
            $table->unsignedBigInteger('plan_id')->nullable()->change();
        });
    }
};
