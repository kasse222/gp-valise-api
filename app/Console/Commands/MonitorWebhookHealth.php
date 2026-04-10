<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorWebhookHealth extends Command
{
    protected $signature = 'monitoring:webhooks
                            {--minutes=10 : Fenêtre de temps à analyser}
                            {--failed-threshold=5 : Seuil d\'échecs avant alerte}';

    protected $description = 'Analyse la santé des webhooks et déclenche une alerte si un seuil d’échecs est dépassé';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $failedThreshold = (int) $this->option('failed-threshold');

        $since = now()->subMinutes($minutes);

        $processedCount = WebhookLog::query()
            ->where('status', WebhookLog::STATUS_PROCESSED)
            ->where('created_at', '>=', $since)
            ->count();

        $ignoredCount = WebhookLog::query()
            ->where('status', WebhookLog::STATUS_IGNORED)
            ->where('created_at', '>=', $since)
            ->count();

        $failedCount = WebhookLog::query()
            ->where('status', WebhookLog::STATUS_FAILED)
            ->where('created_at', '>=', $since)
            ->count();

        $failedJobsCount = DB::table('failed_jobs')
            ->where('failed_at', '>=', $since)
            ->where('payload', 'like', '%ProcessPaymentWebhook%')
            ->count();

        $this->info("Webhook monitoring sur les {$minutes} dernières minutes");
        $this->line("processed: {$processedCount}");
        $this->line("ignored: {$ignoredCount}");
        $this->line("failed: {$failedCount}");
        $this->line("failed_jobs (webhook): {$failedJobsCount}");

        if ($failedCount >= $failedThreshold || $failedJobsCount > 0) {
            $message = 'Alerte monitoring webhook : seuil d’échecs dépassé';

            $context = [
                'window_minutes' => $minutes,
                'failed_threshold' => $failedThreshold,
                'processed_count' => $processedCount,
                'ignored_count' => $ignoredCount,
                'failed_count' => $failedCount,
                'failed_jobs_count' => $failedJobsCount,
            ];

            Log::channel('stack')->critical($message, $context);

            $alertEmail = config('payment.webhook.alert_email');


            if ($alertEmail) {
                Mail::raw(
                    json_encode([
                        'message' => $message,
                        ...$context,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    function ($mail) use ($alertEmail) {
                        $mail->to($alertEmail)
                            ->subject('Alerte Monitoring Webhook 🚨');
                    }
                );
            }

            $this->warn('⚠️ Alerte déclenchée.');

            return self::FAILURE;
        }

        $this->info('✅ Aucun problème critique détecté.');

        return self::SUCCESS;
    }
}
