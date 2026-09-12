<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('community_group', 'cover_image')) {
            Schema::table('community_group', function (Blueprint $table) {
                $table->string('cover_image')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('community_group', 'cover_image')) {
            Schema::table('community_group', function (Blueprint $table) {
                $table->dropColumn('cover_image');
            });
        }
    }
};