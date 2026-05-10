<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        return [
            Stat::make('En paiement', Booking::where('status', BookingStatusEnum::EN_PAIEMENT)->count())
                ->description('En attente de paiement PSP')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Confirmées', Booking::where('status', BookingStatusEnum::CONFIRMEE)->count())
                ->description('Paiement validé')
                ->color('info')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Livrées', Booking::where('status', BookingStatusEnum::LIVREE)->count())
                ->description('Escrow en cours')
                ->color('success')
                ->icon('heroicon-o-truck'),

            Stat::make('En litige', Booking::where('status', BookingStatusEnum::EN_LITIGE)->count())
                ->description('Escrow bloqué')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
