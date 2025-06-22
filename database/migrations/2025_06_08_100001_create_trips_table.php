<?php

use App\Enums\TripTypeEnum;
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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('departure');
            $table->string('destination');

            $table->dateTime('date');
            $table->float('capacity');

            $table->enum('status', ['actif', 'complet', 'annule'])->default('actif'); // 🧠 à adapter selon enum
            $table->enum('type_trip', TripTypeEnum::values())->default(TripTypeEnum::STANDARD->value);

            $table->string('flight_number')->nullable();

            $table->softDeletes(); // ✅ propre, Laravel gère deleted_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
