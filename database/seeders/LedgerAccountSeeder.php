<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LedgerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASSET — EUR
            ['slug' => 'external_psp_clearing_eur', 'name' => 'PSP Clearing (EUR)',           'type' => 'ASSET',     'currency' => 'EUR'],
            ['slug' => 'escrow_eur',                'name' => 'Escrow clients (EUR)',          'type' => 'ASSET',     'currency' => 'EUR'],

            // ASSET — XOF
            ['slug' => 'external_psp_clearing_xof', 'name' => 'PSP Clearing (XOF)',           'type' => 'ASSET',     'currency' => 'XOF'],
            ['slug' => 'escrow_xof',                'name' => 'Escrow clients (XOF)',          'type' => 'ASSET',     'currency' => 'XOF'],

            // LIABILITY — EUR
            ['slug' => 'payable_voyageur_eur',      'name' => 'Montants dus voyageurs (EUR)',  'type' => 'LIABILITY', 'currency' => 'EUR'],

            // LIABILITY — XOF
            ['slug' => 'payable_voyageur_xof',      'name' => 'Montants dus voyageurs (XOF)',  'type' => 'LIABILITY', 'currency' => 'XOF'],

            // REVENUE — EUR
            ['slug' => 'revenue_fees_eur',          'name' => 'Commissions GP-Valise (EUR)',   'type' => 'REVENUE',   'currency' => 'EUR'],

            // REVENUE — XOF
            ['slug' => 'revenue_fees_xof',          'name' => 'Commissions GP-Valise (XOF)',   'type' => 'REVENUE',   'currency' => 'XOF'],

            // EXPENSE — EUR
            ['slug' => 'expense_psp_eur',           'name' => 'Frais PSP (EUR)',               'type' => 'EXPENSE',   'currency' => 'EUR'],

            // EXPENSE — XOF
            ['slug' => 'expense_psp_xof',           'name' => 'Frais PSP (XOF)',               'type' => 'EXPENSE',   'currency' => 'XOF'],
        ];

        foreach ($accounts as $account) {
            DB::table('ledger_accounts')->updateOrInsert(
                ['slug' => $account['slug']],
                [...$account, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $this->command->info('✔ LedgerAccountSeeder terminé — ' . count($accounts) . ' comptes.');
    }
}
