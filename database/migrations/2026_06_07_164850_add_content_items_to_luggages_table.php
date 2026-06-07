<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('luggages', function (Blueprint $table): void {
            $table->jsonb('content_items')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('luggages', function (Blueprint $table): void {
            $table->dropColumn('content_items');
        });
    }
};
