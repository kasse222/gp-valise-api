<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\LedgerReader;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $reader = app(LedgerReader::class);

        $revenueEur    = $reader->revenueFeesBalance('eur');
        $revenueXof    = $reader->revenueFeesBalance('xof');
        $expenseEur    = $reader->expensePspBalance('eur');
        $profitNetEur  = $revenueEur + $expenseEur; // expensePsp est négatif

        return [
            Stat::make('Revenue EUR', number_format($revenueEur / 100, 2) . ' €')
                ->description('Commissions perçues')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Revenue XOF', number_format($revenueXof) . ' XOF')
                ->description('Commissions perçues')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Profit net EUR', number_format($profitNetEur / 100, 2) . ' €')
                ->description('Revenue − frais PSP')
                ->color($profitNetEur >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-calculator'),
        ];
    }
}
