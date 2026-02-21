<?php

namespace App\Http\Controllers;

use App\Models\Broker;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrokerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Broker::query();

        if ($request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('prc_license', 'like', "%{$search}%");
        }

        $brokers = $query->latest()->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Brokers/Index', [
            'brokers' => $brokers,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'prc_license' => 'nullable|string|max:50',
        ]);

        Broker::create($validated);

        return redirect()->back()->with('success', 'Broker created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Broker $broker)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'prc_license' => 'nullable|string|max:50',
        ]);

        $broker->update($validated);

        return redirect()->back()->with('success', 'Broker updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Broker $broker)
    {
        // Check if broker has reservations before deleting
        if ($broker->reservations()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete broker with existing reservations.');
        }

        $broker->delete();

        return redirect()->back()->with('success', 'Broker deleted successfully.');
    }
}
