<?php

namespace App\Providers;

use App\Contracts\Payments\KkiapayAdminClientContract;
use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Contracts\Payments\WebhookProcessorContract;
use App\Services\Payments\KkiapayAdminClient;
use App\Services\Payments\PaymentProviderResolver;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PaymentProviderResolverContract::class,
            PaymentProviderResolver::class,
        );

        $this->app->bind(
            WebhookProcessorContract::class,
            WebhookProcessor::class,
        );

        $this->app->bind(
            KkiapayAdminClientContract::class,
            KkiapayAdminClient::class
        );
    }

    public function boot(): void
    {
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60);
        });
    }
}
