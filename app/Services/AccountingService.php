<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    /**
     * Records a generic payment receipt.
     * Handles Reservation, Downpayment, and Amortization.
     */
    public function recordGeneralPaymentReceipt($payment, $principal = 0, $interest = 0)
    {
        return DB::transaction(function () use ($payment, $principal, $interest) {
            $companyId = $payment->company_id;
            $type = $payment->payment_type;
            
            // Get Project ID from unit (if available)
            $projectId = null;
            if ($payment->contractedSale && $payment->contractedSale->unit) {
                $projectId = $payment->contractedSale->unit->project_id;
            } elseif ($payment->reservation && $payment->reservation->unit) {
                $projectId = $payment->reservation->unit->project_id;
            }

            // 1. Get Base Accounts
            $cashAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '1010')->first();
            
            if (!$cashAccount) {
                throw new \Exception("Cash account (1010) not found.");
            }

            // 2. Determine Credit Accounts based on Type
            $creditLines = [];
            $description = "{$type} Receipt from " . ($payment->customer->full_name ?? 'Customer');

            if ($type === 'Amortization') {
                $revenueAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '4100')->first();
                $interestAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '4300')->first();

                if ($principal > 0) {
                    $creditLines[] = [
                        'account_id' => $revenueAccount->id,
                        'amount' => $principal,
                        'memo' => 'Principal portion of amortization'
                    ];
                }
                if ($interest > 0) {
                    $creditLines[] = [
                        'account_id' => $interestAccount->id,
                        'amount' => $interest,
                        'memo' => 'Interest portion of amortization'
                    ];
                }
            } else {
                // Reservation or Downpayment goes to Liability
                $liabilityAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2200')->first();
                $creditLines[] = [
                    'account_id' => $liabilityAccount->id,
                    'amount' => $payment->amount,
                    'memo' => "Customer deposit for {$type}"
                ];
            }

            // 3. Create Journal Entry Header
            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $payment->payment_date,
                'reference_no' => $payment->reference_no,
                'referenceable_type' => get_class($payment),
                'referenceable_id' => $payment->id,
                'description' => $description,
            ]);

            // 4. Create Journal Entry Lines
            // Debit Cash (Total Amount)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cashAccount->id,
                'project_id' => $projectId,
                'debit' => $payment->amount,
                'credit' => 0,
                'memo' => "Receipt of {$type}",
            ]);

            // Create Credit Lines
            foreach ($creditLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $line['account_id'],
                    'project_id' => $projectId,
                    'debit' => 0,
                    'credit' => $line['amount'],
                    'memo' => $line['memo'],
                ]);
            }

            // 5. Update Payment with Journal Entry ID
            $payment->update(['journal_entry_id' => $journalEntry->id]);

            return $journalEntry;
        });
    }

    /**
     * Records a reservation fee receipt (Legacy wrapper).
     */
    public function recordReservationFeeReceipt($payment)
    {
        return $this->recordGeneralPaymentReceipt($payment);
    }

    /**
     * Records revenue recognition from reservation fee.
     * Debit: Reservation Fees Payable (Liability)
     * Credit: Property Sales/Revenue (Income)
     */
    public function recognizeRevenueFromReservation($reservation, $amount, $referenceNo = null)
    {
        return DB::transaction(function () use ($reservation, $amount, $referenceNo) {
            // Safe determination of company_id
            $companyId = $reservation->unit->project->company_id 
                ?? Auth::user()->company_id 
                ?? (\App\Models\Company::first()->id ?? null);

            $liabilityAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2200')->first();
            $revenueAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '4100')->first();

            if (!$liabilityAccount || !$revenueAccount) {
                return null;
            }

            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => now(),
                'reference_no' => $referenceNo,
                'referenceable_type' => get_class($reservation),
                'referenceable_id' => $reservation->id,
                'description' => "Revenue Recognition for Reservation #" . $reservation->id,
            ]);

            // Debit Liability
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $liabilityAccount->id,
                'project_id' => $reservation->unit?->project_id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Applying reservation deposit to revenue',
            ]);

            // Credit Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $revenueAccount->id,
                'project_id' => $reservation->unit?->project_id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Property sales revenue recognition',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Records a refund of reservation fee.
     * Debit: Reservation Fees Payable (Liability)
     * Credit: Cash/Bank (Asset)
     */
    public function recordRefund($reservation, $amount, $referenceNo = null)
    {
        return DB::transaction(function () use ($reservation, $amount, $referenceNo) {
            $companyId = $reservation->unit->project->company_id 
                ?? Auth::user()->company_id 
                ?? (\App\Models\Company::first()->id ?? null);

            $cashAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '1010')->first();
            $liabilityAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2200')->first();

            if (!$cashAccount || !$liabilityAccount) {
                return null;
            }

            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => now(),
                'reference_no' => $referenceNo,
                'referenceable_type' => get_class($reservation),
                'referenceable_id' => $reservation->id,
                'description' => "Refund of Reservation Fee for #" . $reservation->id,
            ]);

            // Debit Liability
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $liabilityAccount->id,
                'project_id' => $reservation->unit?->project_id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Refunded deposit to customer',
            ]);

            // Credit Cash
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cashAccount->id,
                'project_id' => $reservation->unit?->project_id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Cash/Check refund for reservation',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Records a forfeiture of reservation fee (move to other income).
     * Debit: Reservation Fees Payable (Liability)
     * Credit: Other Income (Revenue)
     */
    public function recordForfeiture($reservation, $amount, $referenceNo = null)
    {
        return DB::transaction(function () use ($reservation, $amount, $referenceNo) {
            $companyId = $reservation->unit->project->company_id 
                ?? Auth::user()->company_id 
                ?? (\App\Models\Company::first()->id ?? null);

            $liabilityAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2200')->first();
            $otherIncomeAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '4200')->first();

            if (!$liabilityAccount || !$otherIncomeAccount) {
                return null;
            }

            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => now(),
                'reference_no' => $referenceNo,
                'referenceable_type' => get_class($reservation),
                'referenceable_id' => $reservation->id,
                'description' => "Forfeiture of Reservation Fee for #" . $reservation->id,
            ]);

            // Debit Liability
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $liabilityAccount->id,
                'project_id' => $reservation->unit?->project_id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Forfeited deposit due to cancellation',
            ]);

            // Credit Other Income
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $otherIncomeAccount->id,
                'project_id' => $reservation->unit?->project_id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Forfeited reservation fee income',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Records an expense payment.
     * Debit: Expense Account (Repairs, Taxes, etc.)
     * Credit: Cash/Bank Account
     */
    public function recordExpense($companyId, $expenseAccountCode, $amount, $date, $referenceNo, $description, $memo = null)
    {
        return DB::transaction(function () use ($companyId, $expenseAccountCode, $amount, $date, $referenceNo, $description, $memo) {
            $cashAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '1010')->first();
            $expenseAccount = ChartOfAccount::where('company_id', $companyId)->where('code', $expenseAccountCode)->first();

            if (!$cashAccount || !$expenseAccount) {
                throw new \Exception("Cash or Expense account not found.");
            }

            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $date,
                'reference_no' => $referenceNo,
                'description' => $description,
            ]);

            // Debit Expense
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $expenseAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => $memo ?? $description,
            ]);

            // Credit Cash
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Payment for expense',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Records a capitalized cost (e.g., Land Acquisition, Construction).
     * Debit: Asset Account (Land, Building, CIP)
     * Credit: Cash/Bank or Mortgage Payable
     */
    public function recordCapitalizedCost($companyId, $assetAccountCode, $creditAccountCode, $amount, $date, $referenceNo, $description)
    {
        return DB::transaction(function () use ($companyId, $assetAccountCode, $creditAccountCode, $amount, $date, $referenceNo, $description) {
            $assetAccount = ChartOfAccount::where('company_id', $companyId)->where('code', $assetAccountCode)->first();
            $creditAccount = ChartOfAccount::where('company_id', $companyId)->where('code', $creditAccountCode)->first();

            if (!$assetAccount || !$creditAccount) {
                throw new \Exception("Asset or Credit account not found.");
            }

            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $date,
                'reference_no' => $referenceNo,
                'description' => $description,
            ]);

            // Debit Asset (Capitalize)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $assetAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Capitalized cost for development/acquisition',
            ]);

            // Credit Cash or Liability
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Payment/Obligation for asset acquisition',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Records Cost of Goods Sold (COGS) for a sold property.
     * Debit: COGS (Expense)
     * Credit: Inventory (Asset)
     */
    public function recordCOGS($companyId, $amount, $date, $referenceNo, $description)
    {
        return DB::transaction(function () use ($companyId, $amount, $date, $referenceNo, $description) {
            $cogsAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '5000')->first(); // General or specific COGS
            $inventoryAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '1510')->first(); // Buildings/Inventory

            if (!$cogsAccount || !$inventoryAccount) {
                // If 5000 doesn't exist, we might need to check if 5100 series exists
                $cogsAccount = ChartOfAccount::where('company_id', $companyId)->where('type', 'expense')->where('name', 'like', '%COGS%')->first();
            }

            if (!$cogsAccount || !$inventoryAccount) {
                throw new \Exception("COGS or Inventory account not found.");
            }

            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $date,
                'reference_no' => $referenceNo,
                'description' => $description,
            ]);

            // Debit COGS
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cogsAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Recognition of cost of goods sold',
            ]);

            // Credit Inventory
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $inventoryAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Reduction of inventory for sale',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Records a bill from a vendor (Accounts Payable).
     * Debit: Various Expense/Asset Accounts
     * Credit: Accounts Payable (Liability)
     */
    public function recordBill($bill)
    {
        return DB::transaction(function () use ($bill) {
            $companyId = $bill->company_id;
            
            // 1. Get Accounts Payable account (2300)
            $apAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2300')->first();
            
            if (!$apAccount) {
                throw new \Exception("Accounts Payable account (2300) not found.");
            }

            // 2. Create Journal Entry Header
            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $bill->bill_date,
                'reference_no' => $bill->bill_number,
                'referenceable_type' => get_class($bill),
                'referenceable_id' => $bill->id,
                'description' => "Bill from " . ($bill->vendor->name ?? 'Vendor'),
            ]);

            // 3. Create Debit Lines for each Bill Item
            foreach ($bill->items as $item) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $item->chart_of_account_id,
                    'project_id' => $item->project_id ?? $bill->project_id,
                    'debit' => $item->amount,
                    'credit' => 0,
                    'memo' => $item->description ?? $bill->notes,
                ]);
            }

            // 4. Create Credit Line for Accounts Payable (Total Amount)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $apAccount->id,
                'project_id' => $bill->project_id,
                'debit' => 0,
                'credit' => $bill->total_amount,
                'memo' => "Liability recorded for bill #" . $bill->bill_number,
            ]);

            // 5. Link Journal Entry to Bill
            $bill->update(['journal_entry_id' => $journalEntry->id]);

            return $journalEntry;
        });
    }

    /**
     * Reverses a previously posted bill.
     * Creates a reversal entry: 
     * Debit: Accounts Payable
     * Credit: Original Expense/Asset Accounts
     */
    public function reverseBill($bill)
    {
        return DB::transaction(function () use ($bill) {
            if (!$bill->journal_entry_id) {
                return null;
            }

            $originalEntry = JournalEntry::with('lines')->find($bill->journal_entry_id);
            if (!$originalEntry) {
                return null;
            }

            // Create Reversal Header
            $reversalEntry = JournalEntry::create([
                'company_id' => $bill->company_id,
                'user_id' => Auth::id(),
                'transaction_date' => now(),
                'reference_no' => "REV-" . $bill->bill_number,
                'referenceable_type' => get_class($bill),
                'referenceable_id' => $bill->id,
                'description' => "Reversal of " . $bill->type . " #" . $bill->bill_number,
            ]);

            // Flip the Lines: Original Debits become Credits, Original Credits become Debits
            foreach ($originalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'chart_of_account_id' => $line->chart_of_account_id,
                    'project_id' => $line->project_id,
                    'debit' => $line->credit, // Flip
                    'credit' => $line->debit, // Flip
                    'memo' => "Reversal: " . $line->memo,
                ]);
            }

            return $reversalEntry;
        });
    }

    /**
     * Records a debit memo (Vendor Credit).
     * Debit: Accounts Payable (Liability reduction)
     * Credit: Various Expense/Asset Accounts (Expense reduction)
     */
    public function recordDebitMemo($bill)
    {
        return DB::transaction(function () use ($bill) {
            $companyId = $bill->company_id;
            
            // 1. Get Accounts Payable account (2300)
            $apAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2300')->first();
            
            if (!$apAccount) {
                throw new \Exception("Accounts Payable account (2300) not found.");
            }

            // 2. Create Journal Entry Header
            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $bill->bill_date,
                'reference_no' => $bill->bill_number,
                'referenceable_type' => get_class($bill),
                'referenceable_id' => $bill->id,
                'description' => "Debit Memo from " . ($bill->vendor->name ?? 'Vendor'),
            ]);

            // 3. Create Debit Line for Accounts Payable (Total Amount)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $apAccount->id,
                'project_id' => $bill->project_id,
                'debit' => $bill->total_amount, // Positive debit reduces liability
                'credit' => 0,
                'memo' => "Liability reduced via Debit Memo #" . $bill->bill_number,
            ]);

            // 4. Create Credit Lines for each Item (Reducing Expense/Asset)
            foreach ($bill->items as $item) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $item->chart_of_account_id,
                    'project_id' => $item->project_id ?? $bill->project_id,
                    'debit' => 0,
                    'credit' => $item->amount,
                    'memo' => $item->description ?? $bill->notes,
                ]);
            }

            // 5. Link Journal Entry to Bill
            $bill->update(['journal_entry_id' => $journalEntry->id]);

            return $journalEntry;
        });
    }

    /**
     * Records a disbursement (Payment Voucher).
     * Debit: Accounts Payable (Liability reduction)
     * Credit: Cash/Bank Account (Asset reduction)
     */
    public function recordDisbursement($disbursement)
    {
        return DB::transaction(function () use ($disbursement) {
            $companyId = $disbursement->company_id;
            
            // 1. Get Accounts Payable account (2300)
            $apAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2300')->first();
            
            // 2. Get Cash/Bank Account (as specified in the voucher)
            $cashAccount = ChartOfAccount::find($disbursement->bank_account_id);
            
            if (!$apAccount || !$cashAccount) {
                throw new \Exception("Accounts Payable or Cash account not found.");
            }

            // 3. Create Journal Entry Header
            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $disbursement->payment_date,
                'reference_no' => $disbursement->voucher_no,
                'referenceable_type' => get_class($disbursement),
                'referenceable_id' => $disbursement->id,
                'description' => "Disbursement to " . ($disbursement->vendor->name ?? 'Vendor'),
            ]);

            // 4. Create Debit Line for Accounts Payable (Total Amount)
            // Note: In a more detailed system, we might split this per bill/project, 
            // but for simple AP, we debit the total AP control account.
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $apAccount->id,
                'debit' => $disbursement->total_amount,
                'credit' => 0,
                'memo' => "Payment for bills: " . $disbursement->items->map(fn($i) => "#" . $i->bill->bill_number)->implode(', '),
            ]);

            // 5. Create Credit Line for Cash/Bank
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $disbursement->total_amount,
                'memo' => "Disbursement via " . $disbursement->payment_method,
            ]);

            // 6. Link Journal Entry to Disbursement
            $disbursement->update(['journal_entry_id' => $journalEntry->id]);

            return $journalEntry;
        });
    }

    /**
     * Records associated accounting entries when a reservation fee is modified.
     */
    public function updateReservationAccounting($reservation)
    {
        return DB::transaction(function () use ($reservation) {
            $amount = $reservation->fee;

            // 1. Update the Payment record first
            $payment = $reservation->payments()->first();
            if ($payment) {
                $payment->update(['amount' => $amount]);
                
                // 2. Update the initial Receipt Journal Entry
                if ($payment->journal_entry_id) {
                    $journalEntry = JournalEntry::find($payment->journal_entry_id);
                    if ($journalEntry) {
                        // Update transaction date just in case it was changed
                        $journalEntry->update(['transaction_date' => $reservation->reservation_date]);
                        
                        foreach ($journalEntry->lines as $line) {
                            if ($line->debit > 0) {
                                $line->update(['debit' => $amount]);
                            } else {
                                $line->update(['credit' => $amount]);
                            }
                        }
                    }
                }
            }

            // 3. Update the Revenue Recognition Entry if it exists (Status: Contracted)
            $revenueEntry = JournalEntry::where('referenceable_type', get_class($reservation))
                ->where('referenceable_id', $reservation->id)
                ->where('description', 'like', 'Revenue Recognition%')
                ->first();
            
            if ($revenueEntry) {
                foreach ($revenueEntry->lines as $line) {
                    if ($line->debit > 0) {
                        $line->update(['debit' => $amount]);
                    } else {
                        $line->update(['credit' => $amount]);
                    }
                }
            }

            return true;
        });
    }

    /**
     * Converts a number to words for accounting documents (PDF Vouchers, Checks).
     * Provides a fallback if the 'intl' extension is missing.
     */
    public function formatAmountInWords($amount)
    {
        // 1. Clean the amount: ensure only 2 decimal places for money (cents)
        // This prevents 17000.0000 from becoming "seventeen thousand point zero zero zero zero"
        $amount = round((float)$amount, 2);

        // 2. Separate whole number and fraction
        $wholeNumber = floor($amount);
        $fraction = round(($amount - $wholeNumber) * 100);

        // 3. Convert whole number to words
        $words = '';
        try {
            if (extension_loaded('intl')) {
                $words = \Illuminate\Support\Number::spell($wholeNumber);
            } else {
                $words = $this->numberToWordsFallback($wholeNumber);
            }
        } catch (\Exception $e) {
            $words = $this->numberToWordsFallback($wholeNumber);
        }

        // 4. Handle Cents/Fraction (Standard Check Format: "AND 00/100")
        if ($fraction > 0) {
            $words .= " and " . str_pad($fraction, 2, '0', STR_PAD_LEFT) . "/100";
        } else {
            $words .= " and 00/100";
        }

        return $words;
    }

    /**
     * Manual implementation of number to words (English).
     * Only handles whole numbers now as decimals are handled by formatAmountInWords.
     */
    private function numberToWordsFallback($number)
    {
        $number = (int)$number; // Ensure whole number for the recursion
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'fourty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'numberToWordsFallback only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . $this->numberToWordsFallback(abs($number));
        }

        $string = null;

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[(int) $hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->numberToWordsFallback($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->numberToWordsFallback($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->numberToWordsFallback($remainder);
                }
                break;
        }

        return $string;
    }
}
