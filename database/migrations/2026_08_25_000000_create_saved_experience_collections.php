<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_experience_collections', function (Blueprint $table) {
            $table->id('collection_id');
            $table->uuid('user_id');
            $table->string('name', 80);
            $table->string('normalized_name', 80);
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'normalized_name'], 'saved_collections_user_name_unique');
        });

        Schema::table('favourite', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->after('experience_id')
                ->constrained('saved_experience_collections', 'collection_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('favourite', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collection_id');
        });
        Schema::dropIfExists('saved_experience_collections');
    }
};
