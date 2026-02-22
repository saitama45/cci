<?php

namespace App\Http\Controllers;

use App\Models\ContractedSale;
use App\Models\PaymentSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ContractedSalesController extends Controller
{
    /**
     * Reprice the interest rate and regenerate remaining schedules.
     */
    public function reprice(Request $request, $id)
    {
        $sale = ContractedSale::findOrFail($id);

        $validated = $request->validate([
            'new_interest_rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'repricing_config' => 'required|array',
        ]);

        DB::transaction(function () use ($sale, $validated) {
            // 1. Store the new global config
            $sale->update([
                'repricing_config' => $validated['repricing_config']
            ]);

            // 2. Identify all schedules that are PENDING
            $pendingSchedules = $sale->paymentSchedules()
                ->where('status', 'Pending')
                ->orderBy('due_date', 'asc')
                ->get();

            if ($pendingSchedules->isEmpty()) {
                return;
            }

            // 3. We need the principal balance EXACTLY BEFORE the first pending schedule
            // To be safe, we calculate it as: Loanable Amount - Principal Paid
            $totalPrincipalPaid = (float) $sale->paymentSchedules()
                ->where('type', 'Amortization')
                ->where('status', 'Paid')
                ->sum('principal');
            
            $runningBalance = (float) $sale->loanable_amount - $totalPrincipalPaid;
            $totalTerms = $sale->terms_month;
            $startDate = $sale->start_date;

            // Group schedules by contract year to apply specific rates
            $schedulesByYear = [];
            foreach ($pendingSchedules as $s) {
                // Determine year: diff in months from contract start_date
                $monthsFromStart = $startDate->diffInMonths($s->due_date);
                $year = floor($monthsFromStart / 12) + 1;
                $schedulesByYear[$year][] = $s;
            }

            // 4. Process year by year
            foreach ($schedulesByYear as $year => $schedules) {
                // Find rate for this specific year from config
                $yearConfig = collect($validated['repricing_config'])->firstWhere('year', (int)$year);
                $annualRate = $yearConfig ? (float)$yearConfig['rate'] : (float)$validated['new_interest_rate'];
                $monthlyRate = ($annualRate / 100) / 12;

                // For Factor Rate formula, we need remaining months from the START of this specific group
                $firstInGroup = $schedules[0];
                $monthsElapsed = $startDate->diffInMonths($firstInGroup->due_date);
                $remainingMonths = $totalTerms - $monthsElapsed;

                // Calculate MA for this year's segment
                if ($monthlyRate > 0) {
                    $periodMA = $runningBalance * ($monthlyRate * pow(1 + $monthlyRate, $remainingMonths)) / (pow(1 + $monthlyRate, $remainingMonths) - 1);
                } else {
                    $periodMA = $runningBalance / $remainingMonths;
                }

                // Update all schedules in this year
                foreach ($schedules as $schedule) {
                    $interestComponent = $runningBalance * $monthlyRate;
                    $principalComponent = $periodMA - $interestComponent;

                    // Final adjustment for the very last installment of the contract
                    if ($schedule->installment_no == $totalTerms) {
                        $principalComponent = $runningBalance;
                        $actualTotalDue = $principalComponent + $interestComponent;
                    } else {
                        $actualTotalDue = $periodMA;
                    }

                    $runningBalance -= $principalComponent;

                    $schedule->update([
                        'amount_due' => $actualTotalDue,
                        'principal' => $principalComponent,
                        'interest' => $interestComponent,
                        'remaining_balance' => max(0, $runningBalance),
                        'remarks' => "Year $year Rate: $annualRate%"
                    ]);
                }

                // Update the sale's current MA/Rate if this is the immediate upcoming year
                if ($year == floor($startDate->diffInMonths(now()) / 12) + 1) {
                    $sale->update([
                        'interest_rate' => $annualRate,
                        'monthly_amortization' => $periodMA
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Full interest schedule recalculated successfully.');
    }

    /**
     * Display a listing of contracted sales.
     */
    public function index(Request $request)
    {
        $query = ContractedSale::with(['customer', 'unit.project', 'unit.priceList']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contract_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('unit', function($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
            });
        }

        $sales = $query->latest()->paginate($request->per_page ?? 10)->withQueryString();

        return Inertia::render('Sales/ContractedSales/Index', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }

    /**
     * Display the specified contracted sale details including payments and schedules.
     */
    public function show($id)
    {
        $sale = ContractedSale::with([
            'customer', 
            'unit.project', 
            'unit.priceList',
            'payments' => fn($q) => $q->latest('payment_date'),
            'paymentSchedules' => fn($q) => $q->orderBy('due_date', 'asc')
        ])->findOrFail($id);

        return Inertia::render('Sales/ContractedSales/Show', [
            'sale' => $sale
        ]);
    }
}
