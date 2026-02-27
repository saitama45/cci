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
        Schema::create('bank_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained()->onDelete('cascade');
            // Polymorphic relation to link to either a Payment (Receipt) or Disbursement (Payment Voucher)
            $table->nullableMorphs('transaction'); 
            $table->date('transaction_date');
            $table->string('type'); // Payment, Disbursement, Bank Fee, Interest
            $table->string('reference_no')->nullable(); // Check #, PV #, OR #
            $table->string('description')->nullable();
            $table->decimal('amount', 18, 4); // Positive for receipt, Negative for disbursement
            $table->boolean('is_cleared')->default(false);
            $table->date('cleared_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_lines');
    }
};
