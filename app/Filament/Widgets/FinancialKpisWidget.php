<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Services\LedgerReader;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialKpisWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $reader = app(LedgerReader::class);

        // Volume traité = somme des CHARGE COMPLETED
        $volumeEur = Transaction::where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->where('currency', 'EUR')
            ->sum('amount');

        // Montants dus voyageurs = balance payable_voyageur_eur
        $payableVoyageur = $reader->balanceFor('payable_voyageur_eur');

        // Escrow actif
        $escrowActif = $reader->balanceFor('escrow_eur');

        // Frais PSP
        $fraisPsp = abs($reader->balanceFor('expense_psp_eur'));

        return [
            Stat::make('Volume traité', number_format($volumeEur / 100, 2) . ' €')
                ->description('Total CHARGE COMPLETED EUR')
                ->color('info')
                ->icon('heroicon-o-circle-stack'),

            Stat::make('Escrow actif', number_format($escrowActif / 100, 2) . ' €')
                ->description('Fonds en attente de payout')
                ->color($escrowActif > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-lock-closed'),

            Stat::make('Dus voyageurs', number_format($payableVoyageur / 100, 2) . ' €')
                ->description('Payouts en attente')
                ->color($payableVoyageur > 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-user-group'),

            Stat::make('Frais PSP', number_format($fraisPsp / 100, 2) . ' €')
                ->description('Coûts providers EUR')
                ->color('danger')
                ->icon('heroicon-o-receipt-percent'),
        ];
    }
}
