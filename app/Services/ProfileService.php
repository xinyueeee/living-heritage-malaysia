<?php

namespace App\Services;

use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
        'user_email',
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

    private function storeInSupabase(string $path, UploadedFile $file): string
    {
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$serviceRoleKey}",
            'apikey' => $serviceRoleKey,
            'Content-Type' => $file->getMimeType(),
        ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->post("{$baseUrl}/storage/v1/object/".self::PHOTO_BUCKET."/{$path}");

        if ($response->failed()) {
            throw new RuntimeException('Failed to upload photo to storage: '.$response->body());
        }

        return "{$baseUrl}/storage/v1/object/public/".self::PHOTO_BUCKET."/{$path}";
    }
}
