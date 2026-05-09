<?php

namespace App\Filament\Resources;

use App\Enums\CurrencyEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;


class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(TransactionTypeEnum $state): string => $state->label())
                    ->color(fn(TransactionTypeEnum $state): string => match ($state) {
                        TransactionTypeEnum::CHARGE => 'info',
                        TransactionTypeEnum::PAYOUT => 'success',
                        TransactionTypeEnum::REFUND => 'warning',
                        TransactionTypeEnum::FEE => 'primary',
                        TransactionTypeEnum::PAYMENT_FEE => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(TransactionStatusEnum $state): string => $state->label())
                    ->color(fn(TransactionStatusEnum $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(
                        fn(int $state, Transaction $record): string =>
                        number_format($state / 100, 2, ',', ' ') . ' ' . $record->currency->value
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_id')
                    ->label('Booking')
                    ->sortable()
                    ->url(fn(Transaction $record): string => route('filament.admin.resources.bookings.view', [
                        'record' => $record->booking_id,
                    ])),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('platformAccount.provider')
                    ->label('Provider')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('provider_transaction_id')
                    ->label('Provider Tx ID')
                    ->copyable()
                    ->limit(24)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(TransactionTypeEnum::cases())
                            ->mapWithKeys(fn(TransactionTypeEnum $type) => [
                                $type->value => $type->label(),
                            ])
                            ->toArray()
                    ),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(TransactionStatusEnum::cases())
                            ->mapWithKeys(fn(TransactionStatusEnum $status) => [
                                $status->value => $status->label(),
                            ])
                            ->toArray()
                    ),

                SelectFilter::make('currency')
                    ->label('Devise')
                    ->options(
                        collect(CurrencyEnum::cases())
                            ->mapWithKeys(fn(CurrencyEnum $currency) => [
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



    public static function getRelations(): array
    {
        return [];
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
