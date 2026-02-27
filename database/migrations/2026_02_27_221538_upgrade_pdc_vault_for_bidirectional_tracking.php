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
            $table->string('type')->default('Outward')->after('id'); // Inward (Receipt), Outward (Payment)
            $table->foreignId('payment_id')->nullable()->after('disbursement_id')->constrained('payments')->onDelete('no action');
            $table->foreignId('customer_id')->nullable()->after('payment_id')->constrained('customers')->onDelete('no action');
            $table->foreignId('vendor_id')->nullable()->after('customer_id')->constrained('vendors')->onDelete('no action');
        });

        // Set existing records to 'Outward' (they are all from Disbursements so far)
        DB::table('pdc_vault')->update(['type' => 'Outward']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdc_vault', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['payment_id']);
            $table->dropColumn(['type', 'payment_id', 'customer_id', 'vendor_id']);
        });
    }
};
