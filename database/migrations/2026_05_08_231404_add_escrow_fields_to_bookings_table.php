<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('payment_expires_at');
            $table->timestamp('escrow_releasable_at')->nullable()->after('delivered_at');
            $table->timestamp('disputed_at')->nullable()->after('escrow_releasable_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'escrow_releasable_at', 'disputed_at']);
        });
    }
};
