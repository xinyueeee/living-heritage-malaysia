<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post', function (Blueprint $table) {
            $table->id('post_id');
            $table->uuid('user_id');
            $table->text('content')->nullable();
            $table->text('post_images')->nullable();
            $table->integer('like_count')->default(0);
            $table->string('comments', 255)->nullable();
            $table->text('saved_users')->nullable();
            $table->timestamp('created_at')->useCurrent();
 
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('post');
    }
};