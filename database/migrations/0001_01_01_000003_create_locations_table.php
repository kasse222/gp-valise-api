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

            // 🔗 Clé étrangère vers le trajet
            $table->foreignId('trip_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            // 🌍 Coordonnées GPS
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // 🏙️ Ville ou nom du lieu
            $table->string('city', 100)->nullable();

            // 🧭 Ordre dans le trajet (0 = départ, n = arrivée)
            $table->unsignedTinyInteger('order_index')->default(0);

            // 🧩 Position métier dans le trajet
            $table->string('position', 30)
                ->default(LocationPositionEnum::DEPART->value)
                ->comment('Enum : DEPART / ARRIVEE / TRANSIT');

            // 🧩 Type de lieu : HUB, DOUANE, etc.
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
