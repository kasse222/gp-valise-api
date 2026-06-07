<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_requests', function (Blueprint $table): void {
            $table->renameColumn('id_photo_path', 'id_front_path');
            $table->dropColumn('parcel_photo_path');
            $table->string('id_back_path')->nullable()->after('id_front_path');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_requests', function (Blueprint $table): void {
            $table->renameColumn('id_front_path', 'id_photo_path');
            $table->dropColumn('id_back_path');
            $table->string('parcel_photo_path')->nullable();
        });
    }
};
