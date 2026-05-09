<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatusEnum;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Bookings';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(BookingStatusEnum $state): string => $state->label())
                    ->color(fn(BookingStatusEnum $state): string => match ($state) {
                        BookingStatusEnum::EN_PAIEMENT => 'warning',
                        BookingStatusEnum::CONFIRMEE => 'info',
                        BookingStatusEnum::LIVREE => 'success',
                        BookingStatusEnum::EN_LITIGE => 'danger',
                        BookingStatusEnum::REMBOURSEE => 'gray',
                        BookingStatusEnum::TERMINE => 'success',
                        BookingStatusEnum::ANNULE,
                        BookingStatusEnum::EXPIREE,
                        BookingStatusEnum::PAIEMENT_ECHOUE => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Expéditeur')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('trip.departure')
                    ->label('Départ')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('trip.destination')
                    ->label('Destination')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('has_dispute')
                    ->label('Litige')
                    ->boolean()
                    ->getStateUsing(fn(Booking $record): bool => $record->disputed_at !== null),

                Tables\Columns\TextColumn::make('escrow_releasable_at')
                    ->label('Escrow libérable')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(BookingStatusEnum::cases())
                            ->mapWithKeys(fn(BookingStatusEnum $status): array => [
                                $status->value => $status->label(),
                            ])
                            ->toArray()
                    ),

                Tables\Filters\Filter::make('disputed')
                    ->label('Avec litige')
                    ->query(fn($query) => $query->whereNotNull('disputed_at')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn(BookingStatusEnum $state): string => $state->label())
                            ->color(fn(BookingStatusEnum $state): string => $state->color()),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Infolists\Components\Section::make('Expéditeur & trajet')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Expéditeur')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('trip.user.email')
                            ->label('Voyageur')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('trip.departure')
                            ->label('Départ')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('trip.destination')
                            ->label('Destination')
                            ->placeholder('—'),
                    ]),

                Infolists\Components\Section::make('Escrow & litige')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\IconEntry::make('has_dispute')
                            ->label('Litige actif')
                            ->boolean()
                            ->getStateUsing(fn(Booking $record): bool => $record->disputed_at !== null),

                        Infolists\Components\TextEntry::make('disputed_at')
                            ->label('Litige ouvert le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('escrow_releasable_at')
                            ->label('Escrow libérable le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),

                Infolists\Components\Section::make('Transactions')
                    ->schema([
                        RepeatableEntry::make('transactions')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn($state): string => $state?->label() ?? '-')
                                    ->color(fn($state): string => $state?->color() ?? 'gray'),

                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn($state): string => $state?->label() ?? '-')
                                    ->color(fn($state): string => $state?->color() ?? 'gray'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Montant')
                                    ->formatStateUsing(
                                        fn(int $state, $record): string =>
                                        number_format($state / 100, 2, ',', ' ') . ' ' . $record->currency->value
                                    ),

                                Infolists\Components\TextEntry::make('provider_transaction_id')
                                    ->label('Provider Tx ID')
                                    ->copyable()
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Créée le')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(5),
                    ]),
            ]);
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
