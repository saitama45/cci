<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Vendor;
use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\PdcVault;
use App\Helpers\LogActivity;
use App\Services\DisbursementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DisbursementController extends Controller
{
    protected $disbursementService;
    protected $accountingService;

    public function __construct(DisbursementService $disbursementService, \App\Services\AccountingService $accountingService)
    {
        $this->disbursementService = $disbursementService;
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the Payment Vouchers.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $query = Disbursement::with(['vendor', 'bankAccount', 'preparedBy'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($v) use ($search) {
                      $v->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $disbursements = $query->latest('payment_date')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Accounting/Disbursements/Index', [
            'disbursements' => $disbursements,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new PV.
     */
    public function create()
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $vendors = Vendor::where('company_id', $companyId)->where('is_active', true)->where('verification_status', 'Verified')->get();
        $banks = \App\Models\Bank::where('company_id', $companyId)->where('is_active', true)->get();
        
        // Fetch bank/cash accounts (1000 series)
        $bankAccounts = ChartOfAccount::where('company_id', $companyId)
            ->whereIn('type', ['asset'])
            ->where('name', 'like', '%Cash%')
            ->get();

        return Inertia::render('Accounting/Disbursements/Create', [
            'vendors' => $vendors,
            'banks' => $banks,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Store the Voucher (Draft).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'voucher_no' => 'required|string|unique:disbursements,voucher_no',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:Cash,Check,Bank Transfer,PDC',
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'total_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'bills' => 'required|array|min:1',
            'bills.*.bill_id' => 'required|exists:bills,id',
            'bills.*.amount' => 'required|numeric|min:0.01',
            'pdc_details' => 'required_if:payment_method,PDC|array',
            'pdc_details.check_no' => 'required_if:payment_method,PDC',
            'pdc_details.check_date' => 'required_if:payment_method,PDC|date',
            'pdc_details.bank_name' => 'required_if:payment_method,PDC',
        ]);

        $validated['company_id'] = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $disbursement = $this->disbursementService->createVoucher($validated);

        return redirect()->route('accounting.disbursements.show', $disbursement->id)
            ->with('success', 'Payment Voucher created successfully.');
    }

    /**
     * Show the Voucher details.
     */
    public function show(Disbursement $disbursement)
    {
        $disbursement->load(['vendor', 'bankAccount', 'items.bill', 'pdcDetail', 'preparedBy', 'approvedBy', 'journalEntry.lines.chartOfAccount']);
        
        return Inertia::render('Accounting/Disbursements/Show', [
            'disbursement' => $disbursement,
        ]);
    }

    /**
     * Approve and Post the Voucher.
     */
    public function approve(Disbursement $disbursement)
    {
        try {
            $this->disbursementService->approveVoucher($disbursement);
            return redirect()->back()->with('success', 'Voucher approved and posted to general ledger.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print the Payment Voucher (PDF).
     */
    public function print(Disbursement $disbursement)
    {
        $disbursement->load(['company', 'vendor', 'bankAccount', 'items.bill', 'pdcDetail', 'preparedBy', 'approvedBy']);
        
        $amountInWords = $this->accountingService->formatAmountInWords($disbursement->total_amount);
        $amountInWords = strtoupper($amountInWords);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payment-voucher', [
            'disbursement' => $disbursement,
            'amountInWords' => $amountInWords,
        ]);

        return $pdf->stream("PV-{$disbursement->voucher_no}.pdf");
    }

    /**
     * Print the physical check layout (PDF).
     */
    public function printCheck(Disbursement $disbursement)
    {
        $disbursement->load(['vendor', 'pdcDetail.bank']);
        
        $amountInWords = $this->accountingService->formatAmountInWords($disbursement->total_amount);
        // Clean check format: "** SEVENTEEN THOUSAND AND 00/100 PESOS ONLY **"
        $amountInWords = "** " . strtoupper($amountInWords) . " PESOS ONLY **";

        $checkDate = $disbursement->payment_method === 'PDC' && $disbursement->pdcDetail 
            ? $disbursement->pdcDetail->check_date 
            : $disbursement->payment_date;

        // Fetch bank-specific config or use system default
        $bankConfig = $disbursement->pdcDetail?->bank?->cheque_config ?? [
            'paper_width' => 612, 
            'paper_height' => 252,
            'date_top' => 40,
            'date_right' => 50,
            'payee_top' => 85,
            'payee_left' => 100,
            'amount_top' => 85,
            'amount_right' => 50,
            'words_top' => 115,
            'words_left' => 50
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.check-template', [
            'disbursement' => $disbursement,
            'amountInWords' => $amountInWords,
            'checkDate' => $checkDate,
            'config' => $bankConfig,
        ])->setPaper([0, 0, $bankConfig['paper_width'], $bankConfig['paper_height']], 'portrait');

        return $pdf->stream("Check-{$disbursement->voucher_no}.pdf");
    }

    /**
     * PDC Vault & Intelligence Dashboard.
     */
    public function vault(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        // Default to Outward if no type is specified to match frontend default
        $type = $request->type ?? 'Outward';

        $query = PdcVault::with(['disbursement.vendor', 'payment.customer', 'customer', 'vendor', 'bank'])
            ->where('company_id', $companyId)
            ->where('type', $type);

        $pdcs = $query->orderBy('check_date')
            ->paginate(15)
            ->withQueryString();

        $forecast = $this->disbursementService->getLiquidityForecast($companyId);

        return Inertia::render('Accounting/Disbursements/Vault', [
            'pdcs' => $pdcs,
            'forecast' => $forecast,
            'filters' => $request->only(['type']),
        ]);
    }

    /**
     * Mark a PDC as Cleared.
     */
    public function markAsCleared(Request $request, PdcVault $pdc)
    {
        $validated = $request->validate([
            'cleared_date' => 'required|date',
        ]);

        $this->disbursementService->clearPdc($pdc, $validated['cleared_date']);

        return redirect()->back()->with('success', "Check #{$pdc->check_no} marked as Cleared.");
    }

    /**
     * Mark a PDC as Bounced.
     */
    public function markAsBounced(PdcVault $pdc)
    {
        $pdc->update(['status' => 'Bounced']);
        LogActivity::log('Treasury', 'Bounced', "Marked PDC Check #{$pdc->check_no} as Bounced", $pdc);
        return redirect()->back()->with('success', "Check #{$pdc->check_no} marked as Bounced.");
    }

    /**
     * Get unpaid bills for a specific vendor (API).
     */
    public function getVendorBills($vendorId)
    {
        $bills = Bill::where('vendor_id', $vendorId)
            ->whereIn('status', ['Approved', 'Partial'])
            ->get(['id', 'bill_number', 'bill_date', 'total_amount', 'status']);

        // Enrich with already paid amount for each bill
        $bills->map(function($bill) {
            $paid = \App\Models\DisbursementItem::where('bill_id', $bill->id)
                ->whereHas('disbursement', function($q) {
                    $q->whereIn('status', ['Approved', 'Paid']);
                })
                ->sum('amount');
            $bill->balance = $bill->total_amount - $paid;
            return $bill;
        });

        return response()->json($bills);
    }
}
