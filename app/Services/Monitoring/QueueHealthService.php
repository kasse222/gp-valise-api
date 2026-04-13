<?php

namespace App\Services\Monitoring;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class QueueHealthService
{
    /**
     * Queues supervisées par défaut.
     *
     * 👉 On centralise ici pour éviter les magic strings répétées
     * dans plusieurs méthodes.
     */
    private const QUEUES = ['high', 'default', 'low'];

    /**
     * Collecte un snapshot global de la santé des queues.
     *
     * Ce snapshot est volontairement simple et structuré :
     * - taille de chaque queue
     * - âge du plus vieux job en attente
     * - nombre de failed jobs récents
     *
     * 👉 Cette méthode sert de "contrat principal" pour la supervision.
     */
    public function collect(int $failedJobsWindowMinutes = 15): array
    {
        return [
            'queues' => $this->collectQueueSizes(),
            'oldest_job_age_seconds' => $this->collectOldestAges(),
            'failed_jobs_recent' => $this->countRecentFailedJobs($failedJobsWindowMinutes),
        ];
    }

    /**
     * Détecte un retry storm / failure storm à partir des failed_jobs récents.
     *
     * Idée :
     * - on regarde les jobs échoués sur une fenêtre donnée
     * - on compte combien de fois chaque type de job apparaît
     * - si un job dominant dépasse un seuil, on considère qu'il y a tempête
     *
     * 👉 Ce signal est utile pour distinguer :
     * - un problème de capacité
     * - d’un problème applicatif ou provider ciblé
     */
    public function detectRetryStorm(
        int $windowMinutes = 15,
        int $perJobThreshold = 5
    ): array {
        $since = now()->subMinutes($windowMinutes);

        $rows = DB::table('failed_jobs')
            ->select('payload')
            ->where('failed_at', '>=', $since)
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $payload = json_decode($row->payload, true);

            // displayName est généralement le signal le plus utile
            // pour identifier le type réel de job.
            $jobName = $payload['displayName'] ?? 'unknown';

            $counts[$jobName] = ($counts[$jobName] ?? 0) + 1;
        }

        arsort($counts);

        $dominantJob = array_key_first($counts);
        $dominantCount = $dominantJob ? $counts[$dominantJob] : 0;

