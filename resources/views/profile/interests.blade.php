@extends('layouts.app')

@section('title', 'My Interests - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>My Interests</h1>
            <p>Pick the cultural categories you're most interested in — we'll use these for your recommendations.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'interests'])

        <div>
            <div class="profile-card">
                <form data-interests-form>
                    @if ($categories->isEmpty())
                        <p class="profile-empty">No categories are available yet.</p>
                    @else
                        <div class="interest-picker">
                            @foreach ($categories as $category)
                                <label class="interest-picker-chip">
                                    <input type="checkbox" name="category_ids[]" value="{{ $category->category_id }}" @checked(in_array($category->category_id, $selectedIds))>
                                    <span>{{ $category->category_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <p class="profile-field-error" data-interests-error hidden></p>

                    <div class="profile-field-actions">
                        <button type="submit" class="button button-primary">Save Interests</button>
                        <span class="profile-field-success" data-interests-success hidden>Saved</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/pages/interests.js'])
    @endpush
@endsection
