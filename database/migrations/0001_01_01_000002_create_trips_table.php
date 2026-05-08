<?php

declare(strict_types=1);

use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('departure', 255);
            $table->string('destination', 255);

            $table->dateTime('date');

            $table->integer('capacity');              // grammes : 25000 = 25kg, >= 1
            $table->integer('price_per_kg')->nullable(); // centimes/kg : 1500 = 15.00€, >= 0

            $table->string('status', 20)
                ->default(TripStatusEnum::ACTIVE->value);

            $table->string('type_trip', 20)
                ->default(TripTypeEnum::STANDARD->value);

            $table->string('flight_number', 30)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
