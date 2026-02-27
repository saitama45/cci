<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationLine;
use App\Models\ChartOfAccount;
use App\Models\Disbursement;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BankReconciliationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $query = BankReconciliation::with(['account', 'preparer'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('account', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $reconciliations = $query->latest('statement_date')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Accounting/BankReconciliation/Index', [
            'reconciliations' => $reconciliations,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        // Fetch bank accounts
        $bankAccounts = ChartOfAccount::where('company_id', $companyId)
            ->where('type', 'asset')
            ->where('category', 'Current Asset')
            ->where('name', 'like', '%Cash%')
            ->orWhere('name', 'like', '%Bank%')
            ->get();

        return Inertia::render('Accounting/BankReconciliation/Create', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'statement_date' => 'required|date',
            'statement_ending_balance' => 'required|numeric',
            'start_date' => 'required|date|before_or_equal:statement_date', // Needed to bound transactions
        ]);

        return DB::transaction(function () use ($validated) {
            $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

            $reconciliation = BankReconciliation::create([
                'company_id' => $companyId,
                'chart_of_account_id' => $validated['chart_of_account_id'],
                'statement_date' => $validated['statement_date'],
                'statement_ending_balance' => $validated['statement_ending_balance'],
                'cleared_balance' => 0, // Starts at 0, updated dynamically on the frontend or save
                'difference' => $validated['statement_ending_balance'],
                'status' => 'Draft',
                'prepared_by' => Auth::id(),
            ]);

            // 1. Fetch Disbursements (Payments Out) for this account
            // Assuming Disbursement has bank_account_id
            $disbursements = Disbursement::where('company_id', $companyId)
                ->where('bank_account_id', $validated['chart_of_account_id'])
                ->whereIn('status', ['Approved', 'Paid'])
                ->whereBetween('payment_date', [$validated['start_date'], $validated['statement_date']])
                ->get();

            foreach ($disbursements as $disbursement) {
                BankReconciliationLine::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'transaction_type' => Disbursement::class,
                    'transaction_id' => $disbursement->id,
                    'transaction_date' => $disbursement->payment_date,
                    'type' => 'Disbursement',
                    'reference_no' => $disbursement->voucher_no,
                    'description' => 'Payment to ' . ($disbursement->vendor->name ?? 'Vendor'),
                    'amount' => -abs($disbursement->total_amount), // Negative for out
                ]);
            }

            // 2. Fetch Receipts/Payments (Money In). 
            // In CCI, generic Payments are collected. We may need to assume a default bank or check how they are deposited.
            // If the system doesn't link `Payment` directly to a bank account yet, we might need to assume all or filter by a specific mechanism.
            // For now, let's fetch payments that are not yet cleared.
            $payments = Payment::where('company_id', $companyId)
                ->whereBetween('payment_date', [$validated['start_date'], $validated['statement_date']])
                ->get();

            foreach ($payments as $payment) {
                // Determine if this payment is deposited into the selected bank account. 
                // We'd look at its journal entry lines. For robustness, let's just use it if the journal entry debits this account.
                $isDepositedHere = false;
                if ($payment->journal_entry_id) {
                    $hasDebit = DB::table('journal_entry_lines')
                        ->where('journal_entry_id', $payment->journal_entry_id)
                        ->where('chart_of_account_id', $validated['chart_of_account_id'])
                        ->where('debit', '>', 0)
                        ->exists();
                    if ($hasDebit) {
                        $isDepositedHere = true;
                    }
                }

                if ($isDepositedHere) {
                    BankReconciliationLine::create([
                        'bank_reconciliation_id' => $reconciliation->id,
                        'transaction_type' => Payment::class,
                        'transaction_id' => $payment->id,
                        'transaction_date' => $payment->payment_date,
                        'type' => 'Receipt',
                        'reference_no' => $payment->reference_no,
                        'description' => 'Receipt from ' . ($payment->customer->name ?? 'Customer'),
                        'amount' => abs($payment->amount), // Positive for in
                    ]);
                }
            }

            return redirect()->route('accounting.reconciliations.show', $reconciliation->id)
                ->with('success', 'Bank Reconciliation Draft created.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(BankReconciliation $reconciliation)
    {
        $reconciliation->load(['account', 'preparer', 'lines' => function($q) {
            $q->orderBy('transaction_date');
        }]);

        // Calculate beginning balance (sum of all previously cleared transactions for this account up to start date)
        // For simplicity in this iteration, we'll let the user provide an opening balance or we calculate from GL.
        $glBalance = $this->getGLBalance($reconciliation->chart_of_account_id, $reconciliation->statement_date);

        return Inertia::render('Accounting/BankReconciliation/Show', [
            'reconciliation' => $reconciliation,
            'glBalance' => $glBalance
        ]);
    }

    /**
     * Update the reconciliation statement balance.
     */
    public function update(Request $request, BankReconciliation $reconciliation)
    {
        if ($reconciliation->status !== 'Draft') {
            return redirect()->back()->with('error', 'Cannot edit completed reconciliation');
        }

        $validated = $request->validate([
            'statement_ending_balance' => 'required|numeric',
        ]);

        $reconciliation->update($validated);

        return redirect()->back()->with('success', 'Statement balance updated.');
    }

    /**
     * Toggle a line's cleared status (API).
     */
    public function toggleLine(Request $request, BankReconciliation $reconciliation, BankReconciliationLine $line)
    {
        if ($reconciliation->status !== 'Draft') {
            return response()->json(['error' => 'Cannot edit completed reconciliation'], 403);
        }

        $line->update([
            'is_cleared' => $request->is_cleared,
            'cleared_date' => $request->is_cleared ? now() : null,
        ]);

        // AUTOMATION: If this line is linked to a PDC, update the Vault status
        if ($line->transaction_type === \App\Models\Disbursement::class || $line->transaction_type === \App\Models\Payment::class) {
            $pdc = \App\Models\PdcVault::where('disbursement_id', $line->transaction_id)
                ->orWhere('payment_id', $line->transaction_id)
                ->first();
            
            if ($pdc) {
                $pdc->update([
                    'status' => $request->is_cleared ? 'Cleared' : 'Pending',
                    'cleared_date' => $request->is_cleared ? $line->transaction_date : null
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Bulk toggle lines (API).
     */
    public function bulkToggle(Request $request, BankReconciliation $reconciliation)
    {
        if ($reconciliation->status !== 'Draft') {
            return response()->json(['error' => 'Cannot edit completed reconciliation'], 403);
        }

        $request->validate([
            'is_cleared' => 'required|boolean'
        ]);

        $lineIds = $reconciliation->lines()->pluck('id');

        $reconciliation->lines()->update([
            'is_cleared' => $request->is_cleared,
            'cleared_date' => $request->is_cleared ? now() : null,
        ]);

        // AUTOMATION: Bulk update all linked PDCs
        foreach ($reconciliation->lines as $line) {
            if ($line->transaction_type === \App\Models\Disbursement::class || $line->transaction_type === \App\Models\Payment::class) {
                $pdc = \App\Models\PdcVault::where('disbursement_id', $line->transaction_id)
                    ->orWhere('payment_id', $line->transaction_id)
                    ->first();
                
                if ($pdc) {
                    $pdc->update([
                        'status' => $request->is_cleared ? 'Cleared' : 'Pending',
                        'cleared_date' => $request->is_cleared ? $line->transaction_date : null
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Complete the reconciliation.
     */
    public function complete(Request $request, BankReconciliation $reconciliation)
    {
        if ($reconciliation->status !== 'Draft') {
            return redirect()->back()->with('error', 'Already completed.');
        }

        $validated = $request->validate([
            'cleared_balance' => 'required|numeric',
            'difference' => 'required|numeric',
        ]);

        if (abs($validated['difference']) > 0.01) {
            return redirect()->back()->with('error', 'Cannot complete reconciliation with a non-zero difference.');
        }

        $reconciliation->update([
            'status' => 'Completed',
            'cleared_balance' => $validated['cleared_balance'],
            'difference' => $validated['difference'],
        ]);

        return redirect()->back()->with('success', 'Bank Reconciliation Completed successfully.');
    }

    /**
     * Utility to get GL balance for an account up to a date.
     */
    private function getGLBalance($accountId, $date)
    {
        $debits = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.chart_of_account_id', $accountId)
            ->where('journal_entries.transaction_date', '<=', $date)
            ->sum('debit');

        $credits = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.chart_of_account_id', $accountId)
            ->where('journal_entries.transaction_date', '<=', $date)
            ->sum('credit');

        // Asset account: Debit - Credit
        return $debits - $credits;
    }
}
