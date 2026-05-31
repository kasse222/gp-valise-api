<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerAccountResource\Pages;
use App\Filament\Resources\LedgerAccountResource\RelationManagers\EntriesRelationManager;
use App\Models\LedgerAccount;
use App\Services\LedgerReader;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class LedgerAccountResource extends Resource
{
    protected static ?string $model = LedgerAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Ledger Accounts';

    protected static ?string $modelLabel = 'Ledger Account';

    protected static ?string $pluralModelLabel = 'Ledger Accounts';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Compte')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('slug')
                            ->label('Slug')
                            ->badge(),

                        TextEntry::make('name')
                            ->label('Nom'),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state?->label() ?? '-'),

                        TextEntry::make('currency')
                            ->label('Devise')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : (string) $state),
                    ]),

                Section::make('Balance')
                    ->schema([
                        TextEntry::make('balance_display')
                            ->label('Balance actuelle')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->getStateUsing(function (LedgerAccount $record): string {
                                $reader   = app(LedgerReader::class);
                                $balance  = $reader->balanceFor($record->slug);
                                $currency = $record->currency instanceof \BackedEnum
                                    ? $record->currency->value
                                    : (string) $record->currency;
                                return number_format($balance / 100, 2, ',', ' ') . ' ' . $currency;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('slug')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Compte')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn($state): string => $state?->label() ?? '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Devise')
                    ->badge()
                    ->formatStateUsing(
                        fn($state): string => $state instanceof \BackedEnum ? $state->value : (string) $state
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(function (LedgerAccount $record): string {
                        $reader   = app(LedgerReader::class);
                        $balance  = $reader->balanceFor($record->slug);
                        $currency = $record->currency instanceof \BackedEnum
                            ? $record->currency->value
                            : (string) $record->currency;
                        return number_format($balance / 100, 2, ',', ' ') . ' ' . $currency;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(\App\Enums\LedgerAccountTypeEnum::cases())
                            ->mapWithKeys(fn($type): array => [
                                $type->value => $type->label(),
                            ])
                            ->toArray()
                    ),

                SelectFilter::make('currency')
                    ->label('Devise')
                    ->options(
                        collect(\App\Enums\CurrencyEnum::cases())
                            ->mapWithKeys(fn($currency): array => [
                                $currency->value => $currency->value,
                            ])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerAccounts::route('/'),
            'view'  => Pages\ViewLedgerAccount::route('/{record}'),
        ];
    }
}
