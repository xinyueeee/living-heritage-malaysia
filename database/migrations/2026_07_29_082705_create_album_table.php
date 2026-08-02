<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('album', function (Blueprint $table) {
            $table->id('album_id');
            $table->uuid('user_id');
            $table->string('album_name', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->text('images')->nullable();
            $table->timestamp('created_at')->useCurrent();
 
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('album');
    }
};
