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
        ]);
    }
}
