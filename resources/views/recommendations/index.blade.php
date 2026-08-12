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
                <p>Based on your cultural interests and recent activity.</p>
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
                    @foreach ($interestPlaceholders as $interest)
                        <article class="recommendations-interest-card">
                            <span class="recommendations-interest-icon"><x-home-icon :name="$interest['icon']" /></span>
                            <h3>{{ $interest['name'] }}</h3>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="recommendations-section recommendations-activity" aria-labelledby="recommendations-activity-title">
                <div class="recommendations-section-heading">
                    <x-home-icon name="clock" />
                    <h2 id="recommendations-activity-title">Based on your recent activity</h2>
                </div>

                <div class="recommendations-activity-panel">
                    <div class="recommendations-activity-column">
                        <h3>Recently searched:</h3>
                        <ul>
                            @foreach ($recentActivityPlaceholders['searched'] as $search)
                                <li>{{ $search }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="recommendations-activity-column">
                        <h3>Recently viewed:</h3>
                        <ul>
                            @foreach ($recentActivityPlaceholders['viewed'] as $experienceName)
                                <li>{{ $experienceName }}</li>
                            @endforeach
                        </ul>
                    </div>
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
                        @foreach ($recommendedExperiences as $experience)
                            @include('components.experience-card', ['experience' => $experience, 'variant' => 'recommendation'])
                        @endforeach
                    </div>
                @endif
            </section>

            <a class="recommendations-browse-more" href="{{ route('experiences.index') }}">
                <span>Browse More Experiences</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
@endsection
