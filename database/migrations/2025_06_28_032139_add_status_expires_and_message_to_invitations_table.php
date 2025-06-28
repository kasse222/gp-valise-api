<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Ajoute uniquement si la colonne n'existe pas déjà
            if (! Schema::hasColumn('invitations', 'expires_at')) {
                $table->timestamp('expires_at')
                    ->nullable()
                    ->after('used_at');
            }

            if (! Schema::hasColumn('invitations', 'message')) {
                $table->string('message', 255)
                    ->nullable()
                    ->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'message']);
        });
    }
};
