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
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->foreignId('contracted_sale_id')->nullable()->after('reservation_id')->constrained()->onDelete('cascade');
            $table->decimal('principal', 18, 4)->default(0)->after('amount_due');
            $table->decimal('interest', 18, 4)->default(0)->after('principal');
            $table->decimal('remaining_balance', 18, 4)->default(0)->after('interest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contracted_sale_id');
            $table->dropColumn(['principal', 'interest', 'remaining_balance']);
        });
    }
};
