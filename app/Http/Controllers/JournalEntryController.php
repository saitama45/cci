<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Helpers\LogActivity;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        $query = JournalEntry::with(['lines.chartOfAccount', 'user'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $journalEntries = $query->latest('transaction_date')->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Accounting/JournalEntries/Index', [
            'journalEntries' => $journalEntries,
        ]);
    }

    public function create()
    {
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        return Inertia::render('Accounting/JournalEntries/Create', [
            'accounts' => ChartOfAccount::where('company_id', $companyId)->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'description' => 'required|string',
            'lines' => 'required|array|min:2',
            'lines.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.memo' => 'nullable|string',
        ]);

        // Validation: Must balance
        $totalDebit = collect($validated['lines'])->sum('debit');
        $totalCredit = collect($validated['lines'])->sum('credit');

        if (round($totalDebit, 4) !== round($totalCredit, 4)) {
            return redirect()->back()->withErrors(['lines' => 'Total debits must equal total credits. (Unbalanced by ' . abs($totalDebit - $totalCredit) . ')']);
        }

        DB::transaction(function () use ($validated) {
            $journalEntry = JournalEntry::create([
                'company_id' => Auth::user()->company_id,
                'user_id' => Auth::id(),
                'transaction_date' => $validated['transaction_date'],
                'reference_no' => $validated['reference_no'],
                'description' => $validated['description'],
            ]);

            LogActivity::log('Accounting', 'Created', "Recorded Manual Journal Entry (Ref: " . ($journalEntry->reference_no ?? 'None') . ")", $journalEntry);

            foreach ($validated['lines'] as $line) {
                if ($line['debit'] > 0 || $line['credit'] > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'chart_of_account_id' => $line['chart_of_account_id'],
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                        'memo' => $line['memo'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('journal-entries.index')->with('success', 'Journal entry recorded successfully.');
    }

    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load(['lines.chartOfAccount', 'user']);
        return Inertia::render('Accounting/JournalEntries/Show', [
            'journalEntry' => $journalEntry
        ]);
    }
}
