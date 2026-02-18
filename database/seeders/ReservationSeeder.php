<?php

namespace Database\Seeders;

use App\Models\Broker;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample brokers if none exist
        if (Broker::count() === 0) {
            Broker::create(['name' => 'John Doe Real Estate', 'commission_rate' => 5.00, 'prc_license' => '12345']);
            Broker::create(['name' => 'Elite Realty Inc.', 'commission_rate' => 4.50, 'prc_license' => '67890']);
        }

        $customers = Customer::all();
        $units = Unit::where('status', 'Available')->take(3)->get();
        $brokers = Broker::all();

        if ($customers->isEmpty() || $units->isEmpty()) {
            return;
        }

        // Reservation 1: Active
        if ($units->get(0)) {
            Reservation::create([
                'customer_id' => $customers->first()->id,
                'unit_id' => $units->get(0)->id,
                'broker_id' => $brokers->first()->id,
                'reservation_date' => now()->subDays(5),
                'expiry_date' => now()->addDays(25),
                'fee' => 25000,
            ]);
            $units->get(0)->update(['status' => 'Reserved']);
        }

        // Reservation 2: Expiring Soon
        if ($units->get(1)) {
            Reservation::create([
                'customer_id' => $customers->last()->id,
                'unit_id' => $units->get(1)->id,
                'broker_id' => null,
                'reservation_date' => now()->subDays(28),
                'expiry_date' => now()->addDays(2),
                'fee' => 25000,
            ]);
            $units->get(1)->update(['status' => 'Reserved']);
        }

        // Reservation 3: Expired
        if ($units->get(2)) {
            Reservation::create([
                'customer_id' => $customers->get(1)->id ?? $customers->first()->id,
                'unit_id' => $units->get(2)->id,
                'broker_id' => $brokers->last()->id,
                'reservation_date' => now()->subDays(45),
                'expiry_date' => now()->subDays(15),
                'fee' => 30000,
            ]);
            $units->get(2)->update(['status' => 'Reserved']);
        }
    }
}
