<?php

namespace App\Services\Alerting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotifier
{
    public function send(string $message, array $context = [], string $level = 'info'): void
    {
        if (! config('services.slack.enabled')) {
            Log::info('Slack alert skipped: alerts disabled.');
            return;
        }

        $webhookUrl = config('services.slack.webhook_url');

        if (! $webhookUrl) {
            Log::warning('Slack alert skipped: missing webhook URL.');
            return;
        }

        try {
            $payload = [
                'text' => $this->formatMessage($message, $context, $level),
            ];

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (! $response->successful()) {
                Log::error('Slack notification failed: non-success response.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message' => $message,
                    'context' => $context,
                    'level' => $level,
                ]);
            } else {
                Log::info('Slack notification sent successfully.', [
                    'message' => $message,
                    'level' => $level,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Slack notification failed: exception thrown.', [
                'error' => $e->getMessage(),
                'message' => $message,
                'context' => $context,
                'level' => $level,
            ]);
        }
    }

    private function formatMessage(string $message, array $context, string $level): string
    {
        $emoji = match ($level) {
            'critical' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️',
        };

        return "{$emoji} *GP-Valise Alert*\n\n"
            . '*App:* ' . config('app.name') . "\n"
            . '*Env:* ' . app()->environment() . "\n\n"
            . "*Message:* {$message}\n\n"
            . '*Context:*' . "\n```" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '```';
    }
}
