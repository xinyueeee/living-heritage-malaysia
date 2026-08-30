<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadProfilePhotoRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfilePhotoController extends Controller
{
    public function __construct(private ProfileService $profileService)
    {
    }

    public function store(UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = $this->profileService->uploadProfilePhoto(
            Auth::id(),
            $request->file('photo')
        );

        return response()->json([
            'photo_url' => $user->profile_photo,
            'photo_id' => $user->latest_photo_id,
            'uploaded_at' => optional($user->latest_photo_uploaded_at)->format('j M Y'),
        ]);
    }

    public function restore(int $photo): JsonResponse
    {
        $user = $this->profileService->setCurrentPhoto(Auth::id(), $photo);

        return response()->json([
            'photo_url' => $user->profile_photo,
        ]);
    }
}
