<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('feedback_photo');
        Schema::dropIfExists('feedback');
    }

    public function down(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->uuid('user_id');
            $table->tinyInteger('rating')->nullable();
            $table->text('feedback_message')->nullable();
            $table->timestamp('submitted_at')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });

        Schema::create('feedback_photo', function (Blueprint $table) {
            $table->id('photo_id');
            $table->foreignId('feedback_id')->constrained('feedback', 'feedback_id')->onDelete('cascade');
            $table->string('file_name', 255)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }
};
