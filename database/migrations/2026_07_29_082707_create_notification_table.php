<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->id('notification_id');
            $table->uuid('user_id');
            $table->foreignId('experience_id')->nullable()->constrained('experiences', 'experiences_id');
            $table->string('notification_type', 100)->nullable();
            $table->timestamp('date_time')->useCurrent();
            $table->enum('is_read', ['Seen', 'Unseen'])->default('Unseen');
 
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};