        return [
            'window_minutes' => $windowMinutes,
            'per_job_threshold' => $perJobThreshold,
            'counts' => $counts,
            'dominant_job' => $dominantJob,
            'dominant_count' => $dominantCount,
            'storm_detected' => $dominantCount >= $perJobThreshold,
        ];
    }

    /**
     * Produit un diagnostic métier sur la pression subie par la queue high.
     *
     * On ne veut pas seulement savoir "ça va mal",
     * on veut comprendre "de quel type de problème s’agit-il ?"
     *
     * Exemples de sortie :
     * - healthy
     * - traffic_spike
     * - slow_processing
     * - retry_storm_pressure
     * - capacity_pressure
     */
    public function assessHighQueuePressure(
        array $metrics,
        array $retryStorm,
        int $highThreshold,
        int $highAgeThreshold
    ): array {
        $highSize = $metrics['queues']['high'] ?? 0;
        $highAge = $metrics['oldest_job_age_seconds']['high'] ?? null;

        $signals = [
            'backlog_exceeded' => $highSize >= $highThreshold,
            'age_exceeded' => $highAge !== null && $highAge >= $highAgeThreshold,
            'retry_storm_detected' => $retryStorm['storm_detected'] ?? false,
        ];

        return $this->resolvePressureStatus($signals);
    }

    /**
     * Retourne l’âge du plus vieux job encore en attente dans une queue.
     *
     * Pourquoi c’est important :
     * - la taille de queue seule ne suffit pas
     * - une queue modérée peut déjà être en souffrance si le plus vieux job
     *   attend depuis trop longtemps
     *
     * 👉 On retourne un âge positif en secondes, ou null si aucun job exploitable.
     */
    public function oldestJobAge(string $queue): ?int
    {
        // On récupère le plus vieux job encore présent en queue.
        $rawJob = Redis::lindex("queues:{$queue}", -1);

        if (! $rawJob) {
            return null;
        }

        $job = json_decode($rawJob, true);

        // Certains payloads peuvent ne pas contenir pushedAt.
        if (! isset($job['pushedAt'])) {
            return null;
        }

        return (int) Carbon::createFromTimestamp($job['pushedAt'])
            ->diffInSeconds(now());
    }

    /**
     * Retourne la taille brute d’une queue Redis Laravel.
     */
    private function queueSize(string $queue): int
    {
        return (int) Redis::llen("queues:{$queue}");
    }

    /**
     * Collecte la taille de toutes les queues supervisées.
     */
    private function collectQueueSizes(): array
    {
        $result = [];

        foreach (self::QUEUES as $queue) {
            $result[$queue] = $this->queueSize($queue);
        }

        return $result;
    }

    /**
     * Collecte l’âge du plus vieux job pour toutes les queues supervisées.
     */
    private function collectOldestAges(): array
    {
        $result = [];

        foreach (self::QUEUES as $queue) {
            $result[$queue] = $this->oldestJobAge($queue);
        }

        return $result;
    }

    /**
     * Compte les failed_jobs récents sur une fenêtre donnée.
     *
     * 👉 Ce signal reste volontairement global pour le moment.
     * Il pourra être affiné plus tard par type de job ou par queue.
     */
    private function countRecentFailedJobs(int $minutes): int
    {
        return DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Transforme les signaux bruts en diagnostic opérationnel.
     *
     * Lecture métier :
     * - backlog + âge + pas de storm  => pression de capacité
     * - backlog + storm              => problème retry/app/provider
     * - peu de backlog + âge élevé   => traitement lent / bloquant
     * - backlog + âge faible         => pic de trafic absorbable
     */
    private function resolvePressureStatus(array $signals): array
    {
        $backlog = $signals['backlog_exceeded'];
        $age = $signals['age_exceeded'];
        $storm = $signals['retry_storm_detected'];

        if ($backlog && $age && ! $storm) {
            return $this->pressureResponse(
                status: 'capacity_pressure',
                reason: 'La queue high dépasse le seuil de backlog et d’ancienneté sans retry storm détecté. La capacité workers est probablement insuffisante.',
                action: 'Augmenter maxProcesses pour supervisor-high et vérifier CPU/RAM du service horizon.',
                backlogExceeded: $backlog,
                ageExceeded: $age,
                retryStormDetected: $storm,
            );
        }
        if (! $backlog && ! $age && $storm) {
            return $this->pressureResponse(
                status: 'retry_storm_pressure',
                reason: 'Un retry storm est détecté même sans backlog actif. Le système a connu une dégradation applicative récente qui mérite investigation.',
                action: 'Analyser le job dominant, la cause des échecs et ajuster tries/backoff ou la logique applicative.',
                backlogExceeded: $backlog,
                ageExceeded: $age,
                retryStormDetected: $storm,
            );
        }

        if ($backlog && $storm) {
            return $this->pressureResponse(
                status: 'retry_storm_pressure',
                reason: 'La queue high est sous pression avec un retry storm détecté. Le problème principal semble applicatif ou externe plutôt qu’un simple manque de workers.',
                action: 'Identifier le job dominant, revoir tries/backoff et corriger la cause applicative ou provider.',
                backlogExceeded: $backlog,
                ageExceeded: $age,
                retryStormDetected: $storm,
            );
        }

        if (! $backlog && $age) {
            return $this->pressureResponse(
                status: 'slow_processing',
                reason: 'Le backlog high reste modéré mais l’ancienneté du plus vieux job dépasse le seuil. Un job lent ou bloquant monopolise probablement les workers.',
                action: 'Profiler le job lent, revoir timeout et découper le traitement si nécessaire.',
                backlogExceeded: $backlog,
                ageExceeded: $age,
                retryStormDetected: $storm,
            );
        }

        if ($backlog && ! $age) {
            return $this->pressureResponse(
                status: 'traffic_spike',
                reason: 'La queue high dépasse le seuil de backlog mais sans retard ancien significatif. Il peut s’agir d’un pic de trafic encore absorbable.',
                action: 'Observer la résorption naturelle du backlog et scaler légèrement seulement si l’âge continue de monter.',
                backlogExceeded: $backlog,
                ageExceeded: $age,
                retryStormDetected: $storm,
            );
        }

        return $this->pressureResponse(
            status: 'healthy',
            reason: 'Aucune pression critique détectée sur la queue high.',
            action: 'Aucune action requise.',
            backlogExceeded: $backlog,
            ageExceeded: $age,
            retryStormDetected: $storm,
        );
    }

    /**
     * Fabrique une réponse de diagnostic homogène.
     *
     * 👉 L’intérêt :
     * - format stable pour les logs / Slack / tests
     * - plus facile à étendre plus tard
     */
    private function pressureResponse(
        string $status,
        string $reason,
        string $action,
        bool $backlogExceeded,
        bool $ageExceeded,
        bool $retryStormDetected
    ): array {
        return [
            'status' => $status,
            'reason' => $reason,
            'recommended_action' => $action,
            'backlog_exceeded' => $backlogExceeded,
            'age_exceeded' => $ageExceeded,
            'retry_storm_detected' => $retryStormDetected,
        ];
    }
}
