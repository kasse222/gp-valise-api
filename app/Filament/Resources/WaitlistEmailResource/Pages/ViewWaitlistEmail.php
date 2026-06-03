<?php

namespace App\Filament\Resources\WaitlistEmailResource\Pages;

use App\Filament\Resources\WaitlistEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWaitlistEmail extends ViewRecord
{
    protected static string $resource = WaitlistEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
