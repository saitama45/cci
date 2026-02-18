<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\Broker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['customer', 'unit.project', 'broker']);

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
        ]);

        DB::transaction(function () use ($validated) {
            Reservation::create($validated);
            
            // Update unit status to Reserved
            Unit::where('id', $validated['unit_id'])->update(['status' => 'Reserved']);
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
}
