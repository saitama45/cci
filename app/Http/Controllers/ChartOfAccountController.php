<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        $query = ChartOfAccount::where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('code')->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Accounting/ChartOfAccounts/Index', [
            'accounts' => $accounts,
            'types' => ['asset', 'liability', 'equity', 'revenue', 'expense'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:100',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);

        ChartOfAccount::create($validated);

        return redirect()->back()->with('success', 'Account added to Chart of Accounts.');
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $chartOfAccount->update($validated);

        return redirect()->back()->with('success', 'Account updated successfully.');
    }
}
