<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('currency', 3)->index();
            $table->string('country_code', 2)->index();
            $table->string('provider', 50)->nullable();
            $table->timestamps();

            $table->boolean('is_active')->default(true)->index();
            $table->bigInteger('balance')->default(0);
            $table->json('metadata')->nullable();

            $table->unique(['currency', 'country_code', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_accounts');
    }
};
