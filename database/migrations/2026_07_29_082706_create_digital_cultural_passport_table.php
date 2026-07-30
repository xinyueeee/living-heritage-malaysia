<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_cultural_passport', function (Blueprint $table) {
            $table->id('passport_id');
            $table->uuid('user_id');
            $table->date('created_date')->useCurrent();
 
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('digital_cultural_passport');
    }
};