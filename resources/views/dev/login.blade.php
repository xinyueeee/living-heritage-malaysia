@extends('layouts.app')

@section('title', 'Dev Quick Login - Living Heritage Malaysia')

@section('content')
    <section class="section container" style="max-width: 640px;">
        <p class="eyebrow">Local development only</p>
        <h1 style="font-family: Georgia, serif; margin: 0 0 8px;">Quick login</h1>
        <p class="section-description">Sign in as a seeded dummy user without going through Google/Supabase. This page only exists when <code>APP_ENV=local</code>.</p>

        @if ($users->isEmpty())
            <div class="no-data">
                <p>No dummy users yet. Run <code>php artisan db:seed</code>.</p>
            </div>
        @else
            <div style="display: grid; gap: 12px; margin-top: 28px;">
                @foreach ($users as $user)
                    <form method="POST" action="{{ route('dev.login.as', $user) }}" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface);">
                        @csrf
                        <div>
                            <strong>{{ $user->user_name }}</strong>
                            <div style="color: var(--muted); font-size: .88rem;">{{ $user->user_email }}</div>
                        </div>
                        <button type="submit" class="button button-primary">Log in as this user</button>
                    </form>
                @endforeach
            </div>
        @endif
    </section>
@endsection
