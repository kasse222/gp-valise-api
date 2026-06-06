<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('delivery_address')->nullable()->after('pickup_instructions');
            $table->string('delivery_city')->nullable()->after('delivery_address');
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_city');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->decimal('delivery_approx_latitude', 10, 7)->nullable()->after('delivery_longitude');
            $table->decimal('delivery_approx_longitude', 10, 7)->nullable()->after('delivery_approx_latitude');
            $table->string('delivery_instructions')->nullable()->after('delivery_approx_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_address',
                'delivery_city',
                'delivery_latitude',
                'delivery_longitude',
                'delivery_approx_latitude',
                'delivery_approx_longitude',
                'delivery_instructions',
            ]);
        });
    }
};
