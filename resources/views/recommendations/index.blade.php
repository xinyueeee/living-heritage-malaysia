@extends('layouts.app')

@section('title', 'Personalized Recommendations | Living Heritage Malaysia')

@section('content')
    <div class="recommendations-page">
        @php($recommendationHeroExists = is_file(public_path('images/recommendations/recommendation-hero.jpg')))

        <section
            @class(['recommendations-hero', 'recommendations-hero-has-image' => $recommendationHeroExists])
            @if ($recommendationHeroExists)
                style="--recommendations-hero-image: url('{{ asset('images/recommendations/recommendation-hero.jpg') }}')"
            @endif
        >
            <div class="recommendations-container recommendations-hero-content">
                <div class="recommendations-title-row">
                    <x-home-icon name="sparkles" />
                    <h1>Personalized Recommendations</h1>
                </div>
                <p>
                    {{ $isPersonalized
                        ? 'Based on your cultural interests and recent activity.'
                        : 'Explore a diverse selection of available cultural experiences.' }}
                </p>
                <a class="recommendations-refresh" href="{{ route('recommendations.index') }}">
                    <x-home-icon name="refresh" />
                    <span>Refresh Recommendations</span>
                </a>
            </div>
        </section>

        <div class="recommendations-container recommendations-content">
            <section class="recommendations-section recommendations-interests" aria-labelledby="recommendations-interests-title">
                <div class="recommendations-section-heading">
                    <x-home-icon name="target" />
                    <h2 id="recommendations-interests-title">Because you are interested in...</h2>
                </div>

                <div class="recommendations-interest-grid">
                    @forelse ($interests as $interest)
                        <article class="recommendations-interest-card">
                            <span class="recommendations-interest-icon"><x-home-icon name="discover" /></span>
                            <h3>{{ $interest->category_name }}</h3>
                        </article>
                    @empty
                        <div class="recommendations-empty-state recommendations-empty-state-wide">
                            @auth
                                <p>Add cultural interests to your profile to make these recommendations more personal.</p>
                                <a href="{{ route('profile.interests') }}">Choose interests</a>
                            @else
                                <p>Log in and choose your cultural interests for more personalized recommendations.</p>
                                <a href="{{ route('login') }}">Log in</a>
                            @endauth
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="recommendations-section recommendations-activity" aria-labelledby="recommendations-activity-title">
                <div class="recommendations-section-heading">
                    <x-home-icon name="clock" />
                    <h2 id="recommendations-activity-title">Based on your recent activity</h2>
                    @auth
                        <a class="recommendations-activity-viewall" href="{{ route('profile.recent-activity') }}">View All Recent Activity <span aria-hidden="true">&rarr;</span></a>
                    @endauth
                </div>

                <div class="recommendations-activity-panel">
                    @forelse ($recentActivity as $activityType => $activityItems)
                        <div class="recommendations-activity-column">
                            <h3>
                                @if ($activityType === 'searched')
                                    Recently searched:
                                @elseif ($activityType === 'viewed')
                                    Recently viewed:
                                @else
                                    Recent activity:
                                @endif
                            </h3>
                            <ul>
                                @foreach ($activityItems as $activity)
                                    <li>{{ $activity->display_text }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <div class="recommendations-empty-state recommendations-activity-empty">
                            <p>Your recommendations will become more personalized as you explore more cultural experiences.</p>
                        </div>
                    @endforelse
                    <div class="recommendations-activity-decoration" aria-hidden="true">
                        <span></span><span></span><span></span><span></span>
                    </div>
                </div>
            </section>

            <section class="recommendations-section recommendations-results" aria-labelledby="recommended-experiences-title">
                <div class="recommendations-section-heading">
                    <x-home-icon name="star" />
                    <h2 id="recommended-experiences-title">Recommended Experiences</h2>
                </div>

                @if ($recommendedExperiences->isEmpty())
                    <div class="no-data"><p>No experiences are available at the moment.</p></div>
                @else
                    <div class="recommendations-grid">
                        @foreach ($recommendedExperiences as $recommendation)
                            @include('components.experience-card', [
                                'experience' => $recommendation['experience'],
                                'variant' => 'recommendation',
                                'recommendationReason' => $recommendation['reason'],
                            ])
                        @endforeach
                    </div>

                    {{ $recommendedExperiences->onEachSide(1)->links('components.pagination') }}
                @endif
            </section>

            <a class="recommendations-browse-more" href="{{ route('experiences.index') }}">
                <span>Browse More Experiences</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
@endsection
