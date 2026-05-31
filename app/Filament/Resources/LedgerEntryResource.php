<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\LedgerDirectionEnum;
use App\Filament\Resources\LedgerEntryResource\Pages;
use App\Models\LedgerEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Ledger Entries';

    protected static ?string $modelLabel = 'Ledger Entry';

    protected static ?string $pluralModelLabel = 'Ledger Entries';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Écriture comptable')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('account.slug')
                            ->label('Compte')
                            ->badge(),

                        TextEntry::make('account.type')
                            ->label('Type compte')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state?->label() ?? '-'),

                        TextEntry::make('direction')
                            ->label('Sens')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                LedgerDirectionEnum::DEBIT  => 'danger',
                                LedgerDirectionEnum::CREDIT => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : (string)$state),
                    ]),

                Section::make('Montant')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('amount')
                            ->label('Montant')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->getStateUsing(function (LedgerEntry $record): string {
                                $currency = $record->currency instanceof \BackedEnum
                                    ? $record->currency->value
                                    : (string) $record->currency;
                                return number_format($record->amount / 100, 2, ',', ' ') . ' ' . $currency;
                            }),

                        TextEntry::make('transaction_id')
                            ->label('Transaction #')
                            ->url(
                                fn(LedgerEntry $record): ?string => $record->transaction_id
                                    ? route('filament.admin.resources.transactions.view', ['record' => $record->transaction_id])
                                    : null
                            ),
                    ]),

                Section::make('Détails')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Description'),

                        TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.slug')
                    ->label('Compte')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.type')
                    ->label('Type compte')
                    ->badge()
                    ->formatStateUsing(fn($state): string => $state?->label() ?? '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('direction')
                    ->label('Sens')
                    ->badge()
                    ->formatStateUsing(fn(LedgerDirectionEnum $state): string => $state->value)
                    ->color(fn(LedgerDirectionEnum $state): string => match ($state) {
                        LedgerDirectionEnum::DEBIT => 'danger',
                        LedgerDirectionEnum::CREDIT => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(function (int $state, LedgerEntry $record): string {
                        $currency = $record->currency instanceof \BackedEnum
                            ? $record->currency->value
                            : $record->currency;

                        return number_format($state / 100, 2, ',', ' ') . ' ' . $currency;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction')
                    ->sortable()
                    ->url(
                        fn(LedgerEntry $record): ?string => $record->transaction_id
                            ? route('filament.admin.resources.transactions.view', [
                                'record' => $record->transaction_id,
                            ])
                            : null
                    ),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->label('Sens')
                    ->options([
                        LedgerDirectionEnum::DEBIT->value => 'Debit',
                        LedgerDirectionEnum::CREDIT->value => 'Credit',
                    ]),

                SelectFilter::make('account_id')
                    ->label('Compte')
                    ->relationship('account', 'slug')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('currency')
                    ->label('Devise')
                    ->options([
                        'EUR' => 'EUR',
                        'XOF' => 'XOF',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerEntries::route('/'),
            'view' => Pages\ViewLedgerEntry::route('/{record}'),
        ];
    }
}
