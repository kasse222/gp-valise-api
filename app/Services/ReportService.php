<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Crée un nouveau signalement.
     */
    public function create(User $user, array $data): Report
    {
        // Exemple : vérifier qu’un report similaire n’existe pas déjà
        $existing = Report::where([
            'user_id'          => $user->id,
            'reportable_type'  => $data['reportable_type'],
            'reportable_id'    => $data['reportable_id'],
        ])->first();

        if ($existing) {
            throw new \Exception('Vous avez déjà signalé cet élément.');
        }

        $report = $user->reports()->create($data);

        // Log ou dispatch d’un event (optionnel)
        Log::info("Report créé par {$user->id}", [
            'type' => $data['reportable_type'],
            'id'   => $data['reportable_id'],
        ]);

        return $report;
    }
}
