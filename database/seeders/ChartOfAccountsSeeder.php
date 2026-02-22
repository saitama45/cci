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
            ['code' => '1500', 'name' => 'Land Acquisition Costs', 'type' => 'asset', 'category' => 'Fixed Asset'],
            ['code' => '1510', 'name' => 'Buildings & Structures', 'type' => 'asset', 'category' => 'Fixed Asset'],
            ['code' => '1520', 'name' => 'Construction in Progress', 'type' => 'asset', 'category' => 'Fixed Asset'],
            
            // Liabilities (2000s)
            ['code' => '2200', 'name' => 'Reservation Fees Payable / Customer Deposits', 'type' => 'liability', 'category' => 'Current Liability'],
            ['code' => '2300', 'name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'Current Liability'],
            ['code' => '2400', 'name' => 'Mortgage Payable', 'type' => 'liability', 'category' => 'Long-term Liability'],
            
            // Equity (3000s)
            ['code' => '3000', 'name' => 'Retained Earnings', 'type' => 'equity', 'category' => 'Equity'],
            
            // Revenue (4000s)
            ['code' => '4100', 'name' => 'Property Sales / Income', 'type' => 'revenue', 'category' => 'Operating Income'],
            ['code' => '4200', 'name' => 'Other Income', 'type' => 'revenue', 'category' => 'Other Income'],
            ['code' => '4300', 'name' => 'Interest Income', 'type' => 'revenue', 'category' => 'Other Income'],
            
            // Operating Expenses (5000s)
            ['code' => '5100', 'name' => 'Property Management Fees', 'type' => 'expense', 'category' => 'Operating Expense'],
            ['code' => '5110', 'name' => 'Repairs and Maintenance', 'type' => 'expense', 'category' => 'Operating Expense'],
            ['code' => '5120', 'name' => 'Property Taxes', 'type' => 'expense', 'category' => 'Operating Expense'],
            ['code' => '5130', 'name' => 'Insurance Expense', 'type' => 'expense', 'category' => 'Operating Expense'],
            ['code' => '5140', 'name' => 'Utilities (Elec/Water/Gas)', 'type' => 'expense', 'category' => 'Operating Expense'],
            
            // Administrative & Selling (6000s)
            ['code' => '6100', 'name' => 'Marketing and Advertising', 'type' => 'expense', 'category' => 'Admin Expense'],
            ['code' => '6110', 'name' => 'Legal and Professional Fees', 'type' => 'expense', 'category' => 'Admin Expense'],
            ['code' => '6120', 'name' => 'Sales Commissions', 'type' => 'expense', 'category' => 'Admin Expense'],
            ['code' => '6130', 'name' => 'Office Supplies & Expenses', 'type' => 'expense', 'category' => 'Admin Expense'],
            
            // Financial & Other (7000s)
            ['code' => '7100', 'name' => 'Interest Expense', 'type' => 'expense', 'category' => 'Financial Expense'],
            ['code' => '7110', 'name' => 'Depreciation and Amortization', 'type' => 'expense', 'category' => 'Other Expense'],
            ['code' => '7120', 'name' => 'Bank Service Charges', 'type' => 'expense', 'category' => 'Financial Expense'],
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
