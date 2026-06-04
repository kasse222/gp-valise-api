<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Kyc\ApproveKycRequest;
use App\Actions\Kyc\RejectKycRequest;
use App\Enums\KycStatusEnum;
use App\Filament\Resources\KycRequestResource\Pages;
use App\Models\KycRequest;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KycRequestResource extends Resource
{
    protected static ?string $model = KycRequest::class;
    protected static ?string $navigationIcon  = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'KYC';
    protected static ?string $navigationGroup = 'Utilisateurs';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('Prénom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.last_name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Traité le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.email')
                    ->label('Traité par')
                    ->placeholder('—'),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        KycStatusEnum::PENDING->value  => 'En attente',
                        KycStatusEnum::APPROVED->value => 'Approuvé',
                        KycStatusEnum::REJECTED->value => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(KycRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('notes')
                            ->label('Notes (optionnel)')
                            ->rows(3),
                    ])
                    ->action(function (KycRequest $record, array $data): void {
                        app(ApproveKycRequest::class)->execute(
                            $record,
                            auth()->user(),
                            $data['notes'] ?? null
                        );
                        Notification::make()
                            ->title('KYC approuvé')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(KycRequest $record) => $record->isPending())
                    ->form([
                        Textarea::make('reason')
                            ->label('Raison du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (KycRequest $record, array $data): void {
                        app(RejectKycRequest::class)->execute(
                            $record,
                            auth()->user(),
                            $data['reason']
                        );
                        Notification::make()
                            ->title('KYC rejeté')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycRequests::route('/'),
            'view'  => Pages\ViewKycRequest::route('/{record}'),
        ];
    }
}
