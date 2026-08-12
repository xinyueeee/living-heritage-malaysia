<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'user_passport_stamp',
            function (Blueprint $table) {
                $table->unsignedInteger('page_number')
                    ->default(1);

                $table->decimal('position_x', 5, 2)
                    ->default(10);

                $table->decimal('position_y', 5, 2)
                    ->default(10);

                $table->decimal('rotation', 6, 2)
                    ->default(0);

                $table->decimal('scale', 4, 2)
                    ->default(1);

                $table->unsignedInteger('z_index')
                    ->default(1);
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'user_passport_stamp',
            function (Blueprint $table) {
                $table->dropColumn([
                    'page_number',
                    'position_x',
                    'position_y',
                    'rotation',
                    'scale',
                    'z_index',
                ]);
            }
        );
    }
};