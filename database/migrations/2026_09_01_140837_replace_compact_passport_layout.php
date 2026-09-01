<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('digital_cultural_passport')
            ->where('display_layout', 'compact')
            ->update([
                'display_layout' => 'book',
            ]);
    }

    public function down(): void
    {
        // The previous user choice cannot be reliably restored.
    }
};