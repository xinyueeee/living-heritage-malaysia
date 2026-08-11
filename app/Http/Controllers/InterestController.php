<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInterestsRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterestController extends Controller
{
    public function __construct(private ProfileService $profileService)
    {
    }

    public function show(): View
    {
        return view('profile.interests', [
            'categories' => $this->profileService->getAllCategories(),
            'selectedIds' => $this->profileService->getSelectedCategoryIds(Auth::id()),
        ]);
    }

    public function update(UpdateInterestsRequest $request): JsonResponse
    {
        $this->profileService->updateInterests(
            Auth::id(),
            $request->validated('category_ids')
        );

        return response()->json(['message' => 'Interests updated.']);
    }
}
