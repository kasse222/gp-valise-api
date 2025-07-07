<?php

use App\Enums\InvitationStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();

            // 👤 Utilisateur émetteur
            $table->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 📧 Destinataire
            $table->string('recipient_email')->index();

            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();


            // 🔐 Token d’invitation
            $table->string('token')->unique();


            // ✅ Champ enum status
            $table->string('status', 30)
                ->default(InvitationStatusEnum::PENDING->value)
                ->comment('Statut de l’invitation (enum InvitationStatusEnum casté dans le modèle)');

            // 💬 Message facultatif de l’expéditeur
            $table->string('message', 500)->nullable();

            // 🕓 Date d’utilisation
            $table->timestamp('used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
