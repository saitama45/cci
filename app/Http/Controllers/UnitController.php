<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Unit::class);

        $query = Unit::with(['project', 'reservations.customer']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('project', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reservations.customer', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        $units = $query->latest()->paginate(10)->withQueryString();
        $projects = Project::select('id', 'name')->where('is_active', true)->get();

        return Inertia::render('Units/Index', [
            'units' => $units,
            'projects' => $projects,
            'filters' => $request->only(['search', 'project_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Unit::class);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'block_num' => 'required|integer|min:1',
            'lot_num' => 'required|integer|min:1',
            'name' => 'required|string|max:100',
            'sqm_area' => 'required|numeric|min:0.01',
            'status' => ['required', Rule::in(['Available', 'Reserved', 'Sold', 'Blocked'])],
            'svg_path' => 'nullable|string',
        ]);

        Unit::create($validated);

        return redirect()->back()->with('success', 'Unit created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        $this->authorize('view', $unit);
        
        $unit->load(['project', 'reservations.customer']);

        return Inertia::render('Units/Show', [
            'unit' => $unit,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'block_num' => 'required|integer|min:1',
            'lot_num' => 'required|integer|min:1',
            'name' => 'required|string|max:100',
            'sqm_area' => 'required|numeric|min:0.01',
            'status' => ['required', Rule::in(['Available', 'Reserved', 'Sold', 'Blocked'])],
            'svg_path' => 'nullable|string',
        ]);

        $unit->update($validated);

        return redirect()->back()->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        $this->authorize('delete', $unit);

        $unit->delete();

        return redirect()->back()->with('success', 'Unit deleted successfully.');
    }

    /**
     * Get units for a specific project
     */
    public function getUnitsByProject($projectId)
    {
        // Simple API-like response for dropdowns
        return response()->json(
            Unit::where('project_id', $projectId)
                ->select('id', 'name', 'block_num', 'lot_num', 'sqm_area')
                ->orderBy('block_num')
                ->orderBy('lot_num')
                ->get()
        );
    }
}
