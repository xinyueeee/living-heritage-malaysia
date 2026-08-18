<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience_view_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreignId('experience_id');
            $table->timestamp('viewed_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('experience_id')
                ->references('experiences_id')
                ->on('experiences')
                ->cascadeOnDelete();
            $table->unique(
                ['user_id', 'experience_id'],
                'experience_view_history_user_experience_unique'
            );
            $table->index(['user_id', 'viewed_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."experience_view_history" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_view_history');
    }
};
