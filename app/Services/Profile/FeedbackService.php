<?php

namespace App\Services\Profile;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FeedbackService
{
    private const IMAGE_BUCKET = 'profile-photos';

    /**
     * @return list<string>
     */
    public function subjectOptions(): array
    {
        return [
            'General Feedback',
            'Feature Suggestion',
            'Bug / Technical Issue',
            'Content Accuracy',
            'Account & Login',
            'Other',
        ];
    }

    /**
     * @param  list<UploadedFile>  $images
     */
    public function submit(User $user, string $subject, string $description, array $images = []): Feedback
    {
        $feedback = Feedback::create([
            'user_id' => $user->user_id,
            'subject' => $subject,
            'description' => $description,
        ]);

        foreach ($images as $image) {
            $path = $this->storeInSupabase($user->user_id, $image);

            $feedback->photos()->create([
                'file_name' => $image->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }

        return $feedback;
    }

    public function paginateFor(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Feedback::where('user_id', $user->user_id)
            ->with('photos')
            ->latest('submitted_at')
            ->paginate($perPage);
    }

    private function storeInSupabase(string $userId, UploadedFile $file): string
    {
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        $path = 'feedback/'.$userId.'/'.(string) Str::uuid().'.'.$file->extension();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$serviceRoleKey}",
            'apikey' => $serviceRoleKey,
            'Content-Type' => $file->getMimeType(),
        ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->post("{$baseUrl}/storage/v1/object/".self::IMAGE_BUCKET."/{$path}");

        if ($response->failed()) {
            throw new RuntimeException('Failed to upload feedback image to storage: '.$response->body());
        }

        return "{$baseUrl}/storage/v1/object/public/".self::IMAGE_BUCKET."/{$path}";
    }
}
