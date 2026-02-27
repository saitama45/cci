<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BankController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);
        $banks = Bank::where('company_id', $companyId)->get();

        return Inertia::render('Accounting/Banks/Index', [
            'banks' => $banks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:banks,code',
            'branch' => 'nullable|string|max:255',
            'cheque_config' => 'nullable|array',
        ]);

        $validated['company_id'] = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        Bank::create($validated);

        return redirect()->back()->with('success', 'Bank added successfully.');
    }

    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:banks,code,' . $bank->id,
            'branch' => 'nullable|string|max:255',
            'cheque_config' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        $bank->update($validated);

        return redirect()->back()->with('success', 'Bank updated successfully.');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return redirect()->back()->with('success', 'Bank deleted.');
    }
}
