<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->date('selected_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->dropColumn('selected_date');
        });
    }
};