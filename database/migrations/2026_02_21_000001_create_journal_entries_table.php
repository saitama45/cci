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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // created_by
            $table->dateTime('transaction_date');
            $table->string('reference_no')->nullable()->index(); // OR #, Receipt #, Inv #
            $table->string('referenceable_type')->nullable(); // Polymorphic
            $table->unsignedBigInteger('referenceable_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['referenceable_type', 'referenceable_id']);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('no action');
            $table->decimal('debit', 18, 4)->default(0.00);
            $table->decimal('credit', 18, 4)->default(0.00);
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
