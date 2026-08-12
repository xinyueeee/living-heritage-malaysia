<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passport_stamp', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('stamp_id')
                ->constrained('category', 'category_id')
                ->nullOnDelete();

            $table->unique(
                'category_id',
                'passport_stamp_category_unique'
            );
        });

        Schema::table('user_passport_stamp', function (Blueprint $table) {
            $table->foreignId('completed_exp_id')
                ->nullable()
                ->after('stamp_id')
                ->constrained(
                    'completed_experience',
                    'completed_exp_id'
                )
                ->nullOnDelete();

            $table->unique(
                ['passport_id', 'stamp_id'],
                'user_passport_unique_stamp'
            );
        });
    }

    public function down(): void
    {
        Schema::table('user_passport_stamp', function (Blueprint $table) {
            $table->dropUnique('user_passport_unique_stamp');
            $table->dropConstrainedForeignId('completed_exp_id');
        });

        Schema::table('passport_stamp', function (Blueprint $table) {
            $table->dropUnique('passport_stamp_category_unique');
            $table->dropConstrainedForeignId('category_id');
        });
    }
};