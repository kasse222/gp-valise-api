<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Services\Alerting\SlackNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorWebhookHealth extends Command
{
    protected $signature = 'monitoring:webhooks
        {--minutes=10}
        {--failed-threshold=5}';

    protected $description = 'Analyse la santé des webhooks et déclenche des alertes critiques';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $threshold = (int) $this->option('failed-threshold');

        $since = now()->subMinutes($minutes);

        $metrics = $this->collectMetrics($since);

        $this->displayMetrics($minutes, $metrics);

        if ($this->shouldTriggerAlert($metrics, $threshold)) {
            $this->triggerAlert($minutes, $threshold, $metrics);

            $this->warn('⚠️ Alerte déclenchée.');
            return self::FAILURE;
        }

        $this->info('✅ Aucun problème critique détecté.');
        return self::SUCCESS;
    }

    private function collectMetrics($since): array
    {
        return [
            'processed_count' => WebhookLog::where('status', WebhookLog::STATUS_PROCESSED)->where('created_at', '>=', $since)->count(),
            'ignored_count' => WebhookLog::where('status', WebhookLog::STATUS_IGNORED)->where('created_at', '>=', $since)->count(),
            'failed_count' => WebhookLog::where('status', WebhookLog::STATUS_FAILED)->where('created_at', '>=', $since)->count(),
            'failed_jobs_count' => DB::table('failed_jobs')
                ->where('failed_at', '>=', $since)
                ->where('payload', 'like', '%ProcessPaymentWebhook%')
                ->count(),
        ];
    }

    private function displayMetrics(int $minutes, array $metrics): void
    {
        $this->info("Webhook monitoring sur les {$minutes} dernières minutes");

        foreach ($metrics as $key => $value) {
            $this->line("{$key}: {$value}");
        }
    }

    private function shouldTriggerAlert(array $metrics, int $threshold): bool
    {
        return $metrics['failed_count'] >= $threshold
            || $metrics['failed_jobs_count'] > 0;
    }

    private function triggerAlert(int $minutes, int $threshold, array $metrics): void
    {
        $message = 'Alerte monitoring webhook : seuil d’échecs dépassé';

        $context = [
            'window_minutes' => $minutes,
            'failed_threshold' => $threshold,
            ...$metrics,
        ];

        Log::channel('stack')->critical($message, $context);

        dispatch(new \App\Jobs\SendSlackAlert(
            $message,
            $context,
            'critical'
        ));

        $this->sendEmailFallback($message, $context);
    }

    private function sendEmailFallback(string $message, array $context): void
    {
        $email = config('payment.webhook.alert_email');

        if (! $email) {
            return;
        }

        Mail::raw(
            json_encode(compact('message') + $context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            fn($mail) => $mail->to($email)->subject('Alerte Monitoring Webhook 🚨')
        );
    }
}
