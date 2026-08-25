<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personalized_alert_sent', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('experiences_id');
            $table->timestamp('sent_at')->useCurrent();

            $table->unique(['user_id', 'experiences_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalized_alert_sent');
    }
};