<?php

declare(strict_types=1);

namespace Tests\Traits;

use Database\Seeders\LedgerAccountSeeder;

trait SeedsLedgerAccounts
{
    protected function seedLedgerAccounts(): void
    {
        $this->seed(LedgerAccountSeeder::class);
    }
}
