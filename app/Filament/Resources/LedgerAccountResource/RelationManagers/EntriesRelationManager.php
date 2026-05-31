<?php

namespace App\Filament\Resources\LedgerAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';
    protected static ?string $title = 'Entrées comptables';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction #')
                    ->sortable(),

                Tables\Columns\TextColumn::make('direction')
                    ->label('Sens')
                    ->badge()
                    ->color(fn($state) => match ((string)($state instanceof \BackedEnum ? $state->value : $state)) {
                        'DEBIT'  => 'danger',
                        'CREDIT' => 'success',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : (string)$state),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->getStateUsing(fn($record) => number_format($record->amount / 100, 2, ',', ' ') . ' ' . $record->currency),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description'),
            ])
            ->paginated([10, 25, 50]);
    }
}
