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
        $query = Reservation::with(['customer', 'unit.project', 'broker', 'payments.journalEntry']);

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhereHas('unit', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
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
                'active' => Reservation::where('expiry_date', '>=', now())->count(),
                'expiring_soon' => Reservation::whereBetween('expiry_date', [now(), now()->addDays(7)])->count(),
                'total_fees' => (float) Reservation::sum('fee') ?: 0,
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
            'reference_no' => 'nullable|string',
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
        });

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            // Revert unit status to Available
            Unit::where('id', $reservation->unit_id)->update(['status' => 'Available']);
            $reservation->delete();
        });

        return redirect()->back();
    }

    /**
     * Sign Contract: Transition from Active to Contracted (Revenue Recognition)
     */
    public function contract(Reservation $reservation)
    {
        if ($reservation->status !== 'Active') {
            return redirect()->back()->with('error', 'Only active reservations can be contracted.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'Contracted']);
            
            // Record Revenue Recognition in Accounting
            $this->accountingService->recognizeRevenueFromReservation($reservation, $reservation->fee);
        });

        return redirect()->back();
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
}
