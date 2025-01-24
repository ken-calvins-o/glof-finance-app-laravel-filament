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
        Schema::create('monthly_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('month_id')->nullable()->constrained('months');
            $table->foreignId('receivable_id')->nullable()->constrained('receivables');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_receivable');
    }
};
