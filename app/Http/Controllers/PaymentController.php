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
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        $contract = \App\Models\ContractedSale::with([
            'customer', 
            'unit.project',
            'payments',
            'paymentSchedules' => fn($q) => $q->orderBy('due_date', 'asc')
        ])->findOrFail($id);

        $banks = \App\Models\Bank::where('company_id', $companyId)->where('is_active', true)->get();

        return Inertia::render('Accounting/Payments/Show', [
            'contract' => $contract,
            'banks' => $banks,
            'payment_methods' => ['Cash', 'Check', 'Bank Transfer', 'PDC', 'Bulk PDC', 'GCash/Maya', 'Other'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contracted_sale_id' => 'required|exists:contracted_sales,id',
            'schedule_ids' => 'required|array',
            'amount' => 'required_unless:payment_method,Bulk PDC|numeric|min:0.01|nullable',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_no' => 'required_unless:payment_method,Bulk PDC|string|nullable',
            'notes' => 'nullable|string',
            // PDC Fields (Optional unless method is Check/PDC/Bulk PDC)
            'bank_id' => 'required_if:payment_method,Bulk PDC|nullable|exists:banks,id',
            'check_no' => 'nullable|string',
            'check_date' => 'nullable|date',
            'starting_check_no' => 'required_if:payment_method,Bulk PDC|nullable|string',
        ]);

        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $contract = \App\Models\ContractedSale::findOrFail($validated['contracted_sale_id']);

        DB::transaction(function () use ($validated, $companyId, $contract) {
            $schedules = \App\Models\PaymentSchedule::whereIn('id', $validated['schedule_ids'])
                ->orderBy('due_date', 'asc')
                ->get();

            if ($validated['payment_method'] === 'Bulk PDC') {
                $checkNoInt = (int) preg_replace('/[^0-9]/', '', $validated['starting_check_no']);
                $prefix = preg_replace('/[0-9]/', '', $validated['starting_check_no']);
                $bank = \App\Models\Bank::find($validated['bank_id']);

                foreach ($schedules as $schedule) {
                    $amountDue = $schedule->amount_due - $schedule->amount_paid;
                    if ($amountDue <= 0) continue;

                    $currentCheckNo = $prefix . str_pad($checkNoInt, strlen(preg_replace('/[^0-9]/', '', $validated['starting_check_no'])), '0', STR_PAD_LEFT);

                    $payment = Payment::create([
                        'company_id' => $companyId,
                        'customer_id' => $contract->customer_id,
                        'reservation_id' => $contract->reservation_id,
                        'payment_type' => 'Amortization',
                        'amount' => $amountDue,
                        'payment_date' => $schedule->due_date, // Payment date aligns with maturity/due date
                        'payment_method' => 'PDC',
                        'reference_no' => 'CHK-' . $currentCheckNo,
                        'notes' => $validated['notes'] ?? 'Bulk PDC Generation',
                    ]);

                    \App\Models\PdcVault::create([
                        'company_id' => $companyId,
                        'type' => 'Inward',
                        'payment_id' => $payment->id,
                        'customer_id' => $contract->customer_id,
                        'bank_id' => $validated['bank_id'],
                        'bank_name' => $bank?->name ?? 'Unknown',
                        'check_no' => $currentCheckNo,
                        'check_date' => $schedule->due_date, // Maturity follows due date
                        'amount' => $amountDue,
                        'status' => 'Pending',
                    ]);

                    $schedule->update([
                        'amount_paid' => $schedule->amount_due,
                        'status' => 'Paid'
                    ]);

                    $this->accountingService->recordGeneralPaymentReceipt($payment, $schedule->principal, $schedule->interest);

                    $checkNoInt++;
                }
            } else {
                // Standard Single Payment Logic
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

                if (in_array($validated['payment_method'], ['Check', 'PDC'])) {
                    $bank = \App\Models\Bank::find($validated['bank_id']);
                    \App\Models\PdcVault::create([
                        'company_id' => $companyId,
                        'type' => 'Inward',
                        'payment_id' => $payment->id,
                        'customer_id' => $contract->customer_id,
                        'bank_id' => $validated['bank_id'],
                        'bank_name' => $bank?->name ?? 'Unknown',
                        'check_no' => $validated['check_no'] ?? $validated['reference_no'],
                        'check_date' => $validated['check_date'] ?? $validated['payment_date'],
                        'amount' => $validated['amount'],
                        'status' => 'Pending',
                    ]);
                }

                $totalAmount = (float)$validated['amount'];
                
                $totalPrincipalPaid = 0;
                $totalInterestPaid = 0;

                foreach ($schedules as $schedule) {
                    if ($totalAmount <= 0) break;

                    $remainingDue = (float)$schedule->amount_due - (float)$schedule->amount_paid;
                    $toPay = min($totalAmount, $remainingDue);
                    
                    if ($toPay >= $remainingDue) {
                        $principalPortion = (float)$schedule->principal;
                        $interestPortion = (float)$schedule->interest;
                    } else {
                        $ratio = $toPay / (float)$schedule->amount_due;
                        $principalPortion = (float)$schedule->principal * $ratio;
                        $interestPortion = (float)$schedule->interest * $ratio;
                    }

                    $totalPrincipalPaid += $principalPortion;
                    $totalInterestPaid += $interestPortion;

                    $schedule->increment('amount_paid', $toPay);
                    
                    $amountPaid = round((float)$schedule->amount_paid, 2);
                    $amountDue = round((float)$schedule->amount_due, 2);

                    if ($amountPaid >= $amountDue) {
                        $schedule->update(['status' => 'Paid']);
                    } else {
                        $schedule->update(['status' => 'Partially Paid']);
                    }
                    
                    $totalAmount -= $toPay;
                }

                $this->accountingService->recordGeneralPaymentReceipt($payment, $totalPrincipalPaid, $totalInterestPaid);
            }
        });

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }
}
