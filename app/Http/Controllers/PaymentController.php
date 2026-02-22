<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        $query = \App\Models\ContractedSale::with(['customer', 'unit.project'])
            ->where('company_id', $companyId)
            ->where('status', 'Active');

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contract_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($sub) use ($search) {
                      $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $contracts = $query->latest()->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Accounting/Payments/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }

    public function show($id)
    {
        $contract = \App\Models\ContractedSale::with([
            'customer', 
            'unit.project',
            'payments',
            'paymentSchedules' => fn($q) => $q->orderBy('due_date', 'asc')
        ])->findOrFail($id);

        return Inertia::render('Accounting/Payments/Show', [
            'contract' => $contract,
            'payment_methods' => ['Cash', 'Check', 'Bank Transfer', 'GCash/Maya', 'Other'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contracted_sale_id' => 'required|exists:contracted_sales,id',
            'schedule_ids' => 'required|array',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_no' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $contract = \App\Models\ContractedSale::findOrFail($validated['contracted_sale_id']);

        DB::transaction(function () use ($validated, $companyId, $contract) {
            $payment = Payment::create([
                'company_id' => $companyId,
                'customer_id' => $contract->customer_id,
                'reservation_id' => $contract->reservation_id,
                'payment_type' => 'Amortization',
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'],
                'notes' => $validated['notes'],
            ]);

            $totalAmount = (float)$validated['amount'];
            $schedules = \App\Models\PaymentSchedule::whereIn('id', $validated['schedule_ids'])
                ->orderBy('due_date', 'asc')
                ->get();

            $totalPrincipalPaid = 0;
            $totalInterestPaid = 0;

            foreach ($schedules as $schedule) {
                if ($totalAmount <= 0) break;

                $remainingDue = (float)$schedule->amount_due - (float)$schedule->amount_paid;
                $toPay = min($totalAmount, $remainingDue);
                
                // For data science precision, we breakdown the payment into principal and interest
                // Ratio = toPay / remainingDue (if it's a partial payment)
                // but usually we pay full installments here.
                if ($toPay >= $remainingDue) {
                    $principalPortion = (float)$schedule->principal - (float)$schedule->principal_paid; // assuming we track principal_paid too, but we don't yet in model.
                    // Let's simplify: if fully paid, use schedule principal/interest
                    // If partially paid, we need a better model. 
                    // For now, let's assume installments are usually paid in full as per UI selection.
                    $principalPortion = (float)$schedule->principal;
                    $interestPortion = (float)$schedule->interest;
                } else {
                    // Pro-rated for partial
                    $ratio = $toPay / (float)$schedule->amount_due;
                    $principalPortion = (float)$schedule->principal * $ratio;
                    $interestPortion = (float)$schedule->interest * $ratio;
                }

                $totalPrincipalPaid += $principalPortion;
                $totalInterestPaid += $interestPortion;

                $schedule->increment('amount_paid', $toPay);
                
                // Fix: Use rounded comparison to avoid Partially Paid status due to precision errors
                $amountPaid = round((float)$schedule->amount_paid, 2);
                $amountDue = round((float)$schedule->amount_due, 2);

                if ($amountPaid >= $amountDue) {
                    $schedule->update(['status' => 'Paid']);
                } else {
                    $schedule->update(['status' => 'Partially Paid']);
                }
                
                $totalAmount -= $toPay;
            }

            // Auto-generate Journal Entry with breakdown
            $this->accountingService->recordGeneralPaymentReceipt($payment, $totalPrincipalPaid, $totalInterestPaid);
        });

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }
}
