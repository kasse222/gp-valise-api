<?php

use App\Status\BookingStatus;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->decimal('total_weight_kg', 6, 1)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->enum('status', array_column(BookingStatus::cases(), 'value'))
                ->default(BookingStatus::EN_ATTENTE->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
