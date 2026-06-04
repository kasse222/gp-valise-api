<?php

declare(strict_types=1);

namespace App\Filament\Resources\KycRequestResource\Pages;

use App\Filament\Resources\KycRequestResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewKycRequest extends ViewRecord
{
    protected static string $resource = KycRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Utilisateur')->schema([
                TextEntry::make('user.email')->label('Email'),
                TextEntry::make('user.first_name')->label('Prénom'),
                TextEntry::make('user.last_name')->label('Nom'),
                TextEntry::make('user.phone')->label('Téléphone')->placeholder('—'),
                TextEntry::make('user.country')->label('Pays')->placeholder('—'),
            ])->columns(2),

            Section::make('Statut KYC')->schema([
                TextEntry::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),
                TextEntry::make('submitted_at')->label('Soumis le')->dateTime('d/m/Y H:i'),
                TextEntry::make('reviewed_at')->label('Traité le')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('reviewer.email')->label('Traité par')->placeholder('—'),
                TextEntry::make('admin_notes')->label('Notes admin')->placeholder('—'),
                TextEntry::make('rejection_reason')->label('Raison du rejet')->placeholder('—'),
            ])->columns(2),

            Section::make('Photos')->schema([
                ImageEntry::make('id_photo_path')->label("Photo d'identité"),
                ImageEntry::make('parcel_photo_path')->label('Photo du colis'),
            ])->columns(2),
        ]);
    }
}
