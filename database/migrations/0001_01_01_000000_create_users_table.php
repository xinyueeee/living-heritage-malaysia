<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('user_name', 100)->nullable();
            $table->string('user_email', 100)->nullable();
            $table->string('profile_photo', 255)->nullable();
            $table->text('bio')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
 
            // Links this row to Supabase Auth's internal auth.users table
            $table->foreign('user_id')
                  ->references('id')->on('auth.users')
                  ->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
