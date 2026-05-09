<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;

final class LedgerReader
{
    public function balanceFor(string $slug): int
    {
        $account = LedgerAccount::where('slug', $slug)->firstOrFail();

        return $account->balance();
    }

    public function escrowBalance(string $currency): int
    {
        return $this->balanceFor("escrow_{$currency}");
    }

    public function revenueFeesBalance(string $currency): int
    {
        return $this->balanceFor("revenue_fees_{$currency}");
    }

    public function payableVoyageurBalance(string $currency): int
    {
        return $this->balanceFor("payable_voyageur_{$currency}");
    }

    public function expensePspBalance(string $currency): int
    {
        return $this->balanceFor("expense_psp_{$currency}");
    }

    /**
     * Vérifie que SUM(debits) = SUM(credits) sur toutes les entries.
     */
    public function isBalanced(): bool
    {
        $debits  = (int) LedgerEntry::where('direction', 'DEBIT')->sum('amount');
        $credits = (int) LedgerEntry::where('direction', 'CREDIT')->sum('amount');

        return $debits === $credits;
    }
}
