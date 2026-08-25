<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE notification
            DROP CONSTRAINT IF EXISTS notification_is_read_check
        ');
    }

    public function down(): void
    {
        // No rollback needed for this old constraint
    }
};