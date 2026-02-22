<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? 1);
        
        $query = Vendor::with('defaultExpenseAccount')->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('tin', 'like', "%{$search}%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $vendors = $query->latest()->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Accounting/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'per_page', 'category']),
            'expenseAccounts' => ChartOfAccount::where('company_id', $companyId)->where('type', 'expense')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tin' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'category' => 'required|in:Utility,Contractor,Government,Broker,Supplier,Other',
            'verification_status' => 'required|in:Pending,Verified,Blacklisted',
            'payment_terms' => 'nullable|string|max:255',
            'default_expense_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string',
        ]);

        // Safer company ID determination
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);
        
        if (!$companyId) {
            return redirect()->back()->withErrors(['company_id' => 'No active company found for this user. Please ensure your account is linked to a company.']);
        }

        $validated['company_id'] = $companyId;

        Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['defaultExpenseAccount', 'documents']);
        return Inertia::render('Accounting/Vendors/Show', [
            'vendor' => $vendor
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tin' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'category' => 'required|in:Utility,Contractor,Government,Broker,Supplier,Other',
            'verification_status' => 'required|in:Pending,Verified,Blacklisted',
            'payment_terms' => 'nullable|string|max:255',
            'default_expense_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string',
        ]);

        $vendor->update($validated);

        return redirect()->back()->with('success', 'Vendor updated successfully.');
    }

    public function uploadDocument(Request $request, Vendor $vendor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'category' => 'required|string',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('vendors/' . $vendor->id, 'public');
            
            VendorDocument::create([
                'vendor_id' => $vendor->id,
                'name' => $request->name,
                'file_path' => $path,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'category' => $request->category,
            ]);
        }

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(VendorDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        // Check for dependencies (unpaid bills) before deleting could be added here
        $vendor->delete();
        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
