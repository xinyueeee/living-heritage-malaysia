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
        Schema::create('experiences', function (Blueprint $table) {
            $table->id('experiences_id');
            $table->string('experiences_title', 200);
            $table->text('experiences_desc');
            $table->string('experiences_location', 100);
            $table->string('experiences_category', 100);
            $table->string('experiences_image_url', 255);
            $table->decimal('experiences_price', 10, 2);
            $table->string('experiences_duration', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->date('experiences_start_date')->nullable();
            $table->date('experiences_end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
