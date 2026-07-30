<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert', function (Blueprint $table) {
            $table->id('alert_id');
            $table->uuid('user_id');
            $table->foreignId('category_id')->constrained('category', 'category_id');
            $table->timestamp('created_date')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert');
    }
};