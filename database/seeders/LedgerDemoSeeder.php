<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Enums\LedgerDirectionEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $psp     = LedgerAccount::where('slug', 'external_psp_clearing_eur')->firstOrFail();
            $escrow  = LedgerAccount::where('slug', 'escrow_eur')->firstOrFail();
            $payable = LedgerAccount::where('slug', 'payable_voyageur_eur')->firstOrFail();
            $revenue = LedgerAccount::where('slug', 'revenue_fees_eur')->firstOrFail();
            $expense = LedgerAccount::where('slug', 'expense_psp_eur')->firstOrFail();

            $sender   = User::where('email', 'sender@gpvalise.com')->firstOrFail();
            $traveler = User::where('email', 'traveler@gpvalise.com')->firstOrFail();

            // ── Booking CONFIRMEE ──────────────────────────────────────
            $bookingConfirmee = Booking::where('status', BookingStatusEnum::CONFIRMEE)->first();

            if ($bookingConfirmee) {
                $charge1 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingConfirmee->id, 'type' => TransactionTypeEnum::CHARGE],
                    [
                        'user_id'        => $sender->id,
                        'amount'         => 6400,
                        'currency'       => 'EUR',
                        'status'         => TransactionStatusEnum::COMPLETED,
                        'processed_at'   => now()->subDays(2),
                        'correlation_id' => Str::uuid(),
                    ]
                );

                $this->writeEntry($psp,    $charge1->id, LedgerDirectionEnum::DEBIT,  6400, 'EUR', "CHARGE booking #{$bookingConfirmee->id}");
                $this->writeEntry($escrow, $charge1->id, LedgerDirectionEnum::CREDIT, 6400, 'EUR', "CHARGE booking #{$bookingConfirmee->id}");
            }

            // ── Booking LIVREE ─────────────────────────────────────────
            $bookingLivree = Booking::where('status', BookingStatusEnum::LIVREE)->first();

            if ($bookingLivree) {
                $charge2 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingLivree->id, 'type' => TransactionTypeEnum::CHARGE],
                    ['user_id' => $sender->id, 'amount' => 8000, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(5), 'correlation_id' => Str::uuid()]
                );
                $fee2 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingLivree->id, 'type' => TransactionTypeEnum::FEE],
                    ['user_id' => $sender->id, 'amount' => 800, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(1), 'correlation_id' => Str::uuid()]
                );
                $payout2 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingLivree->id, 'type' => TransactionTypeEnum::PAYOUT],
                    ['user_id' => $traveler->id, 'amount' => 7200, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(1), 'correlation_id' => Str::uuid()]
                );
                $pfee2 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingLivree->id, 'type' => TransactionTypeEnum::PAYMENT_FEE],
                    ['user_id' => $sender->id, 'amount' => 160, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(5), 'correlation_id' => Str::uuid()]
                );

                $this->writeEntry($psp,    $charge2->id, LedgerDirectionEnum::DEBIT,  8000, 'EUR', "CHARGE booking #{$bookingLivree->id}");
                $this->writeEntry($escrow, $charge2->id, LedgerDirectionEnum::CREDIT, 8000, 'EUR', "CHARGE booking #{$bookingLivree->id}");

                $this->writeEntry($escrow,  $payout2->id, LedgerDirectionEnum::DEBIT,  7200, 'EUR', "PAYOUT booking #{$bookingLivree->id}");
                $this->writeEntry($payable, $payout2->id, LedgerDirectionEnum::CREDIT, 7200, 'EUR', "PAYOUT booking #{$bookingLivree->id}");

                $this->writeEntry($escrow,  $fee2->id, LedgerDirectionEnum::DEBIT,  800, 'EUR', "FEE booking #{$bookingLivree->id}");
                $this->writeEntry($revenue, $fee2->id, LedgerDirectionEnum::CREDIT, 800, 'EUR', "FEE booking #{$bookingLivree->id}");

                $this->writeEntry($expense, $pfee2->id, LedgerDirectionEnum::DEBIT,  160, 'EUR', "PAYMENT_FEE booking #{$bookingLivree->id}");
                $this->writeEntry($psp,     $pfee2->id, LedgerDirectionEnum::CREDIT, 160, 'EUR', "PAYMENT_FEE booking #{$bookingLivree->id}");
            }

            // ── Booking TERMINE ────────────────────────────────────────
            $bookingTermine = Booking::where('status', BookingStatusEnum::TERMINE)->first();

            if ($bookingTermine) {
                $charge3 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingTermine->id, 'type' => TransactionTypeEnum::CHARGE],
                    ['user_id' => $sender->id, 'amount' => 7200, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(10), 'correlation_id' => Str::uuid()]
                );
                $fee3 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingTermine->id, 'type' => TransactionTypeEnum::FEE],
                    ['user_id' => $sender->id, 'amount' => 720, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(7), 'correlation_id' => Str::uuid()]
                );
                $payout3 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingTermine->id, 'type' => TransactionTypeEnum::PAYOUT],
                    ['user_id' => $traveler->id, 'amount' => 6480, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(7), 'correlation_id' => Str::uuid()]
                );
                $pfee3 = Transaction::firstOrCreate(
                    ['booking_id' => $bookingTermine->id, 'type' => TransactionTypeEnum::PAYMENT_FEE],
                    ['user_id' => $sender->id, 'amount' => 144, 'currency' => 'EUR', 'status' => TransactionStatusEnum::COMPLETED, 'processed_at' => now()->subDays(10), 'correlation_id' => Str::uuid()]
                );

                $this->writeEntry($psp,    $charge3->id, LedgerDirectionEnum::DEBIT,  7200, 'EUR', "CHARGE booking #{$bookingTermine->id}");
                $this->writeEntry($escrow, $charge3->id, LedgerDirectionEnum::CREDIT, 7200, 'EUR', "CHARGE booking #{$bookingTermine->id}");

                $this->writeEntry($escrow,  $payout3->id, LedgerDirectionEnum::DEBIT,  6480, 'EUR', "PAYOUT booking #{$bookingTermine->id}");
                $this->writeEntry($payable, $payout3->id, LedgerDirectionEnum::CREDIT, 6480, 'EUR', "PAYOUT booking #{$bookingTermine->id}");

                $this->writeEntry($escrow,  $fee3->id, LedgerDirectionEnum::DEBIT,  720, 'EUR', "FEE booking #{$bookingTermine->id}");
                $this->writeEntry($revenue, $fee3->id, LedgerDirectionEnum::CREDIT, 720, 'EUR', "FEE booking #{$bookingTermine->id}");

                $this->writeEntry($expense, $pfee3->id, LedgerDirectionEnum::DEBIT,  144, 'EUR', "PAYMENT_FEE booking #{$bookingTermine->id}");
                $this->writeEntry($psp,     $pfee3->id, LedgerDirectionEnum::CREDIT, 144, 'EUR', "PAYMENT_FEE booking #{$bookingTermine->id}");
            }

            $this->command->info('✅ LedgerDemoSeeder — écritures double-entry créées');
        });
    }

    private function writeEntry(
        LedgerAccount $account,
        int $transactionId,
        LedgerDirectionEnum $direction,
        int $amount,
        string $currency,
        string $description
    ): void {
        $exists = LedgerEntry::where('account_id', $account->id)
            ->where('transaction_id', $transactionId)
            ->where('direction', $direction)
            ->exists();

        if (! $exists) {
            LedgerEntry::create([
                'account_id'     => $account->id,
                'transaction_id' => $transactionId,
                'direction'      => $direction,
                'amount'         => $amount,
                'currency'       => $currency,
                'description'    => $description,
            ]);
        }
    }
}
