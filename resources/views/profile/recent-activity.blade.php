@extends('layouts.app')

@section('title', 'Recent Activity - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero-photo" style="background-image: url('{{ asset('images/profile/profile-hero.png') }}');">
        <div class="profile-hero-photo-overlay"></div>
        <div class="container profile-hero-photo-content">
            <h1>Recent Discovery Activity</h1>
            <p>Revisit what you've recently searched and viewed while exploring Malaysia's living heritage.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'recent-activity'])

        <div>
            @if (session('status'))
                <p class="profile-saved-status" role="status">{{ session('status') }}</p>
            @endif

            @if ($searches->isEmpty() && $views->isEmpty())
                <div class="profile-card profile-saved-empty">
                    <span aria-hidden="true">&#9201;</span>
                    <h2>No recent discovery activity yet.</h2>
                    <p>Search or browse cultural experiences and festivals — your recent activity will show up here.</p>
                    <a class="button button-primary" href="{{ route('experiences.index') }}">Explore Experiences</a>
                </div>
            @else
                <form
                    method="POST"
                    action="{{ route('profile.recent-activity.clear') }}"
                    onsubmit="return confirm('Clear all your recent discovery activity? This cannot be undone.');"
                    class="recent-activity-clear-form"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="button button-primary">Clear Recent Activity</button>
                </form>

                <section class="profile-card">
                    <div class="profile-card-header-row">
                        <h3>Recently Searched</h3>
                    </div>

                    @if ($searches->isEmpty())
                        <p class="profile-empty">You haven't searched for anything yet.</p>
                    @else
                        <div class="profile-achievement-list">
                            @foreach ($searches as $search)
                                @php
                                    $filterParams = array_filter([
                                        'search' => $search->keyword,
                                        'location' => $search->location,
                                        'category' => $search->category_id,
                                        'type' => $search->type_id,
                                    ], fn ($value) => filled($value));
                                    $searchLabel = collect([
                                        $search->keyword,
                                        $search->location,
                                        $search->category_name,
                                        $search->type_name,
                                    ])->filter()->unique()->join(' · ');
                                @endphp
                                <a class="profile-achievement-item recent-activity-link" href="{{ route('experiences.index', $filterParams) }}">
                                    <span class="profile-achievement-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                                    </span>
                                    <div class="profile-achievement-info">
                                        <p class="profile-achievement-title">{{ $searchLabel !== '' ? $searchLabel : 'All Cultural Experiences' }}</p>
                                    </div>
                                    <span class="profile-achievement-date">{{ \Illuminate\Support\Carbon::parse($search->activity_at)->diffForHumans() }}</span>
                                </a>
                            @endforeach
                        </div>

                        {{ $searches->withQueryString()->onEachSide(1)->links('components.pagination') }}
                    @endif
                </section>

                <section class="profile-card">
                    <div class="profile-card-header-row">
                        <h3>Recently Viewed</h3>
                    </div>

                    @if ($views->isEmpty())
                        <p class="profile-empty">You haven't viewed any experiences yet.</p>
                    @else
                        <div class="profile-saved-grid">
                            @foreach ($views as $view)
                                @include('components.experience-card', ['experience' => $view])
                            @endforeach
                        </div>

                        {{ $views->withQueryString()->onEachSide(1)->links('components.pagination') }}
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
