<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_passport_stamp', function (Blueprint $table) {
            $table->id('user_stamp_id');
            $table->foreignId('passport_id')->constrained('digital_cultural_passport', 'passport_id')->onDelete('cascade');
            $table->foreignId('stamp_id')->constrained('passport_stamp', 'stamp_id');
            $table->timestamp('collected_date')->useCurrent();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('user_passport_stamp');
    }
};