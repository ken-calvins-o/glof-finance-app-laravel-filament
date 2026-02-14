<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivable_effects', function (Blueprint $table) {
            if (!Schema::hasColumn('receivable_effects', 'reverted')) {
                $table->boolean('reverted')->default(false)->after('debt_created_by_receivable');
            }

            if (!Schema::hasColumn('receivable_effects', 'reverted_at')) {
                $table->timestamp('reverted_at')->nullable()->after('reverted');
            }

            if (!Schema::hasColumn('receivable_effects', 'reverted_by')) {
                $table->unsignedBigInteger('reverted_by')->nullable()->after('reverted_at');
            }

            if (!Schema::hasColumn('receivable_effects', 'reversal_saving_ids')) {
                $table->json('reversal_saving_ids')->nullable()->after('deletion_reversal_saving_id');
            }

            if (!Schema::hasColumn('receivable_effects', 'saving_snapshots')) {
                $table->json('saving_snapshots')->nullable()->after('saving_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receivable_effects', function (Blueprint $table) {
            if (Schema::hasColumn('receivable_effects', 'reverted')) {
                $table->dropColumn('reverted');
            }

            if (Schema::hasColumn('receivable_effects', 'reverted_at')) {
                $table->dropColumn('reverted_at');
            }

            if (Schema::hasColumn('receivable_effects', 'reverted_by')) {
                $table->dropColumn('reverted_by');
            }

            if (Schema::hasColumn('receivable_effects', 'reversal_saving_ids')) {
                $table->dropColumn('reversal_saving_ids');
            }

            if (Schema::hasColumn('receivable_effects', 'saving_snapshots')) {
                $table->dropColumn('saving_snapshots');
            }
        });
    }
};

