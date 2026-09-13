@extends('layouts.app')

@section('title', 'Edit Post | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <div class="container create-post-page">

        {{-- =========================================================
             BACK BUTTON
        ========================================================== --}}

        @php
            $from = $from ?? request('from', 'community');
        @endphp

        <a
            href="{{ $from === 'profile'
                ? route('profile.my-posts')
                : ($post->community_group_id
                    ? route('community.groups.show', $post->community_group_id)
                    : route('community.index')
                )
            }}"
            class="back-link"
        >

            ← Back

        </a>


        {{-- =========================================================
             HEADER
        ========================================================== --}}

        <div class="create-header">

            <h1>
                Edit Post
            </h1>

            <p>
                Update your community group, content, and photos.
            </p>

        </div>


        {{-- =========================================================
             VALIDATION ERRORS
        ========================================================== --}}

        @if ($errors->any())

            <div class="alert alert-danger">

                <ul style="margin:0;padding-left:20px;">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =========================================================
             EDIT POST CARD
        ========================================================== --}}

        <div class="create-card">

            <form
                action="{{ route('community.posts.update', $post->post_id) }}"
                method="POST"
                enctype="multipart/form-data"
                id="editPostForm"
            >

                @csrf

                @method('PUT')


                {{-- =================================================
                     KEEP WHERE THE USER CAME FROM
                ================================================== --}}

                <input
                    type="hidden"
                    name="from"
                    value="{{ $from }}"
                >


                {{-- =================================================
                     POST TO
                ================================================== --}}

                <div class="form-group">

                    <div class="form-label-row">

                        <label for="community_group_id">
                            Post to
                        </label>

                    </div>

                    <p class="form-help">
                        Choose where you want this post to appear.
                    </p>


                    <div class="experience-dropdown">

                        <select
                            id="community_group_id"
                            name="community_group_id"
                        >

                            {{-- Community Feed --}}

                            <option
                                value=""
                                @selected(
                                    old(
                                        'community_group_id',
                                        $post->community_group_id
                                    ) === null ||
                                    old(
                                        'community_group_id',
                                        $post->community_group_id
                                    ) === ''
                                )
                            >
                                Community
                            </option>


                            {{-- Joined Community Groups --}}

                            @foreach ($groups as $group)

                                <option
                                    value="{{ $group->group_id }}"
                                    @selected(
                                        old(
                                            'community_group_id',
                                            $post->community_group_id
                                        ) == $group->group_id
                                    )
                                >
                                    {{ $group->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <small
                        class="form-help"
                        style="display:block;margin-top:8px;"
                    >
                        You can select one of the community groups you have joined.
                    </small>

                </div>


                {{-- =================================================
                     WHAT'S ON YOUR MIND
                ================================================== --}}

                <div class="form-group">

                    <label for="content">
                        What's on your mind?
                    </label>


                    <textarea
                        id="content"
                        name="content"
                        rows="7"
                        maxlength="2000"
                        placeholder="Share your experience, stories, tips, or recommendations..."
                    >{{ old('content', $post->content) }}</textarea>


                    <div class="character-counter">

                        <span id="charCount">
                            {{ strlen(old('content', $post->content ?? '')) }}
                        </span>

                        / 2000

                    </div>

                </div>


                {{-- =================================================
                     EXISTING PHOTOS
                ================================================== --}}

                @php

                    $existingImages = [];

                    if (!empty($post->post_images)) {

                        $decodedImages = json_decode(
                            $post->post_images,
                            true
                        );

                        if (is_array($decodedImages)) {

                            $existingImages = $decodedImages;

                        }

                    }

                @endphp


                @if (count($existingImages) > 0)

                    <div class="form-group">

                        <div class="form-label-row">

                            <label>
                                Current Photos
                            </label>

                            <span class="optional">
                                (Click × to remove)
                            </span>

                        </div>


                        <p class="form-help">
                            Remove any photos you no longer want in this post.
                        </p>


                        <div class="edit-current-photos">

                            @foreach ($existingImages as $index => $image)

                                <div
                                    class="edit-current-photo"
                                    id="current-photo-{{ $index }}"
                                >

                                    <img
                                        src="{{ $image }}"
                                        alt="Current post photo"
                                    >


                                    {{-- Keep image by default --}}

                                    <input
                                        type="checkbox"
                                        name="keep_images[]"
                                        value="{{ $image }}"
                                        id="keep-image-{{ $index }}"
                                        class="keep-image-checkbox"
                                        checked
                                    >


                                    <button
                                        type="button"
                                        class="edit-remove-image"
                                        data-index="{{ $index }}"
                                        aria-label="Remove photo"
                                    >
                                        ×
                                    </button>

                                </div>

                            @endforeach

                        </div>


                        <small class="form-help">
                            Removed photos will no longer appear in this post.
                        </small>

                    </div>

                @endif


                {{-- =================================================
                     ADD NEW PHOTOS
                ================================================== --}}

                <div class="form-group">

                    <div class="form-label-row">

                        <label>
                            Add Photos
                        </label>

                        <span class="optional">
                            (Optional)
                        </span>

                    </div>


                    <p class="form-help">
                        Add new photos to your post.
                    </p>


                    <div class="photo-upload-row">


                        {{-- UPLOAD BOX --}}

                        <div class="upload-box">

                            <div class="upload-icon">
                                +
                            </div>

                            <h3>
                                Add Photos
                            </h3>

                            <p>
                                Click or drag files here
                            </p>

                            <small>
                                Maximum 10 photos
                            </small>


                            <input
                                type="file"
                                id="imageInput"
                                name="images[]"
                                multiple
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>


                        {{-- NEW PHOTO PREVIEW --}}

                        <div
                            id="photoPreviewPanel"
                            class="photo-preview-panel"
                        >

                            <div class="photo-preview-header">

                                <strong>
                                    Photo Preview
                                </strong>

                                <span id="photoCount">
                                    ({{ count($existingImages) }}/10)
                                </span>

                            </div>


                            <div
                                id="imagePreview"
                                class="image-preview"
                            ></div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     UPLOAD NOTE
                ================================================== --}}

                <div class="upload-note">

                    Supported formats:
                    JPG, PNG, WEBP

                    •

                    Maximum size:
                    10MB per photo

                </div>


                {{-- =================================================
                     BUTTONS
                ================================================== --}}

                <div class="button-group">


                    {{-- CANCEL --}}

                    <a
                        href="{{ $from === 'profile'
                            ? route('profile.my-posts')
                            : ($post->community_group_id
                                ? route('community.groups.show', $post->community_group_id)
                                : route('community.index')
                            )
                        }}"
                        class="cancel-btn"
                    >

                        Cancel

                    </a>


                    {{-- SAVE --}}

                    <button
                        type="submit"
                        class="publish-btn"
                        id="saveChangesButton"
                    >

                        Save Changes

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>

