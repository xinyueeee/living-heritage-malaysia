<?php

namespace App\Http\Controllers;

use App\Models\SavedExperienceCollection;
use App\Models\User;
use App\Services\Experience\SavedExperienceCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedExperienceCollectionController extends Controller
{
    public function __construct(private SavedExperienceCollectionService $service) {}

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:80']]);
        $collection = $this->service->create($this->user($request), $request->string('name')->toString());
        if ($request->expectsJson()) {
            return response()->json(['collection' => $collection], 201);
        }
        return back()->with('status', 'Collection created.');
    }

    public function update(Request $request, SavedExperienceCollection $collection): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:80']]);
        $this->service->rename($this->user($request), $collection, $request->string('name')->toString());
        return back()->with('status', 'Collection renamed.');
    }

    public function destroy(Request $request, SavedExperienceCollection $collection): RedirectResponse
    {
        $this->service->delete($this->user($request), $collection);
        return redirect()->route('profile.saved-experiences')->with('status', 'Collection deleted. Its saved experiences were moved to Default.');
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        return $user;
    }
}
