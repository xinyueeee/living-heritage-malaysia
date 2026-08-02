<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interest', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreignId('interest_id')->constrained('interest', 'interest_id')->onDelete('cascade');
            $table->timestamp('selected_date')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->primary(['user_id', 'interest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interest');
    }
};