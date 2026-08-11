<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id('profile_photo_id');
            $table->uuid('user_id');
            $table->string('photo_url', 255);
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });

        // Block direct anon-key access via Supabase's REST API; Laravel connects
        // via the postgres role and is unaffected by RLS.
        DB::statement('ALTER TABLE public.profile_photos ENABLE ROW LEVEL SECURITY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_photos');
    }
};
