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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('tin')->nullable();
            $table->string('email')->unique()->nullable();
            $table->boolean('maceda_status')->default(false);
            
            // Address Fields
            $table->string('home_no_street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('zip_code')->nullable();

            // Additional Info
            $table->string('gender')->nullable(); // Male, Female, Other
            $table->string('civil_status')->nullable(); // Single, Married, Divorced, Widowed
            $table->string('profile_photo')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
