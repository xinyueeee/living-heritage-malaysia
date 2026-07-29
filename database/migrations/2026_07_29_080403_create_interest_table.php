<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest', function (Blueprint $table) {
            $table->id('interest_id');
            $table->string('interest_name', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest');
    }
};