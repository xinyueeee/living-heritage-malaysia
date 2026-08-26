<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {

            $table->uuid('user_id')
                ->after('trip_plan_id');

            $table->date('trip_date')
                ->after('user_id');

            $table->string('status')
                ->default('active')
                ->after('trip_date');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique([
                'user_id',
                'trip_date'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {

            $table->dropForeign([
                'user_id'
            ]);

            $table->dropUnique([
                'trip_plans_user_id_trip_date_unique'
            ]);

            $table->dropColumn([
                'user_id',
                'trip_date',
                'status'
            ]);
        });
    }
};