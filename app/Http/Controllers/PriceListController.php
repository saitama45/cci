<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\Unit;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PriceListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PriceList::class);

        $query = PriceList::with(['unit.project']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('unit', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('block_num', 'like', "%{$search}%")
                  ->orWhere('lot_num', 'like', "%{$search}%")
                  ->orWhereHas('project', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('project_id')) {
            $query->whereHas('unit', function($q) use ($request) {
                $q->where('project_id', $request->input('project_id'));
            });
        }

        $priceLists = $query->latest()->paginate(10)->withQueryString();
        $projects = Project::select('id', 'name')->where('is_active', true)->get();

        return Inertia::render('PriceLists/Index', [
            'priceLists' => $priceLists,
            'projects' => $projects,
            'filters' => $request->only(['search', 'project_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', PriceList::class);

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'price_per_sqm' => 'required|numeric|min:0',
            'downpayment_amount' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        // Auto-calculate logic
        $unit = Unit::findOrFail($validated['unit_id']);
        $basePrice = $validated['price_per_sqm'] * $unit->sqm_area;
        $vatRate = 0.12;
        $vatAmount = $basePrice * $vatRate;
        $tcp = $basePrice + $vatAmount;

        PriceList::create([
            'unit_id' => $validated['unit_id'],
            'price_per_sqm' => $validated['price_per_sqm'],
            'downpayment_amount' => $validated['downpayment_amount'],
            'vat_amount' => $vatAmount,
            'tcp' => $tcp,
            'effective_date' => $validated['effective_date'],
        ]);

        return redirect()->back()->with('success', 'Price list created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PriceList $priceList)
    {
        $this->authorize('view', $priceList);
        
        $priceList->load(['unit.project']);

        return Inertia::render('PriceLists/Show', [
            'priceList' => $priceList,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PriceList $priceList)
    {
        $this->authorize('update', $priceList);

        $validated = $request->validate([
            'price_per_sqm' => 'required|numeric|min:0',
            'downpayment_amount' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        // Recalculate based on new price per sqm
        $unit = $priceList->unit;
        $basePrice = $validated['price_per_sqm'] * $unit->sqm_area;
        $vatRate = 0.12;
        $vatAmount = $basePrice * $vatRate;
        $tcp = $basePrice + $vatAmount;

        $priceList->update([
            'price_per_sqm' => $validated['price_per_sqm'],
            'downpayment_amount' => $validated['downpayment_amount'],
            'vat_amount' => $vatAmount,
            'tcp' => $tcp,
            'effective_date' => $validated['effective_date'],
        ]);

        return redirect()->back()->with('success', 'Price list updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PriceList $priceList)
    {
        $this->authorize('delete', $priceList);

        $priceList->delete();

        return redirect()->back()->with('success', 'Price list deleted successfully.');
    }
    
    /**
     * Get units for a specific project (API endpoint for dropdowns)
     */
    public function getUnitsByProject($projectId)
    {
        return Unit::where('project_id', $projectId)
                   ->whereDoesntHave('priceList') // Optional: only show units without price lists? Or allow multiple? Assuming 1 active for now or listing all. 
                   // Actually, a unit can have price history. But for creating a NEW price list entry, we probably just want to select the unit.
                   ->select('id', 'name', 'block_num', 'lot_num', 'sqm_area')
                   ->get();
    }
}
