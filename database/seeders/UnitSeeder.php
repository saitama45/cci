<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            Project::firstOrCreate(['code' => 'P1'], ['name' => 'Legacy Estate', 'location' => 'San Jose']),
            Project::firstOrCreate(['code' => 'P2'], ['name' => 'Horizon Views', 'location' => 'Angeles']),
        ];

        foreach ($projects as $project) {
            for ($i = 1; $i <= 25; $i++) {
                Unit::firstOrCreate(
                    ['name' => "{$project->code}-U{$i}", 'project_id' => $project->id],
                    [
                        'block_num' => rand(1, 10),
                        'lot_num' => rand(1, 50),
                        'sqm_area' => rand(50, 200),
                        'status' => 'Available'
                    ]
                );
            }
        }
        
        $this->command->info('✅ Seeded 50 available units across 2 projects.');
    }
}
