<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record where a collection came from.
 *
 * Repaying a fund debt is money coming in, but it was only ever written to the
 * debt, the fund's running total and the savings ledger — never as a collection.
 * So a member who paid 4,000, was charged 5,000 and then settled the 1,000
 * shortfall showed 4,000 collected, and the repayment appeared on none of the
 * screens that read collections.
 *
 * Repayments are recorded as collections now, and this column distinguishes
 * them: a repayment has already done its own bookkeeping inside
 * DebtRepaymentService, so it must not be reversed as though it were an
 * ordinary entry, and it reads more honestly on screen labelled for what it is.
 *
 * Nullable, so every existing row keeps meaning exactly what it meant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->string('source')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
