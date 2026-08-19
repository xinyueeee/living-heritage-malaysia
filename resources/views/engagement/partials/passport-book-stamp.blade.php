<button
    type="button"
    class="passport-book-stamp"
    data-user-stamp-id="{{ $userStamp->user_stamp_id }}"
    title="{{ $userStamp->stamp?->category }}"
>
    <img
        src="{{ $userStamp->stamp?->stamp_image
            ? asset($userStamp->stamp->stamp_image)
            : asset('images/default-stamp.png') }}"
        alt="{{ $userStamp->stamp?->category
            ?? 'Passport stamp' }}"
    >

    <span>
        {{ $userStamp->stamp?->category
            ?? 'Category' }}
    </span>

    <small class="passport-book-stamp-details">
        @if (
            $userStamp
                ->completedExperience
                ?->experience
        )
            <strong>
                {{
                    $userStamp
                        ->completedExperience
                        ->experience
                        ->experiences_name
                }}
            </strong>
        @endif

        <time>
            {{ $userStamp->collected_date
                ?->format('d M Y')
                ?? 'Unknown date' }}
        </time>
    </small>
</button>