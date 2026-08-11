<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->renameColumn('date_time', 'scheduled_at');
            $table->text('message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->renameColumn('scheduled_at', 'date_time');
            $table->dropColumn('message');
        });
    }
};