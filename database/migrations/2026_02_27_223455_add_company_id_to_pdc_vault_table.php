<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pdc_vault', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('no action');
        });

        // Data Fix: Populate company_id from associated disbursements
        DB::statement("
            UPDATE pdc_vault 
            SET company_id = disbursements.company_id 
            FROM pdc_vault 
            INNER JOIN disbursements ON pdc_vault.disbursement_id = disbursements.id
            WHERE pdc_vault.company_id IS NULL AND pdc_vault.disbursement_id IS NOT NULL
        ");

        // If still null (unlikely but safe), use default company
        $defaultCompany = DB::table('companies')->first();
        if ($defaultCompany) {
            DB::table('pdc_vault')->whereNull('company_id')->update(['company_id' => $defaultCompany->id]);
        }

        // Now make it non-nullable if needed, but keeping nullable for flex is safer in migrations sometimes.
        // Let's keep it for now.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdc_vault', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
