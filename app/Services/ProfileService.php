<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProfileService
{
    private const PHOTO_BUCKET = 'profile-photos';
    /**
     * Personal-information fields that are allowed to be edited one at a time
     * from the Personal Information page.
     *
     * @var list<string>
     */
    private const EDITABLE_FIELDS = [
        'user_name',
        'bio',
        'gender',
        'birthday',
    ];

    public function getProfile(string $userId): User
    {
        return User::findOrFail($userId);
    }

    public function updateField(string $userId, string $field, ?string $value): User
    {
        if (! in_array($field, self::EDITABLE_FIELDS, true)) {
            throw ValidationException::withMessages([
                'field' => ["\"{$field}\" is not an editable field."],
            ]);
        }

        $user = User::findOrFail($userId);
        $user->{$field} = $value;
        $user->save();

        return $user;
    }

    public function uploadProfilePhoto(string $userId, UploadedFile $file): User
    {
        $user = User::findOrFail($userId);

        $path = $userId.'/'.(string) Str::uuid().'.'.$file->extension();
        $photoUrl = $this->storeInSupabase($path, $file);

        ProfilePhoto::create([
            'user_id' => $userId,
            'photo_url' => $photoUrl,
            'uploaded_at' => now(),
        ]);

        $user->profile_photo = $photoUrl;
        $user->save();

        return $user;
    }

    /**
     * @return Collection<int, ProfilePhoto>
     */
    public function getPhotoHistory(string $userId, int $limit = 20): Collection
    {
        return ProfilePhoto::where('user_id', $userId)
            ->orderByDesc('uploaded_at')
            ->limit($limit)
            ->get();
    }

    public function setCurrentPhoto(string $userId, int $photoId): User
    {
        $user = User::findOrFail($userId);

        $photo = ProfilePhoto::where('profile_photo_id', $photoId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Restoring an older photo counts as a new change, so it becomes
        // the latest history entry and shows up first again.
        ProfilePhoto::create([
            'user_id' => $userId,
            'photo_url' => $photo->photo_url,
            'uploaded_at' => now(),
        ]);

        $user->profile_photo = $photo->photo_url;
        $user->save();

        return $user;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection
    {
        return Category::orderBy('category_name')->get(['category_id', 'category_name']);
    }

    /**
     * @return list<int>
     */
    public function getSelectedCategoryIds(string $userId): array
    {
        return DB::table('user_interest')
            ->where('user_id', $userId)
            ->pluck('category_id')
            ->all();
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function updateInterests(string $userId, array $categoryIds): void
    {
        DB::transaction(function () use ($userId, $categoryIds) {
            DB::table('user_interest')->where('user_id', $userId)->delete();

            $rows = array_map(fn (int $categoryId) => [
                'user_id' => $userId,
                'category_id' => $categoryId,
                'selected_date' => now(),
            ], $categoryIds);

            DB::table('user_interest')->insert($rows);
        });
    }

    private function storeInSupabase(string $path, UploadedFile $file): string
    {
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$serviceRoleKey}",
            'apikey' => $serviceRoleKey,
            'Content-Type' => $file->getMimeType(),
        ])
            ->timeout(10)
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->post("{$baseUrl}/storage/v1/object/".self::PHOTO_BUCKET."/{$path}");

        if ($response->failed()) {
            throw new RuntimeException('Failed to upload photo to storage: '.$response->body());
        }

        return "{$baseUrl}/storage/v1/object/public/".self::PHOTO_BUCKET."/{$path}";
    }
}
