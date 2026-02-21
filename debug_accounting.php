<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ChartOfAccount;

echo "Total JEs: " . JournalEntry::count() . "
";
foreach(JournalEntry::all() as $je) {
    echo "ID: {$je->id} | Company: {$je->company_id} | Date: {$je->transaction_date}
";
}

echo "
Total Lines: " . JournalEntryLine::count() . "
";
echo "Total COAs: " . ChartOfAccount::count() . "
";
