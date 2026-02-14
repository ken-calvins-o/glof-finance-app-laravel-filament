<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivable_effects', function (Blueprint $table) {
            if (! Schema::hasColumn('receivable_effects', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('receivable_id');
            }

            if (! Schema::hasColumn('receivable_effects', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('receivable_effects', 'account_collection_post_amount')) {
                $table->decimal('account_collection_post_amount', 10, 2)->nullable()->after('account_collection_prev_amount');
            }

            // Add index for faster lookup
            if (! Schema::hasColumn('receivable_effects', 'idx_receivable_user_account')) {
                $table->index(['receivable_id', 'user_id', 'account_id'], 'idx_receivable_user_account');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receivable_effects', function (Blueprint $table) {
            if (Schema::hasColumn('receivable_effects', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('receivable_effects', 'account_id')) {
                $table->dropColumn('account_id');
            }

            if (Schema::hasColumn('receivable_effects', 'account_collection_post_amount')) {
                $table->dropColumn('account_collection_post_amount');
            }

            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn($i)=>$i->getName(), $sm->listTableIndexes('receivable_effects'));
            if (in_array('idx_receivable_user_account', $indexes)) {
                $table->dropIndex('idx_receivable_user_account');
            }
        });
    }
};

