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
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained()->onDelete('no action'); // The bank account
            $table->date('statement_date');
            $table->decimal('statement_ending_balance', 18, 4);
            $table->decimal('cleared_balance', 18, 4)->default(0);
            $table->decimal('difference', 18, 4)->default(0);
            $table->string('status')->default('Draft'); // Draft, Completed
            $table->foreignId('prepared_by')->nullable()->constrained('users')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
