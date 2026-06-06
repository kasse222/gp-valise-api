<?php

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
        Schema::table('trips', function (Blueprint $table) {
            $table->string('pickup_address')->nullable()->after('destination');
            $table->string('pickup_city')->nullable()->after('pickup_address');
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_city');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            $table->decimal('pickup_approx_latitude', 10, 7)->nullable()->after('pickup_longitude');
            $table->decimal('pickup_approx_longitude', 10, 7)->nullable()->after('pickup_approx_latitude');
            $table->string('pickup_instructions')->nullable()->after('pickup_approx_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            //
        });
    }
};
