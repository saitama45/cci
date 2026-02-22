<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\Broker;
use App\Models\Payment;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['customer', 'unit.project', 'unit.priceList', 'broker', 'payments.journalEntry']);

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhereHas('unit', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->status) {
            if ($request->status === 'Ongoing DP') {
                $query->where('status', 'Active')->where(function($q) {
                    $q->whereColumn(
                        DB::raw('(SELECT SUM(amount) FROM payments WHERE reservation_id = reservations.id)'),
                        '<',
                        DB::raw('(SELECT downpayment_amount FROM price_lists WHERE unit_id = reservations.unit_id)')
                    );
                });
            } elseif ($request->status === 'Ready for Contract') {
                $query->where('status', 'Active')->where(function($q) {
                    $q->whereColumn(
                        DB::raw('(SELECT SUM(amount) FROM payments WHERE reservation_id = reservations.id)'),
                        '>=',
                        DB::raw('(SELECT downpayment_amount FROM price_lists WHERE unit_id = reservations.unit_id)')
                    );
                });
            } elseif ($request->status === 'Expiring Soon') {
                $query->where('status', 'Active')
                      ->whereBetween('expiry_date', [now(), now()->addDays(7)]);
            } else {
                $query->where('status', $request->status);
            }
        }

        $reservations = $query->latest()->paginate($request->per_page ?? 10)->withQueryString();

        // Get Available Units + any unit already in a reservation (for editing)
        $reservedUnitIds = Reservation::pluck('unit_id')->filter()->unique()->toArray();
        $units = Unit::with('project')
            ->where('status', 'Available')
            ->orWhereIn('id', $reservedUnitIds)
            ->select('id', 'name', 'status', 'project_id', 'block_num', 'lot_num')
            ->get();

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'customers' => Customer::select('id', 'first_name', 'last_name')->get()->map(fn($c) => [
                'id' => $c->id,
                'name' => "{$c->first_name} {$c->last_name}"
            ]),
            'units' => $units,
            'brokers' => Broker::select('id', 'name')->get(),
            'payment_methods' => ['Cash', 'Check', 'Bank Transfer', 'GCash/Maya', 'Other'],
            'stats' => [
                'total' => Reservation::count(),
                'ongoing_dp' => Reservation::where('status', 'Active')->where(function($q) {
                    $q->whereColumn(
                        DB::raw('(SELECT SUM(amount) FROM payments WHERE reservation_id = reservations.id)'),
                        '<',
                        DB::raw('(SELECT downpayment_amount FROM price_lists WHERE unit_id = reservations.unit_id)')
                    );
                })->count(),
                'ready_for_contract' => Reservation::where('status', 'Active')->where(function($q) {
                    $q->whereColumn(
                        DB::raw('(SELECT SUM(amount) FROM payments WHERE reservation_id = reservations.id)'),
                        '>=',
                        DB::raw('(SELECT downpayment_amount FROM price_lists WHERE unit_id = reservations.unit_id)')
                    );
                })->count(),
                'contracted' => Reservation::where('status', 'Contracted')->count(),
                'expiring_soon' => Reservation::where('status', 'Active')
                    ->whereBetween('expiry_date', [now(), now()->addDays(7)])
                    ->count(),
                'total_collected' => (float) Payment::whereNotNull('reservation_id')->sum('amount') ?: 0,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'unit_id' => 'required|exists:units,id',
            'broker_id' => 'nullable|exists:brokers,id',
            'reservation_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:reservation_date',
            'fee' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference_no' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            $reservation = Reservation::create([
                'customer_id' => $validated['customer_id'],
                'unit_id' => $validated['unit_id'],
                'broker_id' => $validated['broker_id'],
                'reservation_date' => $validated['reservation_date'],
                'expiry_date' => $validated['expiry_date'],
                'fee' => $validated['fee'],
            ]);
            
            // Update unit status to Reserved
            Unit::where('id', $validated['unit_id'])->update(['status' => 'Reserved']);

            // Record Accounting Entry if fee > 0
            if ($validated['fee'] > 0) {
                // Determine company_id: Use user's company or default to the first company found
                $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? null);
                
                if (!$companyId) {
                    throw new \Exception("Cannot record payment: No company associated with current user and no default company found.");
                }

                $payment = Payment::create([
                    'company_id' => $companyId,
                    'customer_id' => $validated['customer_id'],
                    'reservation_id' => $reservation->id,
                    'payment_type' => 'Reservation Fee',
                    'amount' => $validated['fee'],
                    'payment_date' => $validated['reservation_date'],
                    'payment_method' => $validated['payment_method'],
                    'reference_no' => $validated['reference_no'],
                ]);

                $this->accountingService->recordReservationFeeReceipt($payment);
            }
        });

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'unit_id' => 'required|exists:units,id',
            'broker_id' => 'nullable|exists:brokers,id',
            'reservation_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:reservation_date',
            'fee' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $reservation) {
            // If unit changed, release the old one and reserve the new one
            if ($reservation->unit_id != $validated['unit_id']) {
                Unit::where('id', $reservation->unit_id)->update(['status' => 'Available']);
                Unit::where('id', $validated['unit_id'])->update(['status' => 'Reserved']);
            }
            
            $reservation->update($validated);
            
            // Synchronize associated accounting records (Payment and Journal Entries)
            $this->accountingService->updateReservationAccounting($reservation);
        });

        return redirect()->back()->with('success', 'Reservation and accounting records synchronized.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            // Revert unit status to Available
            Unit::where('id', $reservation->unit_id)->update(['status' => 'Available']);
            
            // If it has associated accounting entries, delete them safely
            $payment = $reservation->payments()->first();
            if ($payment) {
                if ($payment->journal_entry_id) {
                    \App\Models\JournalEntry::where('id', $payment->journal_entry_id)->delete();
                }
                $payment->delete();
            }

            // Also delete revenue recognition entries if any
            \App\Models\JournalEntry::where('referenceable_type', get_class($reservation))
                ->where('referenceable_id', $reservation->id)
                ->delete();

            $reservation->delete();
        });

        return redirect()->back();
    }

    /**
     * Sign Contract: Transition from Active to Contracted (Revenue Recognition)
     */
    public function contract(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'Active') {
            return redirect()->back()->with('error', 'Only active reservations can be contracted.');
        }

        $validated = $request->validate([
            'plan_type' => 'required|in:Spot Cash,Installment',
            'amortization_terms' => 'required_if:plan_type,Installment|nullable|integer|min:1',
            'interest_rate' => 'required|numeric|min:0',
            'start_date' => 'required|date',
        ]);

        $priceList = $reservation->unit->priceList;
        if (!$priceList) {
            return redirect()->back()->with('error', 'Pricing not set for this unit. Please set a price list first.');
        }

        if (!$reservation->is_dp_fully_paid) {
            $due = (float) $priceList->downpayment_amount - $reservation->total_paid;
            return redirect()->back()->with('error', "Cannot contract: Downpayment is not fully paid. Balance due: " . number_format($due, 2));
        }

        DB::transaction(function () use ($reservation, $validated, $priceList) {
            $tcp = (float) $priceList->tcp;
            $dpPaid = (float) $reservation->total_paid;
            $loanableAmount = $tcp - $dpPaid;
            
            // Fix: Force 1 term and 0 interest for Spot Cash
            if ($validated['plan_type'] === 'Spot Cash') {
                $terms = 1;
                $annualInterest = 0;
            } else {
                $terms = (int) ($validated['amortization_terms'] ?? 1);
                $annualInterest = (float) $validated['interest_rate'] / 100;
            }

            $monthlyInterest = $annualInterest / 12;
            
            // Calculate Monthly Amortization (Standard formula)
            // MA = P * [ i(1+i)^n ] / [ (1+i)^n - 1 ]
            if ($annualInterest > 0 && $validated['plan_type'] === 'Installment') {
                $monthlyAmortization = $loanableAmount * ($monthlyInterest * pow(1 + $monthlyInterest, $terms)) / (pow(1 + $monthlyInterest, $terms) - 1);
            } else {
                $monthlyAmortization = $loanableAmount / $terms;
            }

            // Generate unique contract number: CNT-YEAR-MONTH-RESERVATION_ID
            $contractNo = 'CNT-' . date('Ym') . '-' . str_pad($reservation->id, 4, '0', STR_PAD_LEFT);

            $contractedSale = \App\Models\ContractedSale::create([
                'contract_no' => $contractNo,
                'company_id' => Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1),
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'unit_id' => $reservation->unit_id,
                'tcp' => $tcp,
                'downpayment_paid' => $dpPaid,
                'loanable_amount' => $loanableAmount,
                'interest_rate' => $annualInterest * 100,
                'terms_month' => $terms,
                'monthly_amortization' => $monthlyAmortization,
                'start_date' => $validated['start_date'],
                'status' => 'Active',
            ]);

            $reservation->update(['status' => 'Contracted']);
            $reservation->unit->update(['status' => 'Sold']);

            // Accounting revenue recognition - Fix: recognize Total Paid (Fee + DP)
            $payment = $reservation->payments()->first();
            $referenceNo = $payment ? $payment->reference_no : null;
            $this->accountingService->recognizeRevenueFromReservation($reservation, $reservation->total_paid, $referenceNo);

            // Generate Payment Schedule with breakdown
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $remainingPrincipal = $loanableAmount;

            for ($i = 1; $i <= $terms; $i++) {
                $interestComponent = $remainingPrincipal * $monthlyInterest;
                $principalComponent = $monthlyAmortization - $interestComponent;
                
                // Adjustment for the last installment to handle rounding
                if ($i === $terms) {
                    $principalComponent = $remainingPrincipal;
                    $monthlyAmortization = $principalComponent + $interestComponent;
                }

                $remainingPrincipal -= $principalComponent;

                \App\Models\PaymentSchedule::create([
                    'reservation_id' => $reservation->id,
                    'contracted_sale_id' => $contractedSale->id,
                    'customer_id' => $reservation->customer_id,
                    'unit_id' => $reservation->unit_id,
                    'type' => 'Amortization',
                    'installment_no' => $i,
                    'due_date' => $startDate->copy()->addMonths($i - 1),
                    'amount_due' => $monthlyAmortization,
                    'principal' => $principalComponent,
                    'interest' => $interestComponent,
                    'remaining_balance' => max(0, $remainingPrincipal),
                    'status' => 'Pending',
                    'remarks' => "Monthly Amortization $i of $terms"
                ]);
            }
        });

        return redirect()->back()->with('success', 'Contract signed and amortization schedule generated.');
    }

    /**
     * Cancel/Refund/Forfeit: Transition status and record accounting reversal
     */
    public function cancel(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'action' => 'required|in:Refund,Forfeit',
            'reference_no' => 'nullable|string',
        ]);

        DB::transaction(function () use ($reservation, $validated) {
            $reservation->update(['status' => $validated['action'] === 'Refund' ? 'Refunded' : 'Cancelled']);
            
            // Revert unit status to Available
            Unit::where('id', $reservation->unit_id)->update(['status' => 'Available']);

            // Record Accounting reversal
            if ($validated['action'] === 'Refund') {
                $this->accountingService->recordRefund($reservation, $reservation->fee, $validated['reference_no']);
            } else {
                $this->accountingService->recordForfeiture($reservation, $reservation->fee, $validated['reference_no']);
            }
        });

        return redirect()->back();
    }

    /**
     * Record an additional payment (e.g., Downpayment balance)
     */
    public function recordPayment(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_type' => 'required|string|in:Downpayment,Amortization,Other',
            'reference_no' => 'required|string',
        ]);

        DB::transaction(function () use ($reservation, $validated) {
            $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? null);

            $payment = Payment::create([
                'company_id' => $companyId,
                'customer_id' => $reservation->customer_id,
                'reservation_id' => $reservation->id,
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'],
            ]);

            $this->accountingService->recordReservationFeeReceipt($payment);
        });

        return redirect()->back()->with('success', 'Payment recorded and ledger updated.');
    }
}
