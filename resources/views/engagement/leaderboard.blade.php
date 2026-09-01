@extends('layouts.app')

@section('title', 'Overall Leaderboard')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')
<div class="engagement-page leaderboard-page">
    <section class="leaderboard-header">
        <div class="container">
            <a
                href="{{ route('engagement.index') }}"
                class="passport-back-link"
            >
                &larr; Back to Engagement &amp; Rewards
            </a>

            <span class="passport-eyebrow">
                CULTURAL EXPLORERS
            </span>

            <h1>Overall Leaderboard</h1>

            <p>
                Celebrating the community’s all-time cultural
                journey progress.
            </p>
        </div>
    </section>

    <section class="leaderboard-content container">
        <div class="leaderboard-grid">
            @include(
                'engagement.partials.leaderboard-list',
                [
                    'title' => 'Top Stamp Collectors',
                    'description' =>
                        'Most unique passport stamps collected',
                    'icon' => '📖',
                    'leaders' => $stampLeaders,
                    'unit' => 'stamp',
                ]
            )

            @include(
                'engagement.partials.leaderboard-list',
                [
                    'title' => 'Top Cultural Explorers',
                    'description' =>
                        'Most unique experiences completed',
                    'icon' => '🧭',
                    'leaders' => $experienceLeaders,
                    'unit' => 'experience',
                ]
            )
        </div>
    </section>
</div>
@endsection