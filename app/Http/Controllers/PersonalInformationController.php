<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePersonalInformationRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PersonalInformationController extends Controller
{
    public function __construct(private ProfileService $profileService)
    {
    }

    public function show(): View
    {
        return view('profile.personal-information', [
            'user' => $this->profileService->getProfile(Auth::id()),
        ]);
    }

    public function update(UpdatePersonalInformationRequest $request, string $field): JsonResponse
    {
        $user = $this->profileService->updateField(
            Auth::id(),
            $field,
            $request->validated('value')
        );

        $value = $user->{$field};

        if ($value instanceof Carbon) {
            $value = $value->format('Y-m-d');
        }

        return response()->json([
            'field' => $field,
            'value' => $value,
        ]);
    }
}
