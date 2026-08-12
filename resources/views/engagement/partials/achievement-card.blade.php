<button
    type="button"
    class="achievement-card
        {{ $achievement->is_unlocked ? 'unlocked' : 'locked' }}"
    data-badge-card
    data-name="{{ $achievement->badge_name }}"
    data-description="{{ $achievement->description ?? '-' }}"
    data-requirement="{{ $achievement->requirement ?? '-' }}"
    data-image="{{ asset(
        $achievement->badge_image
            ?? 'images/default-badge.png'
    ) }}"
    data-progress="{{ $achievement->current_progress ?? 0 }}"
    data-target="{{ $achievement->target_count ?? 1 }}"
    data-percentage="{{ $achievement->progress_percentage ?? 0 }}"
    data-unlocked="{{ $achievement->is_unlocked
        ? 'true'
        : 'false' }}"
    data-unlocked-date="{{ $achievement
        ->unlocked_date
        ?->format('d M Y') ?? '' }}"
>
    <img
        src="{{ asset(
            $achievement->badge_image
                ?? 'images/default-badge.png'
        ) }}"
        alt="{{ $achievement->badge_name }}"
    >

    <h3>{{ $achievement->badge_name }}</h3>

    <span class="badge-status">
        {{ $achievement->is_unlocked ? 'Unlocked' : 'Locked' }}
    </span>

    @if (! $achievement->is_unlocked)
        <div class="badge-card-progress">
            <div class="progress-bar">
                <div
                    class="progress"
                    style="width:
                        {{ $achievement->progress_percentage ?? 0 }}%"
                ></div>
            </div>

            <span>
                {{ $achievement->current_progress ?? 0 }}
                /
                {{ $achievement->target_count ?? 1 }}
            </span>
        </div>
    @endif

    <span class="view-details">View Details</span>
</button>