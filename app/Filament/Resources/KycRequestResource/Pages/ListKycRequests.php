<?php

declare(strict_types=1);

namespace App\Filament\Resources\KycRequestResource\Pages;

use App\Filament\Resources\KycRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListKycRequests extends ListRecords
{
    protected static string $resource = KycRequestResource::class;
}
