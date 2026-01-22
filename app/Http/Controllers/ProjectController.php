<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:projects',
            'location' => 'required|string',
            'map_overlay' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Project::create($validated);

        return redirect()->back()->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => ['required', 'string', 'max:20', Rule::unique('projects')->ignore($project->id)],
            'location' => 'required|string',
            'map_overlay' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $project->update($validated);

        return redirect()->back()->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->back()->with('success', 'Project deleted successfully.');
    }
}
