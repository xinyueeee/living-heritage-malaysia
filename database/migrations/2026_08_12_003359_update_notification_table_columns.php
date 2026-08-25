<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the old VARCHAR default first
        DB::statement('
            ALTER TABLE notification
            ALTER COLUMN is_read DROP DEFAULT
        ');

        // Change VARCHAR to BOOLEAN
        DB::statement('
            ALTER TABLE notification
            ALTER COLUMN is_read TYPE BOOLEAN
            USING CASE
                WHEN is_read = \'1\' THEN TRUE
                WHEN is_read = \'true\' THEN TRUE
                ELSE FALSE
            END
        ');

        // Set new Boolean default
        DB::statement('
            ALTER TABLE notification
            ALTER COLUMN is_read SET DEFAULT FALSE
        ');

        // Add created_at and updated_at
        Schema::table('notification', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        DB::statement('
            ALTER TABLE notification
            ALTER COLUMN is_read DROP DEFAULT
        ');

        DB::statement('
            ALTER TABLE notification
            ALTER COLUMN is_read TYPE VARCHAR(255)
            USING CASE
                WHEN is_read = TRUE THEN \'1\'
                ELSE \'0\'
            END
        ');

        DB::statement('
            ALTER TABLE notification
            ALTER COLUMN is_read SET DEFAULT \'0\'
        ');
    }
};