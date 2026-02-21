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
        
        $query = Payment::with(['customer', 'reservation.unit', 'journalEntry'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($sub) use ($search) {
                      $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->latest('payment_date')->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Accounting/Payments/Index', [
            'payments' => $payments,
            'customers' => Customer::select('id', 'first_name', 'last_name')->get(),
            'reservations' => Reservation::where('status', 'Active')->with(['customer', 'unit'])->get(),
            'payment_methods' => ['Cash', 'Check', 'Bank Transfer', 'GCash/Maya', 'Other'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_no' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);

        DB::transaction(function () use ($validated, $companyId) {
            $payment = Payment::create([
                'company_id' => $companyId,
                'customer_id' => $validated['customer_id'],
                'reservation_id' => $validated['reservation_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'],
                'notes' => $validated['notes'],
            ]);

            // Auto-generate Journal Entry
            $this->accountingService->recordReservationFeeReceipt($payment);
        });

        return redirect()->back()->with('success', 'Payment recorded and ledger updated.');
    }
}
