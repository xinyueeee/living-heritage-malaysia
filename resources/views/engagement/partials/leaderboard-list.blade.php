<article class="leaderboard-panel">
    <div class="leaderboard-heading">
        <span aria-hidden="true">{{ $icon }}</span>

        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $description }}</p>
        </div>
    </div>

    <ol class="leaderboard-list">
        @forelse ($leaders as $leader)
            <li @class([
                'leaderboard-row',
                'is-current-user' =>
                    $leader->user_id === Auth::id(),
            ])>
                <span class="leaderboard-rank">
                    @if ($loop->iteration === 1)
                        🥇
                    @elseif ($loop->iteration === 2)
                        🥈
                    @elseif ($loop->iteration === 3)
                        🥉
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>

                <div class="leaderboard-avatar">
                    @if ($leader->profile_photo)
                        <img
                            src="{{ $leader->profile_photo }}"
                            alt=""
                        >
                    @else
                        <span>
                            {{ Str::upper(
                                Str::substr(
                                    $leader->user_name ?? 'U',
                                    0,
                                    1
                                )
                            ) }}
                        </span>
                    @endif
                </div>

                <strong class="leaderboard-name">
                    {{ $leader->user_name ?? 'Heritage Explorer' }}

                    @if ($leader->user_id === Auth::id())
                        <small>You</small>
                    @endif
                </strong>

                <span class="leaderboard-score">
                    {{ $leader->score }}
                    {{ Str::plural($unit, $leader->score) }}
                </span>
            </li>
        @empty
            <li class="leaderboard-empty">
                No ranking information is available yet.
            </li>
        @endforelse
    </ol>
</article>