<?php

namespace App\Filament\Resources\LedgerAccountResource\Pages;

use App\Filament\Resources\LedgerAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
