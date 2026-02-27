<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\DisbursementItem;
use App\Models\Bill;
use App\Models\PdcVault;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DisbursementService
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Creates a new Disbursement / Payment Voucher (Draft).
     */
    public function createVoucher(array $data)
    {
        return DB::transaction(function () use ($data) {
            $disbursement = Disbursement::create([
                'company_id' => $data['company_id'],
                'vendor_id' => $data['vendor_id'],
                'voucher_no' => $data['voucher_no'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'bank_account_id' => $data['bank_account_id'],
                'total_amount' => $data['total_amount'],
                'notes' => $data['notes'] ?? null,
                'status' => 'Draft',
                'prepared_by' => Auth::id(),
            ]);

            // Add Bill Items (Linking Voucher to specific Bills)
            foreach ($data['bills'] as $billData) {
                DisbursementItem::create([
                    'disbursement_id' => $disbursement->id,
                    'bill_id' => $billData['bill_id'],
                    'amount' => $billData['amount'],
                ]);
            }

            // If it's a PDC, create a vault record
            if ($data['payment_method'] === 'PDC' && isset($data['pdc_details'])) {
                PdcVault::create(array_merge($data['pdc_details'], [
                    'company_id' => $disbursement->company_id,
                    'type' => 'Outward',
                    'disbursement_id' => $disbursement->id,
                    'vendor_id' => $disbursement->vendor_id,
                    'amount' => $disbursement->total_amount,
                    'status' => 'Pending',
                ]));
            }

            return $disbursement;
        });
    }

    /**
     * Approves the Voucher, Posts to Accounting, and Updates Bill statuses.
     */
    public function approveVoucher(Disbursement $disbursement)
    {
        if ($disbursement->status !== 'Draft') {
            throw new \Exception("Only draft vouchers can be approved.");
        }

        return DB::transaction(function () use ($disbursement) {
            // 1. Post to General Ledger via AccountingService
            $journalEntry = $this->accountingService->recordDisbursement($disbursement);

            // 2. Update Bill Statuses based on payment application
            foreach ($disbursement->items as $item) {
                $bill = $item->bill;
                
                // Calculate paid amount so far (including this voucher)
                // We might need a separate 'paid_amount' column in 'bills' for easier tracking, 
                // or we calculate from DisbursementItems.
                $totalPaidOnBill = DisbursementItem::where('bill_id', $bill->id)
                    ->whereHas('disbursement', function($q) {
                        $q->whereIn('status', ['Approved', 'Paid']);
                    })
                    ->sum('amount');
                
                // We should include the current item if we haven't updated the disbursement status yet.
                // Since we're in the transaction, it's safer to just calculate.
                // Let's assume the status will be changed to 'Approved' right after this loop.
                $totalPaidOnBill += $item->amount;

                if ($totalPaidOnBill >= $bill->total_amount) {
                    $bill->update(['status' => 'Paid']);
                } else {
                    $bill->update(['status' => 'Partial']);
                }
            }

            // 3. Update Voucher Status
            $disbursement->update([
                'status' => 'Approved',
                'approved_by' => Auth::id(),
            ]);

            return $disbursement;
        });
    }

    /**
     * Clears a PDC in the vault (when the bank clears it).
     */
    public function clearPdc(PdcVault $pdc, $dateCleared)
    {
        return DB::transaction(function () use ($pdc, $dateCleared) {
            $pdc->update([
                'status' => 'Cleared',
                'cleared_date' => $dateCleared,
            ]);

            // Note: If we had a separate "Checks Issued" GL account (Liability), 
            // we would move from Checks Issued (Debit) to Cash in Bank (Credit).
            // For now, if the PV already credited bank, no additional entry is needed 
            // unless we're using a two-step clearing process.
            
            return $pdc;
        });
    }

    /**
     * Get Liquidity Heatmap Data (Future inflows vs outflows)
     */
    public function getLiquidityForecast($companyId)
    {
        return PdcVault::where('company_id', $companyId)
            ->pending()
            ->selectRaw("
                check_date, 
                sum(case when type = 'Inward' then amount else 0 end) as total_inflow,
                sum(case when type = 'Outward' then amount else 0 end) as total_outflow
            ")
            ->groupBy('check_date')
            ->orderBy('check_date')
            ->get();
    }
}
