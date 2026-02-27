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
                'debit' => round($accounts->sum('total_debit'), 2),
                'credit' => round($accounts->sum('total_credit'), 2),
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
                'debit' => round($accounts->sum('total_debit'), 2),
                'credit' => round($accounts->sum('total_credit'), 2),
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

    /**
     * AR Aging Report: Shows overdue payments from customers.
     */
    public function arAging(Request $request)
    {
        $asOfDate = $request->as_of_date ?: now()->format('Y-m-d');
        $data = $this->getAgingReportData($asOfDate);

        return Inertia::render('Accounting/AgingReport', [
            'report_data' => $data['report_data'],
            'filters' => [
                'as_of_date' => $asOfDate,
            ],
            'totals' => $data['totals']
        ]);
    }

    /**
     * AP Aging Report: Shows unpaid bills to vendors categorized by due date.
     */
    public function apAging(Request $request)
    {
        $asOfDate = $request->as_of_date ?: now()->format('Y-m-d');
        $data = $this->getAPAgingData($asOfDate);

        return Inertia::render('Accounting/APAgingReport', [
            'report_data' => $data['report_data'],
            'filters' => ['as_of_date' => $asOfDate],
            'totals' => $data['totals']
        ]);
    }

    /**
     * Project P&L: Shows Profit and Loss per Project.
     */
    public function projectPL(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        
        $startDate = $request->start_date ?: now()->startOfYear()->format('Y-m-d');
        $endDate = $request->end_date ?: now()->format('Y-m-d');

        $data = $this->getProjectPLData($companyId, $startDate, $endDate);

        return Inertia::render('Accounting/ProjectPL', [
            'projects' => $data['projects'],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => $data['totals']
        ]);
    }

    private function getAPAgingData($asOfDate)
    {
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $asOf = \Carbon\Carbon::parse($asOfDate)->endOfDay();

        $bills = \App\Models\Bill::with(['vendor', 'project'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['Approved', 'Partial', 'Overdue'])
            ->get();

        $reportData = $bills->map(function ($bill) use ($asOf) {
            // For now, let's assume unpaid_amount = total_amount since we haven't implemented Disbursement fully
            // Once Disbursement is added, we'd subtract payments.
            $unpaid = (float)$bill->total_amount; 
            
            $daysOverdue = 0;
            if ($bill->due_date && \Carbon\Carbon::parse($bill->due_date)->isBefore($asOf)) {
                $daysOverdue = (int) \Carbon\Carbon::parse($bill->due_date)->diffInDays($asOf);
            }

            return (object)[
                'bill_id' => $bill->id,
                'vendor_name' => $bill->vendor->name ?? 'N/A',
                'bill_number' => $bill->bill_number,
                'project_name' => $bill->project->name ?? 'General',
                'due_date' => $bill->due_date ? $bill->due_date->format('Y-m-d') : 'N/A',
                'unpaid_amount' => $unpaid,
                'current' => $daysOverdue === 0 ? $unpaid : 0,
                '1_30' => ($daysOverdue > 0 && $daysOverdue <= 30) ? $unpaid : 0,
                '31_60' => ($daysOverdue > 30 && $daysOverdue <= 60) ? $unpaid : 0,
                '61_90' => ($daysOverdue > 60 && $daysOverdue <= 90) ? $unpaid : 0,
                '91_over' => ($daysOverdue > 90) ? $unpaid : 0,
                'days_overdue' => $daysOverdue
            ];
        })->filter(fn($r) => $r->unpaid_amount > 0)->values();

        return [
            'report_data' => $reportData,
            'totals' => [
                'current' => $reportData->sum('current'),
                '1_30' => $reportData->sum('1_30'),
                '31_60' => $reportData->sum('31_60'),
                '61_90' => $reportData->sum('61_90'),
                '91_over' => $reportData->sum('91_over'),
                'total' => $reportData->sum('unpaid_amount'),
            ]
        ];
    }

    private function getProjectPLData($companyId, $startDate, $endDate)
    {
        $projects = \App\Models\Project::where('company_id', $companyId)->where('is_active', true)->get();
        
        $reportData = $projects->map(function ($project) use ($companyId, $startDate, $endDate) {
            // 1. Actuals from General Ledger
            $lines = JournalEntryLine::where('project_id', $project->id)
                ->whereHas('journalEntry', function($q) use ($companyId, $startDate, $endDate) {
                    $q->where('company_id', $companyId)
                      ->whereDate('transaction_date', '>=', $startDate)
                      ->whereDate('transaction_date', '<=', $endDate);
                })
                ->with('chartOfAccount')
                ->get();

            $actualRevenue = 0;
            $actualExpenses = 0;

            foreach ($lines as $line) {
                if ($line->chartOfAccount->type === 'revenue') {
                    $actualRevenue += ($line->credit - $line->debit);
                } elseif (in_array($line->chartOfAccount->type, ['expense', 'asset'])) {
                    $actualExpenses += ($line->debit - $line->credit);
                }
            }

            // 2. Committed Costs (Approved POs not yet fully billed)
            // We only look at POs created within the period or still open
            $committed = \App\Models\PurchaseOrder::where('project_id', $project->id)
                ->where('company_id', $companyId)
                ->whereIn('status', ['Approved', 'Partially Billed'])
                ->get()
                ->sum(function($po) {
                    // For each PO, the commitment is the remaining balance
                    $totalOrdered = $po->total_amount;
                    // We calculate billed amount from linked bills to get precise remaining liability
                    $billedAmount = $po->bills()->sum('total_amount');
                    return max(0, $totalOrdered - $billedAmount);
                });

            // 3. Budgets
            $totalBudget = \App\Models\ProjectBudget::where('project_id', $project->id)
                ->where('company_id', $companyId)
                ->sum('allocated_amount');

            return (object)[
                'id' => $project->id,
                'name' => $project->name,
                'budget' => (float)$totalBudget,
                'revenue' => (float)$actualRevenue,
                'expenses' => (float)$actualExpenses,
                'committed' => (float)$committed,
                'total_cost_projected' => (float)$actualExpenses + (float)$committed,
                'net_profit' => (float)$actualRevenue - (float)$actualExpenses,
                'variance' => $totalBudget > 0 ? $totalBudget - ($actualExpenses + $committed) : 0,
            ];
        })->filter(fn($p) => $p->revenue != 0 || $p->expenses != 0 || $p->budget != 0)->values();

        return [
            'projects' => $reportData,
            'totals' => [
                'budget' => $reportData->sum('budget'),
                'revenue' => $reportData->sum('revenue'),
                'expenses' => $reportData->sum('expenses'),
                'committed' => $reportData->sum('committed'),
                'net_profit' => $reportData->sum('net_profit'),
            ]
        ];
    }

    /**
     * Overall Receivables Summary Report
     */
    public function overallReceivables(Request $request)
    {
        $asOfDate = $request->as_of_date ?: now()->format('Y-m-d');
        $data = $this->getOverallReceivablesData($asOfDate);

        return Inertia::render('Accounting/OverallReceivables', [
            'outstanding_report' => $data['outstanding_report'],
            'installment_report' => $data['installment_report'],
            'filters' => ['as_of_date' => $asOfDate],
            'summary' => $data['summary']
        ]);
    }

    public function exportOverallReceivables(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $company = \App\Models\Company::find($companyId);
        
        $asOfDate = $request->as_of_date ?: now()->format('Y-m-d');
        $data = $this->getOverallReceivablesData($asOfDate);
        
        $pdf = Pdf::loadView('pdf.overall-receivables', [
            'outstanding_report' => $data['outstanding_report'],
            'installment_report' => $data['installment_report'],
            'summary' => $data['summary'],
            'asOfDate' => $asOfDate,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('overall-receivables-' . $asOfDate . '.pdf');
    }

    private function getOverallReceivablesData($asOfDate)
    {
        $asOf = \Carbon\Carbon::parse($asOfDate)->endOfDay();
        $companyId = Auth::user()->company_id ?: (\App\Models\Company::first()->id ?? 1);

        $contracts = \App\Models\ContractedSale::with(['customer', 'paymentSchedules'])
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();

        // 1. Initialize Buckets
        $outstandingBuckets = [
            'Not Yet Due' => ['amount' => 0, 'count' => 0],
            'CURRENT'     => ['amount' => 0, 'count' => 0],
            '31-60'       => ['amount' => 0, 'count' => 0],
            '61-90'       => ['amount' => 0, 'count' => 0],
            '91-120'      => ['amount' => 0, 'count' => 0],
            '121-OVER'    => ['amount' => 0, 'count' => 0],
        ];

        $installmentBuckets = [
            'CURRENT'     => ['amount' => 0, 'count' => 0],
            '31-60'       => ['amount' => 0, 'count' => 0],
            '61-90'       => ['amount' => 0, 'count' => 0],
            '91-120'      => ['amount' => 0, 'count' => 0],
            '121-OVER'    => ['amount' => 0, 'count' => 0],
        ];

        foreach ($contracts as $contract) {
            $totalBalance = 0;
            $maxDaysOverdue = 0;
            $hasOverdue = false;

            // Analyze schedules for this contract
            foreach ($contract->paymentSchedules as $s) {
                if ($s->type !== 'Amortization') continue;
                
                $unpaid = (float)$s->amount_due - (float)$s->amount_paid;
                if ($unpaid <= 0) continue;

                $totalBalance += $unpaid;
                $dueDate = \Carbon\Carbon::parse($s->due_date);

                if ($dueDate->isBefore($asOf)) {
                    $hasOverdue = true;
                    $days = $dueDate->diffInDays($asOf);
                    if ($days > $maxDaysOverdue) $maxDaysOverdue = $days;

                    // Aggregate Installment Due directly into buckets
                    $instKey = $this->getAgingKey($days, false);
                    $installmentBuckets[$instKey]['amount'] += $unpaid;
                    $installmentBuckets[$instKey]['count']++;
                }
            }

            if ($totalBalance <= 0) continue;

            // Outstanding Balance Report: Group the WHOLE balance of the account into its worst bucket
            $obKey = $hasOverdue ? $this->getAgingKey($maxDaysOverdue, false) : 'Not Yet Due';
            $outstandingBuckets[$obKey]['amount'] += $totalBalance;
            $outstandingBuckets[$obKey]['count']++;
        }

        // Calculate Totals
        $totalOB = array_sum(array_column($outstandingBuckets, 'amount'));
        $totalInst = array_sum(array_column($installmentBuckets, 'amount'));

        return [
            'outstanding_report' => $outstandingBuckets,
            'installment_report' => $installmentBuckets,
            'summary' => [
                'total_outstanding' => $totalOB,
                'total_installment' => $totalInst,
                'outstanding_accounts' => array_sum(array_column($outstandingBuckets, 'count')),
                'installment_accounts' => array_sum(array_column($installmentBuckets, 'count')),
            ]
        ];
    }

    private function getAgingKey($days, $includeNotYetDue = true)
    {
        if ($days <= 0) return $includeNotYetDue ? 'Not Yet Due' : 'CURRENT';
        if ($days <= 30) return 'CURRENT';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        if ($days <= 120) return '91-120';
        return '121-OVER';
    }

    public function exportAgingReport(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $company = \App\Models\Company::find($companyId);
        
        $asOfDate = $request->as_of_date ?: now()->format('Y-m-d');
        $data = $this->getAgingReportData($asOfDate);
        
        $pdf = Pdf::loadView('pdf.aging-report', [
            'reportData' => $data['report_data'],
            'totals' => $data['totals'],
            'asOfDate' => $asOfDate,
            'company' => $company,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('ar-aging-report-as-of-' . $asOfDate . '.pdf');
    }

    public function exportAPAging(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $company = \App\Models\Company::find($companyId);
        
        $asOfDate = $request->as_of_date ?: now()->format('Y-m-d');
        $data = $this->getAPAgingData($asOfDate);
        
        $pdf = Pdf::loadView('pdf.ap-aging', [
            'report_data' => $data['report_data'],
            'totals' => $data['totals'],
            'asOfDate' => $asOfDate,
            'company' => $company,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('ap-aging-report-as-of-' . $asOfDate . '.pdf');
    }

    public function exportProjectPL(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $company = \App\Models\Company::find($companyId);
        
        $startDate = $request->start_date ?: now()->startOfYear()->format('Y-m-d');
        $endDate = $request->end_date ?: now()->format('Y-m-d');

        $data = $this->getProjectPLData($companyId, $startDate, $endDate);
        
        $pdf = Pdf::loadView('pdf.project-pl', [
            'projects' => $data['projects'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('project-pl-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    private function getAgingReportData($asOfDate)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?: (\App\Models\Company::first()->id ?? 1);
        $asOf = \Carbon\Carbon::parse($asOfDate)->endOfDay();

        $contracts = \App\Models\ContractedSale::with(['customer', 'unit.project', 'paymentSchedules', 'payments'])
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();

        $reportData = $contracts->map(function ($contract) use ($asOf) {
            // Only process Amortization schedules
            $schedules = $contract->paymentSchedules->where('type', 'Amortization');
            
            // Only consider Amortization payments for last pay date
            $lastAmortPayment = $contract->payments
                ->where('payment_type', 'Amortization')
                ->sortByDesc('payment_date')
                ->first();
            
            $aging = [
                'not_yet_due' => 0,
                '1_30' => 0,
                '31_60' => 0,
                '61_90' => 0,
                '91_120' => 0,
                '120_over' => 0,
                'total_due' => 0,
                'outstanding_balance' => 0,
                'max_days_overdue' => 0,
            ];

            foreach ($schedules as $s) {
                $unpaid = (float)$s->amount_due - (float)$s->amount_paid;
                if ($unpaid <= 0) continue;

                $aging['outstanding_balance'] += $unpaid;
                
                $dueDate = \Carbon\Carbon::parse($s->due_date);
                
                if ($dueDate->isAfter($asOf)) {
                    $aging['not_yet_due'] += $unpaid;
                } else {
                    $daysOverdue = (int) $dueDate->diffInDays($asOf);
                    $aging['total_due'] += $unpaid;
                    
                    if ($daysOverdue > $aging['max_days_overdue']) {
                        $aging['max_days_overdue'] = $daysOverdue;
                    }

                    if ($daysOverdue <= 30) $aging['1_30'] += $unpaid;
                    elseif ($daysOverdue <= 60) $aging['31_60'] += $unpaid;
                    elseif ($daysOverdue <= 90) $aging['61_90'] += $unpaid;
                    elseif ($daysOverdue <= 120) $aging['91_120'] += $unpaid;
                    else $aging['120_over'] += $unpaid;
                }
            }

            $bracket = 'Current';
            if ($aging['max_days_overdue'] > 120) $bracket = '120+ Over';
            elseif ($aging['max_days_overdue'] > 90) $bracket = '91-120 Days';
            elseif ($aging['max_days_overdue'] > 60) $bracket = '61-90 Days';
            elseif ($aging['max_days_overdue'] > 30) $bracket = '31-60 Days';
            elseif ($aging['max_days_overdue'] > 0) $bracket = '1-30 Days';

            return (object)[
                'contract_id' => $contract->id,
                'customer_name' => $contract->customer->full_name,
                'unit_name' => $contract->unit->name . ' (' . ($contract->unit->project->name ?? 'N/A') . ')',
                'last_pay_date' => $lastAmortPayment ? $lastAmortPayment->payment_date->format('Y-m-d') : 'No Payment',
                'not_yet_due' => $aging['not_yet_due'],
                '1_30' => $aging['1_30'],
                '31_60' => $aging['31_60'],
                '61_90' => $aging['61_90'],
                '91_120' => $aging['91_120'],
                '120_over' => $aging['120_over'],
                'total_due' => $aging['total_due'],
                'outstanding_balance' => $aging['outstanding_balance'],
                'aging_days' => (int) $aging['max_days_overdue'],
                'aging_bracket' => $bracket
            ];
        })->filter(fn($r) => $r->outstanding_balance > 0)->values();

        return [
            'report_data' => $reportData,
            'totals' => [
                'not_yet_due' => $reportData->sum('not_yet_due'),
                '1_30' => $reportData->sum('1_30'),
                '31_60' => $reportData->sum('31_60'),
                '61_90' => $reportData->sum('61_90'),
                '91_120' => $reportData->sum('91_120'),
                '120_over' => $reportData->sum('120_over'),
                'total_due' => $reportData->sum('total_due'),
                'outstanding_balance' => $reportData->sum('outstanding_balance'),
            ]
        ];
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
