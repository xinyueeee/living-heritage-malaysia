<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceIndexRequest;
use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function home(): View
    {
        $experiences = Experience::query()
            ->with(['category', 'type'])
            ->latest('created_at')
            ->latest('experiences_id')
            ->limit(6)
            ->get();

        $festivalType = ExperienceType::query()
            ->where('type_name', 'Festival')
            ->first();

        $festivals = Experience::query()
            ->with(['category', 'type'])
            ->whereHas('type', function ($query) {
                $query->where('type_name', 'Festival');
            })
            ->where(function ($query) {
                $query->whereDate('start_date', '>=', today())
                    ->orWhereDate('end_date', '>=', today());
            })
            ->orderBy('start_date')
            ->orderBy('experiences_id')
            ->limit(3)
            ->get();

        return view('welcome', compact('experiences', 'festivals', 'festivalType'));
    }

    public function index(ExperienceIndexRequest $request): View
    {
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'newest';

        $experiences = Experience::query()
            ->with(['category', 'type'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('experiences_name', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('category_name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('type', function ($query) use ($search) {
                            $query->where('type_name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location) {
                $query->where('location_name', 'ilike', "%{$location}%");
            })
            ->when($filters['category'] ?? null, function ($query, int $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['type'] ?? null, function ($query, int $typeId) {
                $query->where('type_id', $typeId);
            })
            ->when(
                $sort === 'oldest',
                fn ($query) => $query->oldest('created_at')->oldest('experiences_id'),
                fn ($query) => $query->latest('created_at')->latest('experiences_id'),
            )
            ->paginate(9)
            ->withQueryString();

        $categories = Category::query()
            ->with('type')
            ->orderBy('category_name')
            ->get();

        $types = ExperienceType::query()
            ->withCount('experiences')
            ->orderBy('type_id')
            ->get();

        return view('experiences.index', compact('categories', 'experiences', 'types'));
    }
}