@endsection


{{-- ================================================================
     EDIT POST STYLES
================================================================ --}}

@push('styles')

<style>

    .edit-current-photos {

        display: flex;

        align-items: flex-start;

        gap: 8px;

        flex-wrap: wrap;

        width: 100%;

    }


    .edit-current-photo {

        position: relative;

        width: 82px;

        height: 130px;

        flex: 0 0 82px;

        overflow: hidden;

        border-radius: 7px;

        background: #f5f5f5;

        transition:
            opacity .2s ease,
            transform .2s ease;

    }


    .edit-current-photo img {

        display: block;

        width: 100%;

        height: 100%;

        object-fit: cover;

    }


    .edit-remove-image {

        position: absolute;

        top: 4px;

        right: 4px;

        width: 21px;

        height: 21px;

        padding: 0;

        border: none;

        border-radius: 50%;

        background: #fff;

        color: #8A3A2D;

        font-size: 16px;

        font-weight: 700;

        line-height: 21px;

        text-align: center;

        cursor: pointer;

        z-index: 20;

        box-shadow: 0 1px 4px rgba(0,0,0,.15);

    }


    .edit-remove-image:hover {

        background: #8A3A2D;

        color: #fff;

    }


    .edit-current-photo.is-removed {

        opacity: .35;

    }


    .edit-current-photo.is-removed img {

        filter: grayscale(1);

    }


    .edit-current-photo.is-removed::after {

        content: "Removed";

        position: absolute;

        left: 0;

        right: 0;

        bottom: 0;

        padding: 5px 2px;

        background: rgba(138,58,45,.9);

        color: #fff;

        font-size: 10px;

        text-align: center;

        z-index: 10;

    }


    @media (max-width: 600px) {

        .edit-current-photos {

            gap: 6px;

        }

        .edit-current-photo {

            width: 75px;

            height: 115px;

            flex-basis: 75px;

        }

    }

</style>

@endpush


{{-- ================================================================
     JAVASCRIPT
================================================================ --}}

@push('scripts')

    @vite('resources/js/pages/community-edit.js')

@endpush