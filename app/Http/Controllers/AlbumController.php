<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
    /**
     * Display all albums belonging to the logged-in user.
     */
    public function index()
    {
        $user = Auth::user();

        $albums = DB::table('album')
            ->where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->get();

        // Get photo count for each album
        foreach ($albums as $album) {
            $album->photo_count = DB::table('album_photo')
                ->where('album_id', $album->album_id)
                ->count();
        }

        return view('profile.albums.index', compact('albums'));
    }

    /**
     * Show the create album page.
     */
    public function create()
    {
        return view('profile.albums.create');
    }

    /**
     * Store a new album.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'album_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $albumId = DB::table('album')->insertGetId([
            'user_id' => $user->user_id,
            'album_name' => trim($validated['album_name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'album_id');

        return redirect()
            ->route('profile.albums.show', $albumId)
            ->with('success', 'Album "' . $validated['album_name'] . '" created successfully!');
    }

    /**
     * Display one album and its photos.
     */
    public function show($albumId)
    {
        $user = Auth::user();

        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        $photos = DB::table('album_photo')
            ->where('album_id', $albumId)
            ->orderByDesc('created_at')
            ->get();

        return view('profile.albums.show', [
            'album' => $album,
            'photos' => $photos,
        ]);
    }

    /**
     * Show the edit album page.
     */
    public function edit($albumId)
    {
        $user = Auth::user();

        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        return view('profile.albums.edit', compact('album'));
    }

    /**
     * Update an album.
     */
    public function update(Request $request, $albumId)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'album_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->update([
                'album_name' => trim($validated['album_name']),
                'description' => $validated['description'] ? trim($validated['description']) : null,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            abort(404, 'Album not found or you do not have permission to edit it.');
        }

        return redirect()
            ->route('profile.albums.show', $albumId)
            ->with('success', 'Album updated successfully!');
    }

    /**
     * Delete an album.
     */
    public function destroy($albumId)
    {
        $user = Auth::user();

        // Verify album belongs to user
        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        // Get all photos to delete files
        $photos = DB::table('album_photo')
            ->where('album_id', $albumId)
            ->get();

        // Delete physical files
        foreach ($photos as $photo) {
            if ($photo->storage_path) {
                Storage::disk('public')->delete($photo->storage_path);
            }
        }

        // Delete photo records from database
        DB::table('album_photo')
            ->where('album_id', $albumId)
            ->delete();

        // Delete the album
        DB::table('album')
            ->where('album_id', $albumId)
            ->delete();

        return redirect()
            ->route('profile.albums.index')
            ->with('success', 'Album "' . $album->album_name . '" deleted successfully.');
    }

    /**
     * Show the add photos page.
     */
    public function createPhotos($albumId)
    {
        $user = Auth::user();

        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        return view('profile.albums.photos.create', compact('album'));
    }

    /**
     * Store photos in the album.
     */
    public function storePhotos(Request $request, $albumId)
    {
        $user = Auth::user();

        // Verify album belongs to user
        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        // Validate
        $validated = $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $uploadedCount = 0;
        $firstPhotoUrl = null;

        // Get all files
        $files = $request->file('photos');

        // If it's a single file, make it an array
        if ($files && !is_array($files)) {
            $files = [$files];
        }

        // Loop through and upload each file
        if ($files && count($files) > 0) {
            foreach ($files as $photo) {
                // Check if file is valid
                if ($photo && $photo->isValid()) {
                    // Store the photo with unique name
                    $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $path = $photo->storeAs('album_photos', $filename, 'public');
                    
                    // Store the full URL
                    $fullUrl = asset('storage/' . $path);
                    
                    DB::table('album_photo')->insert([
                        'album_id' => $albumId,
                        'photo_url' => $fullUrl,
                        'storage_path' => $path,
                        'created_at' => now(),
                    ]);

                    $uploadedCount++;
                    
                    // Store first photo URL for cover
                    if (!$firstPhotoUrl) {
                        $firstPhotoUrl = $fullUrl;
                    }
                }
            }
        }

        // If no files uploaded, show error
        if ($uploadedCount === 0) {
            return redirect()
                ->back()
                ->withErrors(['photos' => 'Please select at least one valid image.'])
                ->withInput();
        }

        // Update album cover with first photo if no cover exists
        if ($firstPhotoUrl && !$album->cover_photo_url) {
            DB::table('album')
                ->where('album_id', $albumId)
                ->update([
                    'cover_photo_url' => $firstPhotoUrl,
                    'updated_at' => now(),
                ]);
        }

        return redirect()
            ->route('profile.albums.show', $albumId)
            ->with('success', $uploadedCount . ' photo(s) added to "' . $album->album_name . '" successfully!');
    }

    /**
     * Delete a single photo from an album.
     */
    public function deletePhoto($albumId, $photoId)
    {
        $user = Auth::user();

        // Verify album belongs to user
        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        $photo = DB::table('album_photo')
            ->where('album_photo_id', $photoId)
            ->where('album_id', $albumId)
            ->first();

        if ($photo) {
            // Delete the physical file
            if ($photo->storage_path) {
                Storage::disk('public')->delete($photo->storage_path);
            }

            // Delete from database
            DB::table('album_photo')
                ->where('album_photo_id', $photoId)
                ->delete();

            // Update cover if the deleted photo was the cover
            $newCover = DB::table('album_photo')
                ->where('album_id', $albumId)
                ->orderBy('created_at')
                ->first();

            DB::table('album')
                ->where('album_id', $albumId)
                ->update([
                    'cover_photo_url' => $newCover ? $newCover->photo_url : null,
                    'updated_at' => now(),
                ]);
        }

        return redirect()
            ->route('profile.albums.show', $albumId)
            ->with('success', 'Photo deleted successfully.');
    }

    /**
     * Update album cover photo.
     */
    public function updateCover($albumId, $photoId)
    {
        $user = Auth::user();

        // Verify album belongs to user
        $album = DB::table('album')
            ->where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$album) {
            abort(404, 'Album not found.');
        }

        $photo = DB::table('album_photo')
            ->where('album_photo_id', $photoId)
            ->where('album_id', $albumId)
            ->first();

        if (!$photo) {
            abort(404, 'Photo not found.');
        }

        DB::table('album')
            ->where('album_id', $albumId)
            ->update([
                'cover_photo_url' => $photo->photo_url,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('profile.albums.show', $albumId)
            ->with('success', 'Album cover updated successfully!');
    }
}