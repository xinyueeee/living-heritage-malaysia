<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropUnique('trip_plans_user_id_trip_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'trip_date'],
                'trip_plans_user_id_trip_date_unique'
            );
        });
    }
};