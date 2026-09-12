@extends('layouts.app')

@section('content')

<div class="container py-5 community-groups-page">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Community Groups</h1>
            <p class="text-muted mb-0">
                Join communities and connect with people who share your interests.
            </p>
        </div>

        <a href="{{ route('home') }}" class="text-decoration-none" style="color: #8A3A2D; font-weight: 600; font-family: Georgia, serif;">
            ← Back to Home 
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($groups->count() > 0)

        <div class="row g-5">

            @foreach($groups as $group)

                <div class="col-md-6 col-lg-4 mb-4">

                    <div class="card h-100 shadow-sm" style="border-radius: 18px; overflow: hidden; border: 1px solid #eee3dc;">

                        {{-- Cover Image --}}
                        @if($group->cover_image)
                            <img
                                src="{{ asset($group->cover_image) }}"
                                class="card-img-top"
                                alt="{{ $group->name }}"
                                style="height: 200px; width: 100%; object-fit: cover;"
                            >
                        @else
                            <div
                                class="bg-light d-flex align-items-center justify-content-center"
                                style="height: 200px; background: #f5eee8 !important;"
                            >
                                <span class="text-muted" style="color: #9a8980; font-family: Georgia, serif;">
                                    No Cover Image
                                </span>
                            </div>
                        @endif

                        <div class="card-body d-flex flex-column">

                            <h5 class="card-title" style="color: #2C1E16; font-family: Georgia, serif; font-weight: 700;">
                                {{ $group->name }}
                            </h5>

                            <p class="card-text text-muted" style="font-size: 0.92rem; line-height: 1.65; flex: 1;">
                                {{ $group->description }}
                            </p>

                            <div class="mt-auto">

                                <div class="mb-3 text-muted small" style="font-weight: 600; color: #81736b !important;">
                                    {{ $group->members_count }} members
                                </div>

                                <a
                                    href="{{ route('community.groups.show', $group->group_id) }}"
                                    class="btn btn-primary w-100"
                                    style="background: #8A3A2D; border: none; border-radius: 10px; padding: 11px; font-weight: 600; font-family: Georgia, serif; transition: all 0.25s ease;"
                                >
                                    View Group
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="text-center py-5">

            <h4>No Community Groups Yet</h4>

            <p class="text-muted">
                There are currently no community groups available.
            </p>

        </div>

    @endif

</div>

@endsection