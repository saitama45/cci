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
                
                $balance = 0;
                if (in_array($account->type, ['asset', 'expense'])) {
                    $balance = $totalDebit - $totalCredit;
                } else {
                    $balance = $totalCredit - $totalDebit;
                }

                return (object)[
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'balance' => $balance,
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
        
        $lines = $this->getGeneralLedgerData($companyId, $startDate, $endDate, $request->account_id);

        return Inertia::render('Accounting/GeneralLedger', [
            'ledger_lines' => $lines,
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
        
        $lines = $this->getGeneralLedgerData($companyId, $startDate, $endDate, $request->account_id);

        // Group by account for the PDF
        $grouped = [];
        foreach ($lines as $line) {
            $key = $line->chartOfAccount->code . ' - ' . $line->chartOfAccount->name;
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['lines' => [], 'total_debit' => 0, 'total_credit' => 0];
            }
            $grouped[$key]['lines'][] = $line;
            $grouped[$key]['total_debit'] += (float)$line->debit;
            $grouped[$key]['total_credit'] += (float)$line->credit;
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
        $query = JournalEntryLine::with(['journalEntry', 'chartOfAccount'])
            ->whereHas('chartOfAccount', fn($q) => $q->where('company_id', $companyId))
            ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                $q->whereDate('transaction_date', '>=', $startDate)
                  ->whereDate('transaction_date', '<=', $endDate);
            });

        if ($accountId) {
            $query->where('chart_of_account_id', $accountId);
        }

        return $query->get()
            ->sortBy(fn($line) => $line->journalEntry->transaction_date)
            ->values();
    }
}
