<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Actions\Transaction\MarkPayoutCompleted;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markPayoutCompleted')
                ->label('Marquer payout complété')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(
                    fn(): bool =>
                    $this->record instanceof Transaction
                        && $this->record->type === TransactionTypeEnum::PAYOUT
                        && $this->record->status === TransactionStatusEnum::PENDING
                )
                ->action(function (MarkPayoutCompleted $markPayoutCompleted): void {
                    if (! $this->record instanceof Transaction) {
                        throw ValidationException::withMessages([
                            'transaction' => 'Transaction invalide.',
                        ]);
                    }

                    $markPayoutCompleted->execute($this->record);

                    Notification::make()
                        ->title('Payout complété')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', [
                        'record' => $this->record,
                    ]));
                }),
        ];
    }
}
