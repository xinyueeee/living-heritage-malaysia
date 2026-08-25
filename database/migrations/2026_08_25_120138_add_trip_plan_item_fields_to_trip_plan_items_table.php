<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plan_items', function (Blueprint $table) {

            $table->unsignedBigInteger('trip_plan_id')
                ->after('id');

            $table->unsignedBigInteger('experience_id')
                ->after('trip_plan_id');

            $table->string('item_type')
                ->after('experience_id');

            $table->integer('display_order')
                ->default(0)
                ->after('item_type');

            $table->foreign('trip_plan_id')
                ->references('id')
                ->on('trip_plans')
                ->onDelete('cascade');

            $table->foreign('experience_id')
                ->references('experiences_id')
                ->on('experiences')
                ->onDelete('cascade');

            $table->unique([
                'trip_plan_id',
                'experience_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('trip_plan_items', function (Blueprint $table) {

            $table->dropForeign([
                'trip_plan_id'
            ]);

            $table->dropForeign([
                'experience_id'
            ]);

            $table->dropUnique(
                'trip_plan_items_trip_plan_id_experience_id_unique'
            );

            $table->dropColumn([
                'trip_plan_id',
                'experience_id',
                'item_type',
                'display_order'
            ]);
        });
    }
};