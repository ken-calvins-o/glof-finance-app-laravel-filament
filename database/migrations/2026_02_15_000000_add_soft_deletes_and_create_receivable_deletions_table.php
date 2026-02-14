<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesAndCreateReceivableDeletionsTable extends Migration
{
    public function up(): void
    {
        // This was an empty/placeholder migration file that caused the migrator to fail.
        // Leave as a safe no-op so existing deployments that accidentally committed this file won't break migrations.
        // The actual migrations we want are created in separate files.
    }

    public function down(): void
    {
        // no-op
    }
}

