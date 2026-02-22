<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class AccountingReportController extends Controller
{
    /**
     * Trial Balance: Shows total Debits and Credits for each account.
     */
    public function trialBalance(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        $startDate = $request->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?: now()->endOfMonth()->format('Y-m-d');

        $accounts = $this->getTrialBalanceData($companyId, $startDate, $endDate);

        return Inertia::render('Accounting/TrialBalance', [
            'accounts' => $accounts,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'debit' => $accounts->sum('total_debit'),
                'credit' => $accounts->sum('total_credit'),
            ]
        ]);
    }

    public function exportTrialBalance(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $company = \App\Models\Company::find($companyId);
        
        $startDate = $request->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?: now()->endOfMonth()->format('Y-m-d');

        $accounts = $this->getTrialBalanceData($companyId, $startDate, $endDate);
        
        $pdf = Pdf::loadView('pdf.trial-balance', [
            'accounts' => $accounts,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'company' => $company,
            'totals' => [
                'debit' => $accounts->sum('total_debit'),
                'credit' => $accounts->sum('total_credit'),
            ]
        ]);

        return $pdf->stream('trial-balance-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    private function getTrialBalanceData($companyId, $startDate, $endDate)
    {
        return ChartOfAccount::where('company_id', $companyId)
            ->with(['journalEntryLines' => function($query) use ($startDate, $endDate) {
                $query->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                    $q->whereDate('transaction_date', '>=', $startDate)
                      ->whereDate('transaction_date', '<=', $endDate);
                });
            }])
            ->get()
            ->map(function ($account) {
                $totalDebit = (float) $account->journalEntryLines->sum('debit');
                $totalCredit = (float) $account->journalEntryLines->sum('credit');
                
                // Calculate netted display values
                $displayDebit = 0;
                $displayCredit = 0;

                if ($totalDebit > $totalCredit) {
                    $displayDebit = $totalDebit - $totalCredit;
                } elseif ($totalCredit > $totalDebit) {
                    $displayCredit = $totalCredit - $totalDebit;
                }

                return (object)[
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'total_debit' => $displayDebit,
                    'total_credit' => $displayCredit,
                    'balance' => in_array($account->type, ['asset', 'expense']) 
                        ? ($totalDebit - $totalCredit) 
                        : ($totalCredit - $totalDebit),
                ];
            })
            ->filter(fn($a) => $a->total_debit != 0 || $a->total_credit != 0)
            ->values();
    }

    /**
     * General Ledger: Shows detailed transactions for each account.
     */
    public function generalLedger(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);

        $startDate = $request->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?: now()->endOfMonth()->format('Y-m-d');
        
        $data = $this->getGeneralLedgerData($companyId, $startDate, $endDate, $request->account_id);

        return Inertia::render('Accounting/GeneralLedger', [
            'ledger_lines' => $data['lines'],
            'beginning_balances' => $data['beginning_balances'],
            'accounts' => ChartOfAccount::where('company_id', $companyId)->get(),
            'filters' => [
                'account_id' => $request->account_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function exportGeneralLedger(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $company = \App\Models\Company::find($companyId);

        $startDate = $request->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?: now()->endOfMonth()->format('Y-m-d');
        
        $data = $this->getGeneralLedgerData($companyId, $startDate, $endDate, $request->account_id);
        $lines = $data['lines'];
        $beginningBalances = $data['beginning_balances'];

        // Group by account for the PDF
        $grouped = [];
        $accountsById = ChartOfAccount::where('company_id', $companyId)->get()->keyBy('id');

        foreach ($lines as $line) {
            $account = $line->chartOfAccount;
            $key = $account->code . ' - ' . $account->name;
            
            if (!isset($grouped[$key])) {
                $begBal = $beginningBalances[$account->id] ?? 0;
                $grouped[$key] = [
                    'account' => $account,
                    'lines' => [], 
                    'total_debit' => 0, 
                    'total_credit' => 0,
                    'beginning_balance' => $begBal,
                    'running_balance' => $begBal
                ];
            }

            $debit = (float)$line->debit;
            $credit = (float)$line->credit;

            // Calculate running balance based on account type
            $isNormalDebit = in_array($line->chartOfAccount->type, ['asset', 'expense']);
            if ($isNormalDebit) {
                $grouped[$key]['running_balance'] += ($debit - $credit);
            } else {
                $grouped[$key]['running_balance'] += ($credit - $debit);
            }

            $line->current_balance = $grouped[$key]['running_balance'];
            
            $grouped[$key]['lines'][] = $line;
            $grouped[$key]['total_debit'] += $debit;
            $grouped[$key]['total_credit'] += $credit;
        }

        // Include accounts that have a beginning balance but no activity in the period
        if (!$request->account_id) {
            foreach ($beginningBalances as $accountId => $balance) {
                if ($balance != 0) {
                    $account = $accountsById->get($accountId);
                    if ($account) {
                        $key = $account->code . ' - ' . $account->name;
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'account' => $account,
                                'lines' => [],
                                'total_debit' => 0,
                                'total_credit' => 0,
                                'beginning_balance' => $balance,
                                'running_balance' => $balance
                            ];
                        }
                    }
                }
            }
        }

        $pdf = Pdf::loadView('pdf.general-ledger', [
            'groupedLedger' => $grouped,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'company' => $company,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('general-ledger-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    private function getGeneralLedgerData($companyId, $startDate, $endDate, $accountId = null)
    {
        // 1. Calculate Beginning Balances
        $beginningBalances = [];
        $accountsQuery = ChartOfAccount::where('company_id', $companyId);
        if ($accountId) {
            $accountsQuery->where('id', $accountId);
        }
        $accounts = $accountsQuery->get();

        // Optimized beginning balance calculation
        $prevTotals = JournalEntryLine::select('chart_of_account_id')
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereHas('journalEntry', function($q) use ($startDate) {
                $q->whereDate('transaction_date', '<', $startDate);
            })
            ->whereHas('chartOfAccount', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        foreach ($accounts as $account) {
            $totals = $prevTotals->get($account->id);
            $totalDebit = $totals ? (float)$totals->total_debit : 0;
            $totalCredit = $totals ? (float)$totals->total_credit : 0;

            $balance = in_array($account->type, ['asset', 'expense'])
                ? ($totalDebit - $totalCredit)
                : ($totalCredit - $totalDebit);
            
            $beginningBalances[$account->id] = (float)$balance;
        }

        // 2. Get Period Lines
        $query = JournalEntryLine::with(['journalEntry', 'chartOfAccount'])
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->select('journal_entry_lines.*') // Avoid column name collision
            ->whereHas('chartOfAccount', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('journal_entries.transaction_date', '>=', $startDate)
            ->whereDate('journal_entries.transaction_date', '<=', $endDate)
            ->orderBy('journal_entries.transaction_date')
            ->orderBy('journal_entry_lines.id');

        if ($accountId) {
            $query->where('journal_entry_lines.chart_of_account_id', $accountId);
        }

        $lines = $query->get();

        return [
            'lines' => $lines,
            'beginning_balances' => $beginningBalances
        ];
    }
}
