<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivable_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained('receivables')->onDelete('cascade');

            // Account collection snapshot
            $table->foreignId('account_collection_id')->nullable()->constrained('account_collections')->nullOnDelete();
            $table->decimal('account_collection_prev_amount', 14, 2)->nullable();

            // Savings entries created by the original receivable (JSON array of ids)
            $table->json('saving_ids')->nullable();

            // If we created a reversal saving when deleting the receivable, store that id
            $table->unsignedBigInteger('deletion_reversal_saving_id')->nullable();

            // Debt metadata
            $table->unsignedBigInteger('debt_id')->nullable();
            $table->decimal('debt_prev_outstanding', 14, 2)->nullable();
            $table->boolean('debt_created_by_receivable')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_effects');
    }
};

