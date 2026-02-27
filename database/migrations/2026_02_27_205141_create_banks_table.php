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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique(); // e.g. BDO, BPI, METROBANK
            $table->string('branch')->nullable();
            $table->json('cheque_config')->nullable(); // Store field coordinates
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add bank_id to pdc_vault for better relational integrity
        Schema::table('pdc_vault', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('check_date')->constrained('banks')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdc_vault', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
        });
        Schema::dropIfExists('banks');
    }
};
