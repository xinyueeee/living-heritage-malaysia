<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
public function up(): void
{
    Schema::create('trip_plans', function (Blueprint $table) {
        $table->id('trip_plan_id');

        $table->uuid('user_id');

        $table->date('trip_date');

        $table->string('status')
              ->default('active');

        $table->timestamps();

        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');

        $table->unique([
            'user_id',
            'trip_date'
        ]);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_plans');
    }
};
