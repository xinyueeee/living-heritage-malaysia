<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'experience_view_history_user_experience_unique';

    private const DEDUPLICATION_INDEX = 'experience_view_history_user_experience_viewed_index';

    private const TRENDING_INDEX = 'experience_view_history_experience_viewed_index';

    public function up(): void
    {
        Schema::table('experience_view_history', function (Blueprint $table) {
            $table->dropUnique(self::UNIQUE_INDEX);
            $table->index(
                ['user_id', 'experience_id', 'viewed_at'],
                self::DEDUPLICATION_INDEX,
            );
            $table->index(
                ['experience_id', 'viewed_at'],
                self::TRENDING_INDEX,
            );
        });
    }

    public function down(): void
    {
        $hasRepeatedViews = DB::table('experience_view_history')
            ->select(['user_id', 'experience_id'])
            ->groupBy('user_id', 'experience_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasRepeatedViews) {
            throw new \RuntimeException(
                'Cannot restore the unique view-history constraint while repeated meaningful views exist.'
            );
        }

        Schema::table('experience_view_history', function (Blueprint $table) {
            $table->dropIndex(self::DEDUPLICATION_INDEX);
            $table->dropIndex(self::TRENDING_INDEX);
            $table->unique(
                ['user_id', 'experience_id'],
                self::UNIQUE_INDEX,
            );
        });
    }
};
