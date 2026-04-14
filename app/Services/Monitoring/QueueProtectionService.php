<?php

namespace App\Services\Monitoring;

class QueueProtectionService
{
    public function __construct(
        private readonly QueueHealthService $queueHealthService,
    ) {}

    /**
     * Décide si le dispatch de nouveaux jobs sur la queue high
     * doit être bloqué pour éviter d'aggraver une tempête de retries.
     *
     * Retourne une structure stable, exploitable par les commandes,
     * les logs et de futurs points d’intégration.
     */
    public function guardHighQueueDispatch(
        int $windowMinutes = 15,
        int $perJobThreshold = 5
    ): array {
        $retryStorm = $this->queueHealthService->detectRetryStorm(
            windowMinutes: $windowMinutes,
            perJobThreshold: $perJobThreshold,
        );

        if ($retryStorm['storm_detected'] ?? false) {
            return [
                'allowed' => false,
                'blocked' => true,
                'reason' => 'Retry storm détecté sur la fenêtre récente.',
                'recommended_action' => 'Suspendre temporairement les nouveaux dispatchs sur la queue high et corriger le job dominant.',
                'retry_storm' => $retryStorm,
            ];
        }

        return [
            'allowed' => true,
            'blocked' => false,
            'reason' => 'Aucun retry storm détecté.',
            'recommended_action' => 'Dispatch autorisé.',
            'retry_storm' => $retryStorm,
        ];
    }
}
