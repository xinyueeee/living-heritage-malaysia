<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('keyword', 200)->nullable();
            $table->string('location', 100)->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('type_id')->nullable();
            $table->timestamp('searched_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('category_id')
                ->references('category_id')
                ->on('category')
                ->nullOnDelete();
            $table->foreign('type_id')
                ->references('type_id')
                ->on('experience_type')
                ->nullOnDelete();
            $table->index(['user_id', 'searched_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."search_history" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
