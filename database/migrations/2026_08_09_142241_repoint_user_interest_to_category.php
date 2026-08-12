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
     * "Interests" are just the same category names used by the Experiences
     * module, so this repoints user_interest at category directly instead of
     * the separate (and never-populated) interest table.
     */
    public function up(): void
    {
        Schema::dropIfExists('user_interest');
        Schema::dropIfExists('interest');

        Schema::create('user_interest', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreignId('category_id')->constrained('category', 'category_id')->onDelete('cascade');
            $table->timestamp('selected_date')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->primary(['user_id', 'category_id']);
        });

        DB::statement('ALTER TABLE public.user_interest ENABLE ROW LEVEL SECURITY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_interest');

        Schema::create('interest', function (Blueprint $table) {
            $table->id('interest_id');
            $table->string('interest_name', 100);
        });

        Schema::create('user_interest', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreignId('interest_id')->constrained('interest', 'interest_id')->onDelete('cascade');
            $table->timestamp('selected_date')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->primary(['user_id', 'interest_id']);
        });
    }
};
