<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        // If no companies, create at least a default list or wait
        // Let's assume there's at least one company or we can seed without company_id if it's allowed (nullable)
        
        $defaultAccounts = [
            // Assets (1000s)
            ['code' => '1010', 'name' => 'Cash in Bank - PHP', 'type' => 'asset', 'category' => 'Current Asset'],
            ['code' => '1020', 'name' => 'Cash on Hand', 'type' => 'asset', 'category' => 'Current Asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'category' => 'Current Asset'],
            
            // Liabilities (2000s)
            ['code' => '2200', 'name' => 'Reservation Fees Payable / Customer Deposits', 'type' => 'liability', 'category' => 'Current Liability'],
            ['code' => '2300', 'name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'Current Liability'],
            
            // Equity (3000s)
            ['code' => '3000', 'name' => 'Retained Earnings', 'type' => 'equity', 'category' => 'Equity'],
            
            // Revenue (4000s)
            ['code' => '4100', 'name' => 'Property Sales / Income', 'type' => 'revenue', 'category' => 'Operating Income'],
            ['code' => '4200', 'name' => 'Other Income', 'type' => 'revenue', 'category' => 'Other Income'],
            
            // Expenses (5000s)
            ['code' => '5000', 'name' => 'Operating Expenses', 'type' => 'expense', 'category' => 'Operating Expense'],
        ];

        foreach ($defaultAccounts as $account) {
            // For now, if companies exist, seed for each. If not, seed with null company_id.
            if ($companies->count() > 0) {
                foreach ($companies as $company) {
                    DB::table('chart_of_accounts')->updateOrInsert(
                        ['company_id' => $company->id, 'code' => $account['code']],
                        array_merge($account, ['created_at' => now(), 'updated_at' => now()])
                    );
                }
            } else {
                DB::table('chart_of_accounts')->updateOrInsert(
                    ['company_id' => null, 'code' => $account['code']],
                    array_merge($account, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        }
    }
}
