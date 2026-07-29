@extends('layouts.app')

@section('title', 'Signing you in… - Living Heritage Malaysia')

@section('content')
    <section class="auth-page">
        <div class="auth-status-card">
            <div class="auth-spinner" aria-hidden="true"></div>
            <p data-auth-status>Signing you in…</p>
        </div>
    </section>

    @push('scripts')
        @vite(['resources/js/pages/auth-callback.js'])
    @endpush
@endsection
