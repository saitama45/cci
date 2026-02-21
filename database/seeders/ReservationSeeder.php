<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\Company;
use App\Models\Payment;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ReservationSeeder extends Seeder
{
    public function run(AccountingService $accountingService): void
    {
        $company = Company::first();
        if (!$company) return;

        // Reset units to Available for seeding
        Unit::where('status', 'Reserved')->update(['status' => 'Available']);

        $customers = Customer::all();
        $units = Unit::where('status', 'Available')->get();

        if ($customers->isEmpty() || $units->isEmpty()) {
            $this->command->warn('Missing customers or available units. Skipping seeder.');
            return;
        }

        $this->command->info('Seeding 20 reservations with accounting entries in CURRENT MONTH...');

        for ($i = 0; $i < 20; $i++) {
            $customer = $customers->random();
            $unit = $units->pop(); 
            
            if (!$unit) break;

            DB::transaction(function () use ($customer, $unit, $company, $accountingService, $i) {
                // FORCE ALL TO CURRENT MONTH
                $reservationDate = now()->subDays(rand(0, 5)); 
                
                $reservation = Reservation::create([
                    'customer_id' => $customer->id,
                    'unit_id' => $unit->id,
                    'reservation_date' => $reservationDate,
                    'expiry_date' => (clone $reservationDate)->addDays(30),
                    'fee' => 25000 + (rand(0, 5) * 5000),
                    'status' => 'Active',
                ]);

                $unit->update(['status' => 'Reserved']);

                $payment = Payment::create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'reservation_id' => $reservation->id,
                    'amount' => $reservation->fee,
                    'payment_date' => $reservationDate,
                    'payment_method' => 'Bank Transfer',
                    'reference_no' => 'SEED-OR-' . (2000 + $i),
                ]);

                $accountingService->recordReservationFeeReceipt($payment);
                
                // Mark some as contracted
                if ($i % 3 === 0) {
                    $reservation->update(['status' => 'Contracted']);
                    $accountingService->recognizeRevenueFromReservation($reservation, $reservation->fee, 'SEED-JV-' . (6000 + $i));
                }
            });
        }
        
        $this->command->info('✅ Successfully seeded reservations and accounting data.');
    }
}
