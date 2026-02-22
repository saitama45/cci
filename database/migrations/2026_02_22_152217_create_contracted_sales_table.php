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
        Schema::create('contracted_sales', function (Blueprint $table) {
            $table->id();
            $table->string('contract_no')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('reservation_id')->constrained()->onDelete('no action');
            $table->foreignId('customer_id')->constrained()->onDelete('no action');
            $table->foreignId('unit_id')->constrained()->onDelete('no action');
            
            $table->decimal('tcp', 18, 4);
            $table->decimal('downpayment_paid', 18, 4);
            $table->decimal('loanable_amount', 18, 4); // Principal
            $table->decimal('interest_rate', 8, 4)->default(0); // Annual interest rate
            $table->integer('terms_month');
            $table->decimal('monthly_amortization', 18, 4);
            
            $table->date('start_date');
            $table->string('status')->default('Active'); // Active, Fully Paid, Cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracted_sales');
    }
};
