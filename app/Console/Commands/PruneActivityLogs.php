<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;
use Carbon\Carbon;

class PruneActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old activity logs based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // We'll use the default config or the first company's setting for global pruning
        $months = \App\Models\Setting::get('audit_retention_months', config('audit.retention_months', 6));
        $months = (int) $months;
        
        $date = Carbon::now()->subMonths($months);

        $this->info("Pruning activity logs older than {$months} months ({$date->toDateString()})...");

        $count = ActivityLog::where('created_at', '<', $date)->delete();

        $this->info("Successfully deleted {$count} old log entries.");
    }
}
