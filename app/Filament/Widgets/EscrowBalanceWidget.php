<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\LedgerReader;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EscrowBalanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        try {
            $reader    = app(LedgerReader::class);
            $escrowEur = $reader->escrowBalance('eur');
            $escrowXof = $reader->escrowBalance('xof');
            $balanced  = $reader->isBalanced();

            return [
                Stat::make('Escrow EUR', number_format($escrowEur / 100, 2) . ' €')
                    ->description('Fonds clients bloqués')
                    ->color($escrowEur > 0 ? 'warning' : 'gray')
                    ->icon('heroicon-o-banknotes'),

                Stat::make('Escrow XOF', number_format($escrowXof) . ' XOF')
                    ->description('Fonds clients bloqués')
                    ->color($escrowXof > 0 ? 'warning' : 'gray')
                    ->icon('heroicon-o-banknotes'),

                Stat::make('Ledger', $balanced ? 'Équilibré ✓' : '⚠ Déséquilibré')
                    ->description('SUM(debits) = SUM(credits)')
                    ->color($balanced ? 'success' : 'danger')
                    ->icon('heroicon-o-scale'),
            ];
        } catch (\Throwable $e) {
            return [
                Stat::make('Escrow EUR', '— €')->description('Données indisponibles')->color('gray'),
                Stat::make('Escrow XOF', '— XOF')->description('Données indisponibles')->color('gray'),
                Stat::make('Ledger', '—')->description('Données indisponibles')->color('gray'),
            ];
        }
    }
}
