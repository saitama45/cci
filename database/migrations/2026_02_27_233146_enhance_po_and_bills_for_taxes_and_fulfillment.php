<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance Purchase Orders for Taxes
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('tax_type')->default('VAT Exclusive')->after('total_amount'); // VAT Inclusive, VAT Exclusive, Non-VAT
            $table->decimal('vat_amount', 18, 4)->default(0)->after('tax_type');
            $table->decimal('ewt_rate', 5, 2)->default(0)->after('vat_amount'); // e.g. 1.00, 2.00
            $table->decimal('ewt_amount', 18, 4)->default(0)->after('ewt_rate');
            $table->decimal('net_amount', 18, 4)->default(0)->after('ewt_amount'); // Total after Taxes
        });

        // 2. Track Partial Fulfillment in PO Items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('quantity_billed', 18, 4)->default(0)->after('quantity');
        });

        // 3. Enhance Bills for Taxes
        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('vat_amount', 18, 4)->default(0)->after('total_amount');
            $table->decimal('ewt_amount', 18, 4)->default(0)->after('vat_amount');
            $table->decimal('net_amount', 18, 4)->default(0)->after('ewt_amount');
        });

        // 4. Project Budgets Table (For the Intelligence Widget)
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained()->onDelete('no action'); // e.g. Construction Materials
            $table->decimal('allocated_amount', 18, 4);
            $table->decimal('spent_amount', 18, 4)->default(0);
            $table->decimal('committed_amount', 18, 4)->default(0); // POs that are approved but not billed
            $table->timestamps();
            
            $table->unique(['project_id', 'chart_of_account_id'], 'project_account_budget_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
        
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['vat_amount', 'ewt_amount', 'net_amount']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('quantity_billed');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'vat_amount', 'ewt_rate', 'ewt_amount', 'net_amount']);
        });
    }
};
