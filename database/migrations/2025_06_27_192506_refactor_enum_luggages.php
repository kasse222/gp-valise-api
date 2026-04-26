<?php

use App\Enums\LuggageStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('luggages', function (Blueprint $table) {
            $table->string('status')
                ->default(LuggageStatusEnum::EN_ATTENTE->value)
                ->comment('Statut bagage (enum LuggageStatusEnum)')
                ->change();
        });
    }


    public function down(): void
    {
        Schema::table('luggages', function (Blueprint $table) {
            $table->string('status')
                ->default('en_attente')
                ->comment('Ancien format texte')
                ->change();
        });
    }
};
