@extends('layouts.app')

@section('content')

<div class="experience-alert-actions">

            <a href="{{ route('festival.calendar') }}"
               class="experience-alert-back-btn">

                ← Back to Festival Calendar
            </a>

    </div>
<div class="personalized-alert-page">

    <div class="alert-header">
        <div>
            <h1>Personalized Alerts</h1>
            <p>
                Choose the types of experiences you are interested in.
                We'll email you when a new experience in your selected
                categories is starting within the next 7 days.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('alerts.store') }}">
    @csrf

    @foreach ($categories as $category)
        <label>
            <input
                type="checkbox"
                name="category_ids[]"
                value="{{ $category->category_id }}"
                {{ in_array($category->category_id, $selectedCategoryIds) ? 'checked' : '' }}
            >

            {{ $category->category_name }}
        </label>
    @endforeach

    <button type="submit">
        Save Personalized Alerts
    </button>
</form>

</div>

@endsection