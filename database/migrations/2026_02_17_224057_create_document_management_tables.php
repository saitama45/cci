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
        // 1. Configure the types of documents required
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Valid ID", "Latest Payslip"
            $table->string('description')->nullable(); // Instructions for the customer
            $table->boolean('is_required')->default(false); // Can't proceed without this
            $table->string('category')->default('Basic'); // Identity, Income, Address, Civil Status, etc.
            $table->boolean('status')->default(true); // Active or Inactive
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Store actual customer uploads
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_requirement_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->text('remarks')->nullable(); // Reason for rejection
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('document_requirements');
    }
};
