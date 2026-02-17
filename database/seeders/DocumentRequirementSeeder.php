<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use Illuminate\Database\Seeder;

class DocumentRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requirements = [
            // Basic/Identity
            [
                'name' => 'Valid Government ID (Primary)',
                'description' => 'Passport, Driver\'s License, UMID, or any primary government ID.',
                'is_required' => true,
                'category' => 'Identity',
                'sort_order' => 1,
            ],
            [
                'name' => 'Valid Government ID (Secondary)',
                'description' => 'Secondary ID for verification.',
                'is_required' => true,
                'category' => 'Identity',
                'sort_order' => 2,
            ],
            [
                'name' => 'TIN Verification',
                'description' => 'BIR Form 1904, TIN Card, or latest ITR showing TIN.',
                'is_required' => true,
                'category' => 'Identity',
                'sort_order' => 3,
            ],

            // Income
            [
                'name' => 'Certificate of Employment',
                'description' => 'Latest COE with compensation details.',
                'is_required' => false,
                'category' => 'Income',
                'sort_order' => 10,
            ],
            [
                'name' => 'Latest 3 Months Payslips',
                'description' => 'For employed individuals.',
                'is_required' => false,
                'category' => 'Income',
                'sort_order' => 11,
            ],
            [
                'name' => 'Latest Income Tax Return (ITR)',
                'description' => 'BIR Form 2316 or equivalent.',
                'is_required' => false,
                'category' => 'Income',
                'sort_order' => 12,
            ],

            // Address & Civil Status
            [
                'name' => 'Proof of Billing',
                'description' => 'Latest utility bill (Meralco, Water, etc.) not older than 3 months.',
                'is_required' => true,
                'category' => 'Address',
                'sort_order' => 20,
            ],
            [
                'name' => 'PSA Birth Certificate',
                'description' => 'Required for single buyers.',
                'is_required' => false,
                'category' => 'Civil Status',
                'sort_order' => 30,
            ],
            [
                'name' => 'PSA Marriage Contract',
                'description' => 'Required for married buyers.',
                'is_required' => false,
                'category' => 'Civil Status',
                'sort_order' => 31,
            ],

            // Special
            [
                'name' => 'Consularized SPA',
                'description' => 'Required for OFW buyers represented by an attorney-in-fact.',
                'is_required' => false,
                'category' => 'Special',
                'sort_order' => 40,
            ],
        ];

        foreach ($requirements as $req) {
            DocumentRequirement::updateOrCreate(
                ['name' => $req['name']],
                $req
            );
        }
    }
}
