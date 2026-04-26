<?php

use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('city', 100)->nullable();

            $table->unsignedTinyInteger('order_index')->default(0);

            $table->string('position', 30)
                ->default(LocationPositionEnum::DEPART->value)
                ->comment('Enum : DEPART / ARRIVEE / TRANSIT');

            $table->string('type', 30)
                ->default(LocationTypeEnum::ETAPE->value)
                ->comment('Enum : STANDARD, HUB, DOUANE, AUTRE');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
