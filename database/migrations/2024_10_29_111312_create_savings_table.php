<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentMode;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('credit_amount', 10, 2)->default(0.00);
            $table->decimal('debit_amount', 10, 2)->default(0.00);
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('net_worth', 10, 2);
            $table->string('payment_method')->default(PaymentMode::Bank_Transfer);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};
