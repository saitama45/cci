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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('no action');
            $table->string('bill_number')->nullable()->index(); // Invoice # from vendor
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 18, 4)->default(0.00);
            $table->enum('status', ['Draft', 'Pending', 'Approved', 'Partial', 'Paid', 'Overdue', 'Cancelled'])->default('Draft');
            $table->text('notes')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('no action'); // Main project link
            $table->foreignId('journal_entry_id')->nullable()->constrained()->onDelete('no action');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('no action');
            $table->timestamps();
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained()->onDelete('no action');
            $table->string('description')->nullable();
            $table->decimal('amount', 18, 4)->default(0.00);
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('no action');
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
    }
};
