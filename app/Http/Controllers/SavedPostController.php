<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Services\Community\SavedPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedPostController extends Controller
{
    public function __construct(
        private SavedPostService $savedPostService
    ) {}

    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $posts = $this->savedPostService->paginateFor($user);

        return view('profile.saved-posts', compact('posts'));
    }

    public function store(Request $request, Post $post): RedirectResponse|JsonResponse
    {
        $this->savedPostService->save($this->authenticatedUser($request), $post);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Post saved.']);
        }

        return back()->with('status', 'Post saved.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse|JsonResponse
    {
        $this->savedPostService->unsave($this->authenticatedUser($request), $post);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Post removed from saved posts.']);
        }

        return back()->with('status', 'Post removed from saved posts.');
    }

    private function authenticatedUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
