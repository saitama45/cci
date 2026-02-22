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
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('unit_id')->constrained();
            
            $table->string('type'); // 'Downpayment' or 'Amortization'
            $table->integer('installment_no');
            $table->date('due_date');
            $table->decimal('amount_due', 15, 4);
            $table->decimal('amount_paid', 15, 4)->default(0);
            $table->string('status')->default('Pending'); // 'Pending', 'Partially Paid', 'Paid', 'Overdue'
            
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
