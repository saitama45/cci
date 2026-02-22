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
        Schema::table('vendors', function (Blueprint $table) {
            $table->enum('verification_status', ['Pending', 'Verified', 'Blacklisted'])->default('Pending')->after('category');
            $table->string('payment_terms')->nullable()->comment('e.g., Net 30, Due on Receipt')->after('verification_status');
            $table->foreignId('default_expense_account_id')->nullable()->after('payment_terms')->constrained('chart_of_accounts')->noActionOnDelete();
        });

        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., BIR 2303, Business Permit
            $table->string('file_path');
            $table->string('file_name');
            $table->string('category')->default('Compliance');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_expense_account_id');
            $table->dropColumn(['verification_status', 'payment_terms']);
        });
    }
};
