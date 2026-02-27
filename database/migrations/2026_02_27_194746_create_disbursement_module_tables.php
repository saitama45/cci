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
        // 1. Disbursement / Payment Voucher Header
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('no action');
            $table->string('voucher_no')->unique(); // e.g. PV-2026-0001
            $table->date('payment_date');
            $table->string('payment_method'); // Cash, Check, Bank Transfer, PDC
            $table->foreignId('bank_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('no action');
            $table->decimal('total_amount', 18, 4);
            $table->string('status')->default('Draft'); // Draft, Approved, Cancelled, Paid
            $table->text('notes')->nullable();
            
            $table->foreignId('prepared_by')->nullable()->constrained('users')->onDelete('no action');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('no action');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('no action');
            
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('no action');
            $table->timestamps();
        });

        // 2. Disbursement Items (Mapping Voucher to Bills)
        Schema::create('disbursement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disbursement_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_id')->constrained()->onDelete('no action');
            $table->decimal('amount', 18, 4);
            $table->timestamps();
        });

        // 3. PDC Vault (Post-Dated Check Intelligence)
        Schema::create('pdc_vault', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disbursement_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('check_no');
            $table->date('check_date'); // Maturity Date
            $table->string('bank_name');
            $table->string('bank_branch')->nullable();
            $table->decimal('amount', 18, 4);
            $table->string('status')->default('Pending'); // Pending, Cleared, Bounced, Stale
            $table->date('cleared_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdc_vault');
        Schema::dropIfExists('disbursement_items');
        Schema::dropIfExists('disbursements');
    }
};
