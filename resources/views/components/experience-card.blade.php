@php
    $variant = $variant ?? 'standard';
    $imagePath = is_string($experience->image_url)
        ? ltrim(str_replace('\\', '/', trim($experience->image_url)), '/')
        : null;
    $isSafeRelativePath = filled($imagePath)
        && !str_contains($imagePath, '../')
        && !str_starts_with($imagePath, 'http://')
        && !str_starts_with($imagePath, 'https://');
    $imageSource = $isSafeRelativePath && is_file(public_path($imagePath))
        ? asset($imagePath)
        : null;
    $typeName = $experience->type?->type_name;
    $badgeName = $experience->category?->category_name ?? $typeName;

    $countdownTarget = null;
    if ($variant === 'festival') {
        if ($experience->start_date?->greaterThanOrEqualTo(today())) {
            $countdownTarget = $experience->start_date;
        } elseif ($experience->end_date?->greaterThanOrEqualTo(today())) {
            $countdownTarget = $experience->end_date;
        }
    }
    $daysRemaining = $countdownTarget ? (int) today()->diffInDays($countdownTarget) : null;
@endphp

<article @class([
    'experience-card',
    'experience-card-festival' => $typeName === 'Festival',
    'home-feature-card' => $variant === 'home',
    'festival-card' => $variant === 'festival',
])>
    <div class="card-media">
        @if ($imageSource)
            <img class="card-image" src="{{ $imageSource }}" alt="{{ $experience->experiences_name }}">
        @else
            <div class="card-image card-image-placeholder" role="img" aria-label="Image unavailable for {{ $experience->experiences_name }}">
                <svg viewBox="0 0 64 48" fill="none" aria-hidden="true">
                    <circle cx="46" cy="13" r="5" fill="currentColor" opacity=".75"/>
                    <path d="M8 39 24 22l9 9 7-7 16 15H8Z" fill="currentColor" opacity=".72"/>
                    <path d="M8 39h48" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        @endif

        @if ($badgeName)
            <span class="card-category">{{ $badgeName }}</span>
        @endif

        @if ($variant === 'home')
            <span class="card-favourite" aria-label="Favourite control preview">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
            </span>
        @endif

        @if ($variant === 'festival' && !is_null($daysRemaining))
            <span class="festival-countdown"><strong>{{ $daysRemaining }}</strong> {{ $daysRemaining === 1 ? 'day' : 'days' }} left</span>
        @endif
    </div>

    <div class="card-content">
        <h3>{{ $experience->experiences_name }}</h3>

        @if ($experience->start_date)
            <p class="card-date">
                <span aria-hidden="true">&#128197;</span>
                {{ $experience->start_date->format('d M Y') }}
                @if ($experience->end_date && !$experience->end_date->isSameDay($experience->start_date))
                    &ndash; {{ $experience->end_date->format('d M Y') }}
                @endif
            </p>
        @endif

        <p class="location"><span aria-hidden="true">&#128205;</span> {{ $experience->location_name }}</p>

        @if ($variant === 'standard')
            <p class="card-description">{{ Str::limit($experience->description ?? '', 105) }}</p>
        @endif

        <div class="card-meta">
            @if ($variant === 'festival')
                @if ($badgeName)
                    <span class="festival-label">{{ $badgeName }}</span>
                @endif
                <span class="festival-details-link" aria-disabled="true">View Details <span aria-hidden="true">&rarr;</span></span>
            @else
                @if (!is_null($experience->price))
                    <span class="price">RM {{ number_format((float) $experience->price, 2) }}</span>
                @endif
                @if ($experience->duration)
                    <span class="duration"><span aria-hidden="true">&#9201;</span> {{ $experience->duration }}</span>
                @endif
                @if (is_null($experience->price) && !$experience->duration && $typeName)
                    <span class="type-label">{{ $typeName }}</span>
                @endif
            @endif
        </div>
    </div>
</article>
