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
                'debit' => $payment->amount,
                'credit' => 0,
                'memo' => "Receipt of {$type}",
            ]);

            // Create Credit Lines
            foreach ($creditLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $line['account_id'],
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

    /**
     * Updates associated accounting entries when a reservation fee is modified.
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
}
