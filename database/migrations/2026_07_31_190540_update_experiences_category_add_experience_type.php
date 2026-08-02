```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================
        // 1. CREATE EXPERIENCE TYPE TABLE
        // ============================================
        Schema::create('experience_type', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name', 50)->unique();
        });

        DB::table('experience_type')->insert([
            [
                'type_id' => 1,
                'type_name' => 'Cultural Experience',
            ],
            [
                'type_id' => 2,
                'type_name' => 'Festival',
            ],
        ]);


        // ============================================
        // 2. MODIFY CATEGORY TABLE
        // ============================================
        Schema::table('category', function (Blueprint $table) {
            $table->integer('type_id')->nullable();
        });


        // ============================================
        // 3. ASSIGN CATEGORY TYPES
        // ============================================
        DB::table('category')
            ->whereIn('category_id', [1, 2, 3, 4, 5, 6])
            ->update(['type_id' => 1]);

        DB::table('category')
            ->whereIn('category_id', [7, 8, 9, 10, 11, 12, 13])
            ->update(['type_id' => 2]);


        // ============================================
        // 4. MAKE CATEGORY TYPE_ID REQUIRED
        // ============================================
        Schema::table('category', function (Blueprint $table) {
            $table->integer('type_id')->nullable(false)->change();

            $table->foreign('type_id')
                ->references('type_id')
                ->on('experience_type');
        });


        // ============================================
        // 5. RENAME EXPERIENCES COLUMNS
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->renameColumn(
                'experiences_title',
                'experiences_name'
            );

            $table->renameColumn(
                'experiences_desc',
                'description'
            );

            $table->renameColumn(
                'experiences_location',
                'location_name'
            );

            $table->renameColumn(
                'experiences_image_url',
                'image_url'
            );

            $table->renameColumn(
                'experiences_price',
                'price'
            );

            $table->renameColumn(
                'experiences_duration',
                'duration'
            );

            $table->renameColumn(
                'experiences_start_date',
                'start_date'
            );

            $table->renameColumn(
                'experiences_end_date',
                'end_date'
            );
        });


        // ============================================
        // 6. CHANGE EXISTING EXPERIENCES COLUMNS
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->text('description')
                ->nullable()
                ->change();

            $table->string('location_name', 100)
                ->nullable()
                ->change();

            $table->string('image_url', 255)
                ->nullable()
                ->change();

            $table->decimal('price', 10, 2)
                ->nullable()
                ->change();

            $table->string('duration', 50)
                ->nullable()
                ->change();
        });


        // ============================================
        // 7. REMOVE OLD CATEGORY COLUMN
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn('experiences_category');
        });


        // ============================================
        // 8. ADD NEW EXPERIENCES COLUMNS
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->string('operating_hours')
                ->nullable();

            $table->integer('type_id')
                ->nullable();

            $table->integer('category_id')
                ->nullable();

            $table->decimal('latitude', 10, 8)
                ->nullable();

            $table->decimal('longitude', 11, 8)
                ->nullable();

            $table->string('contact_number', 20)
                ->nullable();

            $table->string('status', 20)
                ->nullable()
                ->default('Available');

            $table->timestamp('updated_at')
                ->nullable()
                ->default(DB::raw('CURRENT_TIMESTAMP'));
        });


        // ============================================
        // 9. UPDATE EXISTING EXPERIENCES
        // ============================================
        // Existing experiences are Festival experiences.
        // Their existing category_id values are preserved.
        DB::table('experiences')->update([
            'type_id' => 2,
            'status' => 'Available',
        ]);


        // ============================================
        // 10. MAKE REQUIRED COLUMNS NOT NULL
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->integer('type_id')
                ->nullable(false)
                ->change();

            $table->integer('category_id')
                ->nullable(false)
                ->change();

            $table->string('status', 20)
                ->nullable(false)
                ->change();
        });


        // ============================================
        // 11. ADD FOREIGN KEYS
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->foreign('type_id')
                ->references('type_id')
                ->on('experience_type');

            $table->foreign('category_id')
                ->references('category_id')
                ->on('category');
        });


        // ============================================
        // 12. CREATE INDEXES
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->index(
                'type_id',
                'idx_experiences_type'
            );

            $table->index(
                'category_id',
                'idx_experiences_category'
            );

            $table->index(
                'status',
                'idx_experiences_status'
            );

            $table->index(
                ['latitude', 'longitude'],
                'idx_experiences_location'
            );

            $table->index(
                'start_date',
                'idx_experiences_start_date'
            );
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ============================================
        // 1. DROP INDEXES
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropIndex('idx_experiences_type');
            $table->dropIndex('idx_experiences_category');
            $table->dropIndex('idx_experiences_status');
            $table->dropIndex('idx_experiences_location');
            $table->dropIndex('idx_experiences_start_date');
        });


        // ============================================
        // 2. DROP EXPERIENCES FOREIGN KEYS
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['category_id']);
        });


        // ============================================
        // 3. DROP NEW EXPERIENCES COLUMNS
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn([
                'operating_hours',
                'type_id',
                'category_id',
                'latitude',
                'longitude',
                'contact_number',
                'status',
                'updated_at',
            ]);
        });


        // ============================================
        // 4. RESTORE OLD CATEGORY COLUMN
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->string(
                'experiences_category',
                100
            )->nullable();
        });


        // ============================================
        // 5. RENAME EXPERIENCES COLUMNS BACK
        // ============================================
        Schema::table('experiences', function (Blueprint $table) {
            $table->renameColumn(
                'experiences_name',
                'experiences_title'
            );

            $table->renameColumn(
                'description',
                'experiences_desc'
            );

            $table->renameColumn(
                'location_name',
                'experiences_location'
            );

            $table->renameColumn(
                'image_url',
                'experiences_image_url'
            );

            $table->renameColumn(
                'price',
                'experiences_price'
            );

            $table->renameColumn(
                'duration',
                'experiences_duration'
            );

            $table->renameColumn(
                'start_date',
                'experiences_start_date'
            );

            $table->renameColumn(
                'end_date',
                'experiences_end_date'
            );
        });


        // ============================================
        // 6. DROP CATEGORY FOREIGN KEY
        // ============================================
        Schema::table('category', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
        });


        // ============================================
        // 7. DROP EXPERIENCE TYPE TABLE
        // ============================================
        Schema::dropIfExists('experience_type');
    }
};