<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Vendor;
use App\Models\ChartOfAccount;
use App\Models\Project;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BillController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $query = Bill::with(['vendor', 'project', 'creator'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($v) use ($search) {
                      $v->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $bills = $query->latest('bill_date')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Accounting/Bills/Index', [
            'bills' => $bills,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $vendors = Vendor::where('company_id', $companyId)
            ->verified()
            ->get();
        
        // Fetch relevant accounts (Assets and Expenses for Bills)
        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->whereIn('type', ['asset', 'expense'])
            ->where('is_active', true)
            ->get();

        $projects = Project::all();

        // Optional: Pre-populate based on source bill (e.g. creating Debit Memo from Bill)
        $sourceBill = null;
        if ($request->source_id) {
            $sourceBill = Bill::with('items')->find($request->source_id);
        }

        $prePopulated = [
            'vendor_id' => $sourceBill?->vendor_id ?? $request->vendor_id,
            'project_id' => $sourceBill?->project_id ?? '',
            'type' => $request->type ?? 'Bill',
            'notes' => $sourceBill ? "Credit for Bill #{$sourceBill->bill_number}. " . $sourceBill->notes : '',
            'items' => $sourceBill?->items->map(function($item) {
                return [
                    'chart_of_account_id' => $item->chart_of_account_id,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'project_id' => $item->project_id ?? '',
                ];
            })->toArray(),
        ];

        return Inertia::render('Accounting/Bills/Create', [
            'vendors' => $vendors,
            'accounts' => $accounts,
            'projects' => $projects,
            'prePopulated' => $prePopulated,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'type' => 'required|in:Bill,Debit Memo',
            'bill_number' => 'required|string|max:50',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
            'status' => 'required|in:Draft,Approved',
            'items' => 'required|array|min:1',
            'items.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.project_id' => 'nullable|exists:projects,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $totalAmount = collect($validated['items'])->sum('amount');
            $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

            $bill = Bill::create([
                'company_id' => $companyId,
                'vendor_id' => $validated['vendor_id'],
                'type' => $validated['type'],
                'bill_number' => $validated['bill_number'],
                'bill_date' => $validated['bill_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'project_id' => $validated['project_id'],
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'project_id' => $item['project_id'] ?? $bill->project_id,
                ]);
            }

            if ($bill->status === 'Approved') {
                if ($bill->type === 'Debit Memo') {
                    $this->accountingService->recordDebitMemo($bill);
                } else {
                    $this->accountingService->recordBill($bill);
                }
            }

            return redirect()->route('accounting.bills.index')->with('success', $bill->type . ' created successfully.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill)
    {
        $bill->load(['vendor', 'project', 'creator', 'items.chartOfAccount', 'items.project', 'journalEntry.lines.chartOfAccount']);
        
        return Inertia::render('Accounting/Bills/Show', [
            'bill' => $bill,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bill $bill)
    {
        if ($bill->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only Draft bills can be edited.');
        }

        $bill->load('items');
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $vendors = Vendor::where('company_id', $companyId)->verified()->get();
        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->whereIn('type', ['asset', 'expense'])
            ->where('is_active', true)
            ->get();
        $projects = Project::all();

        return Inertia::render('Accounting/Bills/Create', [
            'bill' => $bill,
            'vendors' => $vendors,
            'accounts' => $accounts,
            'projects' => $projects,
            'isEditing' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bill $bill)
    {
        // If it's just a status update (e.g., from the Show page)
        if ($request->has('status') && !$request->has('items')) {
            $validated = $request->validate([
                'status' => 'required|in:Draft,Approved,Cancelled',
            ]);

            return DB::transaction(function () use ($validated, $bill) {
                $oldStatus = $bill->status;
                $bill->update($validated);

                if ($oldStatus === 'Draft' && $bill->status === 'Approved') {
                    $this->accountingService->recordBill($bill);
                }

                // If cancelling an already approved bill, create reversal entry
                if ($oldStatus === 'Approved' && $bill->status === 'Cancelled') {
                    $this->accountingService->reverseBill($bill);
                }

                return redirect()->back()->with('success', 'Bill status updated successfully.');
            });
        }

        // Full edit logic for Draft bills
        if ($bill->status !== 'Draft') {
            return redirect()->back()->with('error', 'Approved or paid bills cannot be modified.');
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'type' => 'required|in:Bill,Debit Memo',
            'bill_number' => 'required|string|max:50',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
            'status' => 'required|in:Draft,Approved',
            'items' => 'required|array|min:1',
            'items.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.project_id' => 'nullable|exists:projects,id',
        ]);

        return DB::transaction(function () use ($validated, $bill) {
            $totalAmount = collect($validated['items'])->sum('amount');
            
            $bill->update([
                'vendor_id' => $validated['vendor_id'],
                'type' => $validated['type'],
                'bill_number' => $validated['bill_number'],
                'bill_date' => $validated['bill_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'project_id' => $validated['project_id'],
            ]);

            // Sync items (Delete old, create new is simplest for line items)
            $bill->items()->delete();
            foreach ($validated['items'] as $item) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'project_id' => $item['project_id'] ?? $bill->project_id,
                ]);
            }

            if ($bill->status === 'Approved') {
                if ($bill->type === 'Debit Memo') {
                    $this->accountingService->recordDebitMemo($bill);
                } else {
                    $this->accountingService->recordBill($bill);
                }
            }

            return redirect()->route('accounting.bills.index')->with('success', $bill->type . ' updated successfully.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill)
    {
        if ($bill->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only Draft bills can be deleted.');
        }

        $bill->delete();

        return redirect()->route('accounting.bills.index')->with('success', 'Bill deleted successfully.');
    }
}
