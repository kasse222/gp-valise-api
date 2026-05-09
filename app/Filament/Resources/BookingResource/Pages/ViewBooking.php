<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Actions\Booking\OpenDispute;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openDispute')
                ->label('Ouvrir un litige')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->requiresConfirmation()
                ->visible(
                    fn(): bool => $this->record instanceof Booking
                        && ! $this->record->hasActiveDispute()
                        && $this->record->status->canEnterDispute()
                )
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Raison du litige')
                        ->required()
                        ->minLength(10)
                        ->maxLength(1000),
                ])
                ->action(function (array $data, OpenDispute $openDispute): void {
                    try {
                        $openDispute->execute(
                            booking: $this->record,
                            actor: auth()->user(),
                            reason: $data['reason'],
                        );

                        Notification::make()
                            ->title('Litige ouvert avec succès')
                            ->success()
                            ->send();

                        $this->redirect(static::getResource()::getUrl('view', [
                            'record' => $this->record,
                        ]));
                    } catch (ValidationException $e) {
                        throw $e;
                    }
                }),
        ];
    }
}
