<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors the favourite (saved experiences) table pattern for saved
     * community posts, since the two are separate concepts (experiences
     * come from Discover, posts come from Community).
     */
    public function up(): void
    {
        Schema::create('post_save', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreignId('post_id')->constrained('post', 'post_id')->onDelete('cascade');
            $table->timestamp('saved_at')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->primary(['user_id', 'post_id']);
        });

        DB::statement('ALTER TABLE public.post_save ENABLE ROW LEVEL SECURITY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_save');
    }
};
