<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DebtStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('account_id')->nullable();
            $table->decimal('outstanding_balance', 10, 2);
            $table->decimal('repayment_amount', 10, 2)->default(0.00)->nullable();
            $table->boolean('from_savings')->default(false);
            $table->dateTime('due_date')->nullable();
            $table->string('debt_status')->default(DebtStatusEnum::Pending);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
