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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('module')->index(); // e.g. 'Procurement', 'Sales', 'Admin'
            $table->string('action')->index(); // e.g. 'Created', 'Updated', 'Approved', 'Deleted'
            $table->text('description'); // e.g. 'Approved Disbursement PV-2026-001'
            
            // Polymorphic link to the record
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_type')->nullable();
            
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index(); // Indexed for pruning
            
            $table->index(['record_type', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
