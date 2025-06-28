<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();

            // 🔗 Référence vers la réservation concernée
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->onDelete('cascade');

            // 🔁 Statuts avant / après
            $table->string('old_status', 50)->nullable(); // Peut être null si premier statut
            $table->string('new_status', 50); // Jamais null : le statut final après changement

            // 👤 Utilisateur ayant provoqué le changement (admin, support, ou utilisateur)
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete(); // Ne supprime pas l’historique si user supprimé

            // 📝 Raison du changement (facultatif)
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_histories');
    }
};
