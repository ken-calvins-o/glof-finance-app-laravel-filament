<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('account_id')->constrained('users')->onDelete('cascade');
            // Adds a `user_id` column with a foreign key reference to the `users` table
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drops the foreign key
            $table->dropColumn('user_id'); // Drops the `user_id` column
        });
    }
};
