<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequirement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentRequirementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DocumentRequirement::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        $requirements = $query->orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('DocumentRequirements/Index', [
            'requirements' => $requirements,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_requirements',
            'description' => 'nullable|string|max:1000',
            'is_required' => 'required|boolean',
            'category' => 'required|string|max:50',
            'status' => 'required|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        DocumentRequirement::create($validated);

        return redirect()->back()->with('success', 'Document requirement created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentRequirement $documentRequirement)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_requirements,name,' . $documentRequirement->id,
            'description' => 'nullable|string|max:1000',
            'is_required' => 'required|boolean',
            'category' => 'required|string|max:50',
            'status' => 'required|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $documentRequirement->update($validated);

        return redirect()->back()->with('success', 'Document requirement updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentRequirement $documentRequirement)
    {
        $documentRequirement->delete();

        return redirect()->back()->with('success', 'Document requirement deleted successfully.');
    }
}
