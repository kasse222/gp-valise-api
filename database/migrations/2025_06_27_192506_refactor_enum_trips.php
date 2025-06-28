<?php

use App\Enums\TripTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Up : on ne touche qu’à `type_trip`.
     * On laisse `status` tel quel (string) pour éviter l’erreur
     * “Incorrect integer value : 'active'”.
     */
    public function up(): void
    {
        // ⚠️ Vérifie d’abord que la colonne existe
        if (Schema::hasColumn('trips', 'type_trip')) {
            Schema::table('trips', function (Blueprint $table) {
                // On force VARCHAR + valeur par défaut cohérente
                $table->string('type_trip', 20)
                    ->default(TripTypeEnum::STANDARD->value)
                    ->comment('Type de trajet (enum TripTypeEnum, string)')
                    ->change();
            });
        }
    }

    /**
     * Down : simple rollback → on repasse `type_trip` en string sans défaut.
     * On ne touche toujours pas à `status`.
     */
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
