<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceIndexRequest;
use App\Models\Experience;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function home(): View
    {
        $experiences = Experience::query()
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('welcome', compact('experiences'));
    }

    public function index(ExperienceIndexRequest $request): View
    {
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'newest';

        $experiences = Experience::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('experiences_title', 'like', "%{$search}%")
                        ->orWhere('experiences_desc', 'like', "%{$search}%")
                        ->orWhere('experiences_category', 'like', "%{$search}%");
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location) {
                $query->where('experiences_location', 'like', "%{$location}%");
            })
            ->when($filters['category'] ?? null, function ($query, string $category) {
                $query->where('experiences_category', $category);
            })
            ->when(
                $sort === 'oldest',
                fn ($query) => $query->oldest('created_at'),
                fn ($query) => $query->latest('created_at'),
            )
            ->paginate(9)
            ->withQueryString();

        $categories = Experience::query()
            ->distinct()
            ->orderBy('experiences_category')
            ->pluck('experiences_category');

        return view('experiences.index', compact('categories', 'experiences'));
    }
}
