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
     * Records a reservation fee receipt.
     * Debit: Cash/Bank (Asset)
     * Credit: Reservation Fees Payable (Liability)
     */
    public function recordReservationFeeReceipt($payment)
    {
        return DB::transaction(function () use ($payment) {
            $companyId = $payment->company_id;

            // 1. Get Accounts
            $cashAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '1010')->first();
            $liabilityAccount = ChartOfAccount::where('company_id', $companyId)->where('code', '2200')->first();

            if (!$cashAccount || !$liabilityAccount) {
                throw new \Exception("Accounting accounts not found for company {$companyId}. Please ensure codes 1010 and 2200 exist in the Chart of Accounts.");
            }

            // 2. Create Journal Entry Header
            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'transaction_date' => $payment->payment_date,
                'reference_no' => $payment->reference_no,
                'referenceable_type' => get_class($payment),
                'referenceable_id' => $payment->id,
                'description' => "Reservation Fee Receipt from " . ($payment->customer->full_name ?? 'Customer'),
            ]);

            // 3. Create Journal Entry Lines
            // Debit Cash
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cashAccount->id,
                'debit' => $payment->amount,
                'credit' => 0,
                'memo' => 'Receipt of reservation fee',
            ]);

            // Credit Liability
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $liabilityAccount->id,
                'debit' => 0,
                'credit' => $payment->amount,
                'memo' => 'Customer deposit for reservation',
            ]);

            // 4. Update Payment with Journal Entry ID
            $payment->update(['journal_entry_id' => $journalEntry->id]);

            return $journalEntry;
        });
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
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Applying reservation deposit to revenue',
            ]);

            // Credit Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $revenueAccount->id,
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
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Refunded deposit to customer',
            ]);

            // Credit Cash
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $cashAccount->id,
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
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Forfeited deposit due to cancellation',
            ]);

            // Credit Other Income
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $otherIncomeAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Forfeited reservation fee income',
            ]);

            return $journalEntry;
        });
    }
}
