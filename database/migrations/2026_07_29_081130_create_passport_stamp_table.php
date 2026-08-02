<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_stamp', function (Blueprint $table) {
            $table->id('stamp_id');
            $table->string('state', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('stamp_image', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_stamp');
    }
};