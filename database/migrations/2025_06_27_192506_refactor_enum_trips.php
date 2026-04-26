<?php

use App\Enums\TripTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('trips', 'type_trip')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->string('type_trip', 20)
                    ->default(TripTypeEnum::STANDARD->value)
                    ->comment('Type de trajet (enum TripTypeEnum, string)')
                    ->change();
            });
        }
    }


    public function down(): void
    {
        if (Schema::hasColumn('trips', 'type_trip')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->string('type_trip', 20)
                    ->nullable()
                    ->default(null)
                    ->comment(null)
                    ->change();
            });
        }
    }
};
