<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\SavedExperienceCollection;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SavedExperienceCollectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) { $table->uuid('user_id')->primary(); $table->string('user_name')->nullable(); });
        Schema::create('category', function (Blueprint $table) { $table->id('category_id'); $table->string('category_name'); });
        Schema::create('experience_type', function (Blueprint $table) { $table->id('type_id'); $table->string('type_name'); });
        Schema::create('experiences', function (Blueprint $table) {
            $table->id('experiences_id'); $table->string('experiences_name'); $table->text('description')->nullable();
            $table->string('short_description')->nullable(); $table->string('location_name')->nullable(); $table->string('image_url')->nullable();
            $table->decimal('price')->nullable(); $table->string('duration')->nullable(); $table->date('start_date')->nullable(); $table->date('end_date')->nullable();
            $table->unsignedBigInteger('category_id')->nullable(); $table->unsignedBigInteger('type_id')->nullable(); $table->timestamps();
        });
        Schema::create('saved_experience_collections', function (Blueprint $table) {
            $table->id('collection_id'); $table->uuid('user_id'); $table->string('name', 80); $table->string('normalized_name', 80); $table->timestamps();
            $table->unique(['user_id', 'normalized_name']);
        });
        Schema::create('favourite', function (Blueprint $table) {
            $table->id('favourite_id'); $table->uuid('user_id'); $table->unsignedBigInteger('experience_id');
            $table->unsignedBigInteger('collection_id')->nullable(); $table->date('saved_date')->useCurrent();
            $table->unique(['user_id', 'experience_id']);
        });
        Schema::create('notification', function (Blueprint $table) { $table->uuid('user_id'); $table->boolean('is_read')->default(false); });
    }

    public function test_null_collection_is_default_and_page_filters_it(): void
    {
        [$user, $first, $second] = $this->fixtures();
        DB::table('favourite')->insert([
            ['user_id' => $user->getKey(), 'experience_id' => $first->getKey(), 'collection_id' => null],
            ['user_id' => $user->getKey(), 'experience_id' => $second->getKey(), 'collection_id' => $this->collection($user, 'Concerts')->getKey()],
        ]);
        $this->actingAs($user)->get('/profile/saved-experiences?collection=default')->assertOk()->assertSee('Heritage Walk')->assertDontSee('Food Fair');
    }

    public function test_user_can_save_to_default_and_custom_collection_without_duplicates(): void
    {
        [$user, $experience] = $this->fixtures();
        $collection = $this->collection($user, 'Concerts');
        $this->actingAs($user)->post(route('experiences.saved.store', $experience), [])->assertRedirect();
        $this->actingAs($user)->post(route('experiences.saved.store', $experience), ['collection_id' => $collection->getKey()])->assertRedirect();
        $this->assertDatabaseCount('favourite', 1);
        $this->assertDatabaseHas('favourite', ['user_id' => $user->getKey(), 'experience_id' => $experience->getKey(), 'collection_id' => $collection->getKey()]);
    }

    public function test_collection_names_are_validated_reserved_and_case_insensitively_unique(): void
    {
        [$user] = $this->fixtures();
        $this->actingAs($user)->post(route('saved-experience-collections.store'), ['name' => '  Concerts  '])->assertRedirect();
        $this->actingAs($user)->post(route('saved-experience-collections.store'), ['name' => 'concerts'])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('saved-experience-collections.store'), ['name' => 'Default'])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('saved-experience-collections.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->assertDatabaseHas('saved_experience_collections', ['name' => 'Concerts', 'normalized_name' => 'concerts']);
    }

    public function test_different_users_may_use_same_collection_name(): void
    {
        [$first] = $this->fixtures(); $second = $this->user('user-b');
        $this->actingAs($first)->post(route('saved-experience-collections.store'), ['name' => 'Concerts']);
        $this->actingAs($second)->post(route('saved-experience-collections.store'), ['name' => 'Concerts'])->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('saved_experience_collections', 2);
    }

    public function test_owner_can_rename_but_another_user_cannot(): void
    {
        [$owner] = $this->fixtures(); $other = $this->user('user-b'); $collection = $this->collection($owner, 'Concerts');
        $this->actingAs($owner)->patch(route('saved-experience-collections.update', $collection), ['name' => 'Live Music'])->assertRedirect();
        $this->actingAs($other)->patch(route('saved-experience-collections.update', $collection), ['name' => 'Stolen'])->assertNotFound();
        $this->assertDatabaseHas('saved_experience_collections', ['collection_id' => $collection->getKey(), 'name' => 'Live Music']);
    }

    public function test_move_updates_existing_record_between_default_and_collections(): void
    {
        [$user, $experience] = $this->fixtures(); $one = $this->collection($user, 'One'); $two = $this->collection($user, 'Two');
        DB::table('favourite')->insert(['user_id' => $user->getKey(), 'experience_id' => $experience->getKey()]);
        foreach ([$one->getKey(), $two->getKey(), null] as $target) {
            $this->actingAs($user)->patch(route('experiences.saved.move', $experience), ['collection_id' => $target])->assertRedirect();
        }
        $this->assertDatabaseCount('favourite', 1);
        $this->assertDatabaseHas('favourite', ['experience_id' => $experience->getKey(), 'collection_id' => null]);
    }

    public function test_user_cannot_save_or_move_into_another_users_collection(): void
    {
        [$user, $experience] = $this->fixtures(); $other = $this->user('user-b'); $foreign = $this->collection($other, 'Private');
        $this->actingAs($user)->post(route('experiences.saved.store', $experience), ['collection_id' => $foreign->getKey()])->assertNotFound();
        $this->assertDatabaseMissing('favourite', ['user_id' => $user->getKey(), 'experience_id' => $experience->getKey()]);
        DB::table('favourite')->insert(['user_id' => $user->getKey(), 'experience_id' => $experience->getKey()]);
        $this->actingAs($user)->patch(route('experiences.saved.move', $experience), ['collection_id' => $foreign->getKey()])->assertNotFound();
        $this->assertDatabaseHas('favourite', [
            'user_id' => $user->getKey(),
            'experience_id' => $experience->getKey(),
            'collection_id' => null,
        ]);
    }

    public function test_rls_migration_defines_owner_only_crud_policies(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_25_010000_enable_rls_on_saved_experience_collections.php'));

        $this->assertStringContainsString('ENABLE ROW LEVEL SECURITY', $migration);
        $this->assertStringContainsString('FOR SELECT TO authenticated', $migration);
        $this->assertStringContainsString('FOR INSERT TO authenticated', $migration);
        $this->assertStringContainsString('FOR UPDATE TO authenticated', $migration);
        $this->assertStringContainsString('FOR DELETE TO authenticated', $migration);
        $this->assertSame(5, substr_count($migration, 'user_id = auth.uid()'));
        $this->assertStringNotContainsString('TO anon', $migration);
    }

    public function test_deleting_collection_moves_saved_experiences_to_default_without_deleting_them(): void
    {
        [$user, $experience] = $this->fixtures(); $collection = $this->collection($user, 'Concerts');
        DB::table('favourite')->insert(['user_id' => $user->getKey(), 'experience_id' => $experience->getKey(), 'collection_id' => $collection->getKey()]);
        $this->actingAs($user)->delete(route('saved-experience-collections.destroy', $collection))->assertRedirect(route('profile.saved-experiences'));
        $this->assertDatabaseMissing('saved_experience_collections', ['collection_id' => $collection->getKey()]);
        $this->assertDatabaseHas('favourite', ['experience_id' => $experience->getKey(), 'collection_id' => null]);
    }

    public function test_user_cannot_delete_another_users_collection_and_remove_still_works(): void
    {
        [$user, $experience] = $this->fixtures(); $other = $this->user('user-b'); $foreign = $this->collection($other, 'Private');
        $this->actingAs($user)->delete(route('saved-experience-collections.destroy', $foreign))->assertNotFound();
        DB::table('favourite')->insert(['user_id' => $user->getKey(), 'experience_id' => $experience->getKey()]);
        $this->actingAs($user)->delete(route('experiences.saved.destroy', $experience))->assertRedirect();
        $this->assertDatabaseMissing('favourite', ['user_id' => $user->getKey(), 'experience_id' => $experience->getKey()]);
    }

    public function test_saved_ui_uses_information_and_remove_confirmation_dialogs(): void
    {
        [$user, $experience] = $this->fixtures();
        $collection = $this->collection($user, 'Concerts');
        DB::table('favourite')->insert(['user_id' => $user->getKey(), 'experience_id' => $experience->getKey(), 'collection_id' => $collection->getKey()]);

        $page = $this->actingAs($user)->get(route('profile.saved-experiences'));
        $page->assertOk()
            ->assertSee('Remove saved experience?')
            ->assertSee('data-open-remove-saved', false)
            ->assertSee(route('experiences.saved.destroy', $experience), false)
            ->assertSee('View Saved Experiences')
            ->assertSee(route('profile.saved-experiences'), false);

        $names = app(\App\Services\Experience\SavedExperienceService::class)->getSavedExperienceCollectionNames($user);
        $this->assertSame('Concerts', $names[$experience->getKey()]);
        $this->assertDatabaseCount('favourite', 1);
    }

    public function test_null_collection_name_resolves_to_default(): void
    {
        [$user, $experience] = $this->fixtures();
        DB::table('favourite')->insert(['user_id' => $user->getKey(), 'experience_id' => $experience->getKey(), 'collection_id' => null]);

        $names = app(\App\Services\Experience\SavedExperienceService::class)->getSavedExperienceCollectionNames($user);
        $this->assertSame('Default', $names[$experience->getKey()]);
    }

    private function fixtures(): array
    {
        $user = $this->user('user-a');
        DB::table('experiences')->insert([['experiences_id' => 1, 'experiences_name' => 'Heritage Walk'], ['experiences_id' => 2, 'experiences_name' => 'Food Fair']]);
        return [$user, Experience::findOrFail(1), Experience::findOrFail(2)];
    }

    private function user(string $id): User
    {
        DB::table('users')->insert(['user_id' => $id, 'user_name' => $id]);
        return User::findOrFail($id);
    }

    private function collection(User $user, string $name): SavedExperienceCollection
    {
        return $user->savedExperienceCollections()->create(['name' => $name, 'normalized_name' => mb_strtolower($name)]);
    }
}
