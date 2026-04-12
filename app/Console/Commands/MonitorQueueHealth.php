<?php

namespace App\Console\Commands;

use App\Jobs\SendSlackAlert;
use App\Services\Monitoring\QueueHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorQueueHealth extends Command
{
    /**
     * Signature CLI.
     *
     * 👉 Tous les seuils sont configurables à l’exécution
     * pour éviter de modifier le code à chaque ajustement.
     */
    protected $signature = 'monitoring:queues
        {--high-threshold=25 : Seuil backlog queue high}
        {--failed-jobs-threshold=5 : Seuil failed_jobs récents}
        {--high-age-threshold=30 : Seuil d ancienneté max du plus vieux job high (secondes)}
        {--window=15 : Fenêtre d analyse en minutes}';

    protected $description = 'Surveille la santé des queues Redis et déclenche une alerte si nécessaire';

    public function __construct(
        private readonly QueueHealthService $queueHealthService,
    ) {
        parent::__construct();
    }

    /**
     * Point d’entrée principal de la commande.
     *
     * Flux global :
     * 1. lire les seuils runtime
     * 2. collecter les métriques
     * 3. détecter retry storm
     * 4. produire un diagnostic de pression
     * 5. afficher un snapshot lisible
     * 6. décider si alerte
     * 7. log + dispatch Slack async
     */
    public function handle(): int
    {
        $options = $this->readOptions();

        $metrics = $this->queueHealthService->collect($options['window']);
        $retryStorm = $this->queueHealthService->detectRetryStorm($options['window'], 5);

        $pressureAssessment = $this->queueHealthService->assessHighQueuePressure(
            $metrics,
            $retryStorm,
            $options['high_threshold'],
            $options['high_age_threshold']
        );

        $this->displaySnapshot($metrics, $retryStorm, $pressureAssessment);

        if (! $this->shouldTriggerAlert($metrics, $retryStorm, $options)) {
            $this->info('✅ Aucun problème critique détecté.');
            return self::SUCCESS;
        }

        $message = 'Alerte supervision queues : seuil critique dépassé';
        $context = $this->buildAlertContext($metrics, $retryStorm, $pressureAssessment, $options);

        $this->triggerAlert($message, $context);

        $this->warn('⚠️ Alerte déclenchée.');

        return self::FAILURE;
    }

    /**
     * Lit et normalise les options CLI.
     *
     * 👉 On centralise ici pour éviter de disperser les casts
     * et rendre le handle() plus lisible.
     */
    private function readOptions(): array
    {
        return [
            'high_threshold' => (int) $this->option('high-threshold'),
            'failed_jobs_threshold' => (int) $this->option('failed-jobs-threshold'),
            'high_age_threshold' => (int) $this->option('high-age-threshold'),
            'window' => (int) $this->option('window'),
        ];
    }

    /**
     * Affiche un état instantané lisible dans la console.
     *
     * 👉 Très utile en debug local, en cron manuel,
     * ou en incident review.
     */
    private function displaySnapshot(
        array $metrics,
        array $retryStorm,
        array $pressureAssessment
    ): void {
        $highSize = $metrics['queues']['high'];
        $defaultSize = $metrics['queues']['default'];
        $lowSize = $metrics['queues']['low'];
        $failedJobsRecent = $metrics['failed_jobs_recent'];
        $highOldestAge = $metrics['oldest_job_age_seconds']['high'];

        $this->info('Queue monitoring snapshot');
        $this->line("high: {$highSize}");
        $this->line("default: {$defaultSize}");
        $this->line("low: {$lowSize}");
        $this->line("failed_jobs_recent: {$failedJobsRecent}");
        $this->line('oldest_high_job_age_seconds: ' . ($highOldestAge ?? 'none'));

        $this->line('retry_storm_detected: ' . ($retryStorm['storm_detected'] ? 'yes' : 'no'));
        $this->line('retry_storm_dominant_job: ' . ($retryStorm['dominant_job'] ?? 'none'));
        $this->line('retry_storm_dominant_count: ' . $retryStorm['dominant_count']);

        $this->line('high_pressure_status: ' . $pressureAssessment['status']);
        $this->line('high_pressure_reason: ' . $pressureAssessment['reason']);
        $this->line('high_pressure_recommended_action: ' . $pressureAssessment['recommended_action']);
    }

    /**
     * Décision d’alerte multi-signaux.
     *
     * On ne se base pas sur un seul symptôme :
     * - backlog high
     * - failed jobs récents
     * - retry storm
     * - ageing du plus vieux job
     *
     * 👉 Cela évite les diagnostics simplistes du type
     * "high est haut donc il faut scaler".
     */
    private function shouldTriggerAlert(
        array $metrics,
        array $retryStorm,
        array $options
    ): bool {
        $highSize = $metrics['queues']['high'];
        $failedJobsRecent = $metrics['failed_jobs_recent'];
        $highOldestAge = $metrics['oldest_job_age_seconds']['high'];

        return $highSize >= $options['high_threshold']
            || $failedJobsRecent >= $options['failed_jobs_threshold']
            || $retryStorm['storm_detected']
            || ($highOldestAge !== null && $highOldestAge >= $options['high_age_threshold']);
    }

    /**
     * Construit le contexte envoyé dans les logs et dans Slack.
     *
     * 👉 Ce contexte est essentiel :
     * il permet de comprendre l’incident sans SSH immédiat.
     */
    private function buildAlertContext(
        array $metrics,
        array $retryStorm,
        array $pressureAssessment,
        array $options
    ): array {
        return [
            'high_threshold' => $options['high_threshold'],
            'failed_jobs_threshold' => $options['failed_jobs_threshold'],
            'high_age_threshold' => $options['high_age_threshold'],
            'window_minutes' => $options['window'],

            'queues' => $metrics['queues'],
            'failed_jobs_recent' => $metrics['failed_jobs_recent'],
            'oldest_job_age_seconds' => $metrics['oldest_job_age_seconds'],

            'retry_storm' => $retryStorm,
            'pressure_assessment' => $pressureAssessment,
        ];
    }

    /**
     * Déclenche l’alerte :
     * - log critique
     * - Slack async via queue low
     *
     * 👉 On évite toute dépendance réseau synchrone
     * dans le chemin principal de supervision.
     */
    private function triggerAlert(string $message, array $context): void
    {
        Log::channel('stack')->critical($message, $context);

        dispatch(new SendSlackAlert(
            $message,
            $context,
            'critical'
        ));
    }
}
