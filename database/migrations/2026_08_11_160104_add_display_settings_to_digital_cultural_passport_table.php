<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'digital_cultural_passport',
            function (Blueprint $table) {
                $table->string('display_theme', 30)
                    ->default('heritage');

                $table->string('display_layout', 30)
                    ->default('book');

                $table->boolean('show_stamp_details')
                    ->default(true);
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'digital_cultural_passport',
            function (Blueprint $table) {
                $table->dropColumn([
                    'display_theme',
                    'display_layout',
                    'show_stamp_details',
                ]);
            }
        );
    }
};