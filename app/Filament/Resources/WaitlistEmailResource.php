<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WaitlistEmailResource\Pages;
use App\Models\WaitlistEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WaitlistEmailResource extends Resource
{
    protected static ?string $model = WaitlistEmail::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Waitlist';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->disabled(),

            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->disabled(),

            Forms\Components\TextInput::make('role')
                ->label('Rôle')
                ->disabled(),

            Forms\Components\Textarea::make('message')
                ->label('Message')
                ->rows(5)
                ->disabled(),

            Forms\Components\TextInput::make('ip_address')
                ->label('IP')
                ->disabled(),

            Forms\Components\Textarea::make('user_agent')
                ->label('User Agent')
                ->rows(3)
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->colors([
                        'primary' => 'sender',
                        'success' => 'traveler',
                        'gray' => 'curious',
                    ])
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->tooltip(fn(WaitlistEmail $record): ?string => $record->message)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'sender' => 'Sender',
                        'traveler' => 'Traveler',
                        'curious' => 'Curieux',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWaitlistEmails::route('/'),
            'view' => Pages\ViewWaitlistEmail::route('/{record}'),
        ];
    }
}
