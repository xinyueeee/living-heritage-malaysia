@extends('layouts.app')

@section('title', 'Saved Experiences - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>Saved Experiences</h1>
            <p>Keep track of the cultural experiences and festivals you want to explore.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'saved-experiences'])

        <div>
            @if (session('status'))
                <p class="profile-saved-status" role="status">{{ session('status') }}</p>
            @endif

            @if ($experiences->isEmpty())
                <div class="profile-card profile-saved-empty">
                    <span aria-hidden="true">♡</span>
                    <h2>You haven't saved any experiences yet.</h2>
                    <p>Explore Malaysia's living heritage and use the heart button to save experiences here.</p>
                    <a class="button button-primary" href="{{ route('experiences.index') }}">Explore Experiences</a>
                </div>
            @else
                @php($savedExperienceIds = $experiences->pluck('experiences_id')->map(fn ($id) => (int) $id)->all())
                <div class="profile-saved-grid">
                    @foreach ($experiences as $experience)
                        @include('components.experience-card', [
                            'experience' => $experience,
                            'savedExperienceIds' => $savedExperienceIds,
                        ])
                    @endforeach
                </div>

                {{ $experiences->onEachSide(1)->links('components.pagination') }}
            @endif
        </div>
    </div>
@endsection
