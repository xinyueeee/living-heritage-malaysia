<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favourite', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'experience_id'],
                'favourite_user_experience_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('favourite', function (Blueprint $table) {
            $table->dropUnique('favourite_user_experience_unique');
        });
    }
};
