<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\SavedExperienceCollection;
use App\Models\User;
use App\Services\Experience\SavedExperienceService;
use App\Services\Experience\SavedExperienceCollectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedExperienceController extends Controller
{
    public function __construct(
        private SavedExperienceService $savedExperienceService,
        private SavedExperienceCollectionService $collectionService,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $selected = $request->string('collection', 'all')->toString();
        $collections = $this->collectionService->forUser($user, true);
        if (ctype_digit($selected) && ! $collections->contains('collection_id', (int) $selected)) {
            abort(404);
        }
        if (! in_array($selected, ['all', 'default'], true) && ! ctype_digit($selected)) {
            abort(404);
        }
        $experiences = $this->savedExperienceService->paginateFor($user, $selected);
        $defaultCount = DB::table('favourite')->where('user_id', $user->getKey())->whereNull('collection_id')->count();
        $allCount = DB::table('favourite')->where('user_id', $user->getKey())->count();

        return view('profile.saved-experiences', compact('experiences', 'collections', 'selected', 'defaultCount', 'allCount'));
    }

    public function store(Request $request, Experience $experience): RedirectResponse
    {
        $collection = $this->collectionFromRequest($request);
        $this->savedExperienceService->save($this->authenticatedUser($request), $experience, $collection);

        return back()->with('status', 'Experience saved.');
    }

    public function destroy(Request $request, Experience $experience): RedirectResponse
    {
        $this->savedExperienceService->unsave($this->authenticatedUser($request), $experience);

        return back()->with('status', 'Experience removed from saved experiences.');
    }

    public function move(Request $request, Experience $experience): RedirectResponse
    {
        $this->savedExperienceService->move($this->authenticatedUser($request), $experience, $this->collectionFromRequest($request));
        return back()->with('status', 'Experience moved.');
    }

    private function collectionFromRequest(Request $request): ?SavedExperienceCollection
    {
        $validated = $request->validate(['collection_id' => ['nullable', 'integer']]);
        return isset($validated['collection_id'])
            ? SavedExperienceCollection::query()->findOrFail($validated['collection_id'])
            : null;
    }

    private function authenticatedUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
