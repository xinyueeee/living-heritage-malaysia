<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_passport_stamp', function (Blueprint $table) {
            $table->timestamp('notified_at')
                ->nullable()
                ->after('collected_date');
        });

        Schema::table('user_achievement', function (Blueprint $table) {
            $table->timestamp('notified_at')
                ->nullable()
                ->after('unlocked_date');
        });
    }

    public function down(): void
    {
        Schema::table('user_passport_stamp', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });

        Schema::table('user_achievement', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};