<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
{
    Schema::create('trip_plan_items', function (Blueprint $table) {
        $table->id('trip_plan_item_id');

        $table->unsignedBigInteger('trip_plan_id');

        $table->unsignedBigInteger('experience_id');

        $table->string('item_type');

        $table->integer('display_order')
              ->default(0);

        $table->timestamps();

        $table->foreign('trip_plan_id')
              ->references('trip_plan_id')
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_plan_items');
    }
};
