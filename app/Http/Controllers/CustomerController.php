<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DocumentRequirement;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('middle_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('contact_no', 'like', "%{$request->search}%")
                  ->orWhere('tin', 'like', "%{$request->search}%");
            });
        }

        $customers = $query->with('documents')->latest()->paginate($request->get('per_page', 10))->withQueryString();
        $requirements = DocumentRequirement::where('status', true)->orderBy('sort_order')->get();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'requirements' => $requirements,
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'documents.requirement',
            'reservations.unit.project',
            'contractedSales.unit.project'
        ]);
        $requirements = DocumentRequirement::where('status', true)->orderBy('sort_order')->get();

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'requirements' => $requirements,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'tin' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100|unique:customers',
            'contact_no' => 'nullable|string|max:20',
            'maceda_status' => 'boolean',
            'home_no_street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:Male,Female,Other',
            'civil_status' => 'nullable|string|in:Single,Married,Divorced,Widowed',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('customers', 'public');
        }

        $customer = Customer::create($validated);

        LogActivity::log('Sales', 'Created', "Created Customer: {$customer->full_name}", $customer);

        return redirect()->back()->with('success', 'Customer created successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'tin' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100|unique:customers,email,' . $customer->id,
            'contact_no' => 'nullable|string|max:20',
            'maceda_status' => 'boolean',
            'home_no_street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:Male,Female,Other',
            'civil_status' => 'nullable|string|in:Single,Married,Divorced,Widowed',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($customer->profile_photo) {
                Storage::disk('public')->delete($customer->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')->store('customers', 'public');
        }

        $customer->update($validated);

        LogActivity::log('Sales', 'Updated', "Updated Customer: {$customer->full_name}", $customer);

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        // 1. Delete Profile Photo
        if ($customer->profile_photo) {
            Storage::disk('public')->delete($customer->profile_photo);
        }

        // 2. Delete All Uploaded Documents
        foreach ($customer->documents as $doc) {
            if ($doc->file_path) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        // 3. Delete the entire customer directory if it exists
        $directory = 'customer_documents/' . $customer->id;
        if (Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->deleteDirectory($directory);
        }

        LogActivity::log('Sales', 'Deleted', "Deleted Customer: {$customer->full_name} (#{$customer->id})");

        $customer->delete();
        
        return redirect()->back()->with('success', 'Customer and all associated files deleted successfully.');
    }
}
