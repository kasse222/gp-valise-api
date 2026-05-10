<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Dispute\AddDisputeMessage;
use App\Actions\Dispute\UpdateDisputeStatus;
use App\Actions\Booking\ResolveDispute;
use App\Enums\DisputeDecisionEnum;
use App\Enums\DisputeStatusEnum;
use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static ?string $navigationIcon    = 'heroicon-o-scale';
    protected static ?string $navigationLabel   = 'Litiges';
    protected static ?string $modelLabel        = 'Litige';
    protected static ?string $pluralModelLabel  = 'Litiges';
    protected static ?string $navigationGroup   = 'Finance';
    protected static ?int    $navigationSort    = 10;

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_id')
                    ->label('Booking')
                    ->sortable()
                    ->url(
                        fn(Dispute $record): string =>
                        route('filament.admin.resources.bookings.view', $record->booking_id)
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(DisputeStatusEnum $state): string => $state->label())
                    ->color(fn(DisputeStatusEnum $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('decision')
                    ->label('Décision')
                    ->badge()
                    ->formatStateUsing(fn(?DisputeDecisionEnum $state): string => $state?->label() ?? '—')
                    ->color(fn(?DisputeDecisionEnum $state): string => match ($state) {
                        DisputeDecisionEnum::REFUND => 'warning',
                        DisputeDecisionEnum::PAYOUT => 'success',
                        null                        => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('opener.email')
                    ->label('Ouvert par')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('assignee.email')
                    ->label('Assigné à')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Résolu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ouvert le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(DisputeStatusEnum::cases())
                            ->mapWithKeys(fn(DisputeStatusEnum $s) => [$s->value => $s->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('decision')
                    ->label('Décision')
                    ->options(
                        collect(DisputeDecisionEnum::cases())
                            ->mapWithKeys(fn(DisputeDecisionEnum $d) => [$d->value => $d->label()])
                            ->toArray()
                    ),

                Tables\Filters\Filter::make('unresolved')
                    ->label('Non résolus')
                    ->query(fn($query) => $query->where('status', '!=', DisputeStatusEnum::RESOLVED)),

                Tables\Filters\Filter::make('unassigned')
                    ->label('Non assignés')
                    ->query(fn($query) => $query->whereNull('assigned_to')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Dispute ───────────────────────────────────────────────────
                Infolists\Components\Section::make('Litige')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn(DisputeStatusEnum $state): string => $state->label())
                            ->color(fn(DisputeStatusEnum $state): string => $state->color()),

                        Infolists\Components\TextEntry::make('decision')
                            ->label('Décision')
                            ->badge()
                            ->formatStateUsing(fn(?DisputeDecisionEnum $state): string => $state?->label() ?? '—')
                            ->color(fn(?DisputeDecisionEnum $state): string => match ($state) {
                                DisputeDecisionEnum::REFUND => 'warning',
                                DisputeDecisionEnum::PAYOUT => 'success',
                                null                        => 'gray',
                            })
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('reason')
                            ->label('Raison d\'ouverture')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('resolution')
                            ->label('Résolution')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('opener.email')
                            ->label('Ouvert par')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('assignee.email')
                            ->label('Assigné à')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('resolver.email')
                            ->label('Résolu par')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Ouvert le')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('resolved_at')
                            ->label('Résolu le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ])
                    ->footerActions([
                        // ── Changer statut ────────────────────────────────────
                        \Filament\Infolists\Components\Actions\Action::make('update_status')
                            ->label('Changer le statut')
                            ->icon('heroicon-o-arrow-path')
                            ->color('info')
                            ->visible(fn(Dispute $record): bool => ! $record->isResolved())
                            ->form(fn(Dispute $record): array => [
                                Forms\Components\Select::make('new_status')
                                    ->label('Nouveau statut')
                                    ->options(
                                        collect($record->status->allowedTransitions())
                                            ->mapWithKeys(fn(DisputeStatusEnum $s) => [$s->value => $s->label()])
                                            ->toArray()
                                    )
                                    ->required()
                                    ->native(false),

                                Forms\Components\Textarea::make('reason')
                                    ->label('Raison')
                                    ->required()
                                    ->rows(2),
                            ])
                            ->modalHeading('Changer le statut du litige')
                            ->modalSubmitActionLabel('Confirmer')
                            ->action(function (Dispute $record, array $data): void {
                                try {
                                    app(UpdateDisputeStatus::class)->execute(
                                        dispute: $record,
                                        admin: auth()->user(),
                                        newStatus: DisputeStatusEnum::from($data['new_status']),
                                        reason: $data['reason'],
                                    );

                                    Notification::make()
                                        ->title('Statut mis à jour')
                                        ->body(DisputeStatusEnum::from($data['new_status'])->label())
                                        ->success()
                                        ->send();
                                } catch (\Illuminate\Validation\ValidationException $e) {
                                    Notification::make()
                                        ->title('Erreur')
                                        ->body(collect($e->errors())->flatten()->first())
                                        ->danger()
                                        ->send();
                                }
                            }),

                        // ── Résoudre ──────────────────────────────────────────
                        \Filament\Infolists\Components\Actions\Action::make('resolve')
                            ->label('Résoudre le litige')
                            ->icon('heroicon-o-check-badge')
                            ->color('danger')
                            ->visible(fn(Dispute $record): bool => ! $record->isResolved())
                            ->form([
                                Forms\Components\Select::make('decision')
                                    ->label('Décision')
                                    ->options([
                                        ResolveDispute::DECISION_REFUND => '💸 Rembourser l\'expéditeur',
                                        ResolveDispute::DECISION_PAYOUT => '✅ Payer le voyageur',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\Textarea::make('reason')
                                    ->label('Raison de la décision')
                                    ->required()
                                    ->minLength(10)
                                    ->rows(3),
                            ])
                            ->modalHeading('Résoudre le litige')
                            ->modalDescription('Action irréversible. Tracée dans les audit logs.')
                            ->modalSubmitActionLabel('Confirmer la décision')
                            ->modalIcon('heroicon-o-exclamation-triangle')
                            ->action(function (Dispute $record, array $data): void {
                                try {
                                    app(ResolveDispute::class)->execute(
                                        booking: $record->booking,
                                        admin: auth()->user(),
                                        decision: $data['decision'],
                                        reason: $data['reason'],
                                    );

                                    Notification::make()
                                        ->title('Litige résolu')
                                        ->body(match ($data['decision']) {
                                            ResolveDispute::DECISION_REFUND => 'Remboursement initié.',
                                            ResolveDispute::DECISION_PAYOUT => 'Payout accordé. Booking terminé.',
                                        })
                                        ->success()
                                        ->send();
                                } catch (\Illuminate\Validation\ValidationException $e) {
                                    Notification::make()
                                        ->title('Erreur')
                                        ->body(collect($e->errors())->flatten()->first())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),

                // ── Booking associé ───────────────────────────────────────────
                Infolists\Components\Section::make('Booking associé')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('booking.user.email')
                            ->label('Expéditeur')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('booking.trip.user.email')
                            ->label('Voyageur')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('booking.trip.departure')
                            ->label('Départ')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('booking.trip.destination')
                            ->label('Destination')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('booking.status')
                            ->label('Statut booking')
                            ->badge()
                            ->formatStateUsing(fn($state): string => $state?->label() ?? '—')
                            ->color(fn($state): string => $state?->color() ?? 'gray'),
                    ]),

                // ── Historique statuts ────────────────────────────────────────
                Infolists\Components\Section::make('Historique des statuts')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('statusHistories')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('old_status')
                                    ->label('De')
                                    ->badge()
                                    ->formatStateUsing(fn(?DisputeStatusEnum $state): string => $state?->label() ?? 'Création')
                                    ->color(fn(?DisputeStatusEnum $state): string => $state?->color() ?? 'gray'),

                                Infolists\Components\TextEntry::make('new_status')
                                    ->label('Vers')
                                    ->badge()
                                    ->formatStateUsing(fn(DisputeStatusEnum $state): string => $state->label())
                                    ->color(fn(DisputeStatusEnum $state): string => $state->color()),

                                Infolists\Components\TextEntry::make('changedBy.email')
                                    ->label('Par')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('reason')
                                    ->label('Raison')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Le')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(5),
                    ]),

                // ── Messages ──────────────────────────────────────────────────
                Infolists\Components\Section::make('Messages & preuves')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('messages')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('author.email')
                                    ->label('Auteur'),

                                Infolists\Components\TextEntry::make('body')
                                    ->label('Message')
                                    ->columnSpan(3),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Le')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(5),
                    ])
                    ->footerActions([
                        \Filament\Infolists\Components\Actions\Action::make('add_message')
                            ->label('Ajouter un message')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->color('gray')
                            ->visible(fn(Dispute $record): bool => ! $record->isResolved())
                            ->form([
                                Forms\Components\Textarea::make('body')
                                    ->label('Message')
                                    ->required()
                                    ->rows(3),
                            ])
                            ->modalHeading('Ajouter un message au litige')
                            ->modalSubmitActionLabel('Envoyer')
                            ->action(function (Dispute $record, array $data): void {
                                try {
                                    app(AddDisputeMessage::class)->execute(
                                        dispute: $record,
                                        author: auth()->user(),
                                        body: $data['body'],
                                    );

                                    Notification::make()
                                        ->title('Message ajouté')
                                        ->success()
                                        ->send();
                                } catch (\Illuminate\Validation\ValidationException $e) {
                                    Notification::make()
                                        ->title('Erreur')
                                        ->body(collect($e->errors())->flatten()->first())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),
            ]);
    }

    // ── Permissions ───────────────────────────────────────────────────────────

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
            'index' => Pages\ListDisputes::route('/'),
            'view'  => Pages\ViewDispute::route('/{record}'),
        ];
    }
}
