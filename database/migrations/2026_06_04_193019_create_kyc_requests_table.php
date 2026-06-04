<?php

declare(strict_types=1);

use App\Enums\KycStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(KycStatusEnum::PENDING->value);
            $table->string('id_photo_path');
            $table->string('parcel_photo_path');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Index partiel PostgreSQL — un seul PENDING par user
        DB::statement("
        CREATE UNIQUE INDEX kyc_requests_one_pending_per_user
        ON kyc_requests (user_id)
        WHERE status = 'pending'
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_requests');
    }
};
