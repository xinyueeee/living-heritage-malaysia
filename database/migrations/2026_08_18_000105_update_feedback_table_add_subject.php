<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('rating');
            $table->renameColumn('feedback_message', 'description');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->string('subject')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('subject');
            $table->renameColumn('description', 'feedback_message');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->tinyInteger('rating')->nullable();
        });
    }
};
