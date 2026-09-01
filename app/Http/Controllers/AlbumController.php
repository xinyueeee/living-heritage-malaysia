<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AlbumController extends Controller
{
    /**
     * Display all albums belonging to the logged-in user.
     */
    public function index()
    {
        $user = Auth::user();

        $albums = Album::where('user_id', $user->user_id)
            ->withCount('photos')
            ->orderByDesc('created_at')
            ->get();

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
     * Store a new album with optional multiple photos.
     *
     * The first uploaded photo becomes the default album cover.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'album_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:20'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        // Create album first
        $album = Album::create([
            'user_id' => $user->user_id,
            'album_name' => trim($validated['album_name']),
            'description' => !empty($validated['description'])
                ? trim($validated['description'])
                : null,
        ]);

        $firstPhotoUrl = null;

        // Upload photos if provided
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {

                if (!$photo || !$photo->isValid()) {
                    continue;
                }

                $publicUrl = $this->uploadPhotoToSupabase($photo);

                AlbumPhoto::create([
                    'album_id' => $album->album_id,
                    'photo_url' => $publicUrl,
                    'storage_path' => $this->getStoragePathFromUrl($publicUrl),
                    'created_at' => now(),
                ]);

                // First uploaded photo becomes default cover
                if (!$firstPhotoUrl) {
                    $firstPhotoUrl = $publicUrl;
                }
            }
        }

        // Set first photo as album cover
        if ($firstPhotoUrl) {
            $album->update([
                'cover_photo_url' => $firstPhotoUrl,
            ]);
        }

        return redirect()
            ->route('profile.albums.show', $album->album_id)
            ->with(
                'success',
                'Album "' . $album->album_name . '" created successfully!'
            );
    }


    /**
     * Display one album and all its photos.
     */
    public function show($albumId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $photos = $album->photos()
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

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        return view('profile.albums.edit', compact('album'));
    }


    /**
     * Update album name and description.
     */
    public function update(Request $request, $albumId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $validated = $request->validate([
            'album_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $album->update([
            'album_name' => trim($validated['album_name']),
            'description' => !empty($validated['description'])
                ? trim($validated['description'])
                : null,
        ]);

        return redirect()
            ->route('profile.albums.show', $album->album_id)
            ->with('success', 'Album updated successfully!');
    }


    /**
     * Delete an album and all its photos from Supabase Storage.
     */
    public function destroy($albumId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $photos = $album->photos()->get();

        // Delete photos from Supabase Storage
        $this->deleteFromSupabaseStorage($photos);

        // Delete photo records
        $album->photos()->delete();

        // Delete album
        $album->delete();

        return redirect()
            ->route('profile.albums.index')
            ->with(
                'success',
                'Album "' . $album->album_name . '" deleted successfully.'
            );
    }


    /**
     * Show the add photos page.
     */
    public function createPhotos($albumId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        return view('profile.albums.photos.create', compact('album'));
    }


    /**
     * Store multiple photos in an existing album.
     */
    public function storePhotos(Request $request, $albumId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $validated = $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $uploadedCount = 0;
        $firstPhotoUrl = null;

        foreach ($request->file('photos', []) as $photo) {

            if (!$photo || !$photo->isValid()) {
                continue;
            }

            $publicUrl = $this->uploadPhotoToSupabase($photo);

            AlbumPhoto::create([
                'album_id' => $album->album_id,
                'photo_url' => $publicUrl,
                'storage_path' => $this->getStoragePathFromUrl($publicUrl),
                'created_at' => now(),
            ]);

            $uploadedCount++;

            if (!$firstPhotoUrl) {
                $firstPhotoUrl = $publicUrl;
            }
        }

        if ($uploadedCount === 0) {
            return redirect()
                ->back()
                ->withErrors([
                    'photos' => 'Please select at least one valid image.',
                ])
                ->withInput();
        }

        // Only set a default cover if the album does not already have one
        if (!$album->cover_photo_url && $firstPhotoUrl) {
            $album->update([
                'cover_photo_url' => $firstPhotoUrl,
            ]);
        }

        return redirect()
            ->route('profile.albums.show', $album->album_id)
            ->with(
                'success',
                $uploadedCount .
                ' photo(s) added to "' .
                $album->album_name .
                '" successfully!'
            );
    }


    /**
     * Delete one photo from an album.
     */
    public function deletePhoto($albumId, $photoId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $photo = AlbumPhoto::where('album_photo_id', $photoId)
            ->where('album_id', $album->album_id)
            ->firstOrFail();

        $wasCover = $album->cover_photo_url === $photo->photo_url;

        // Delete from Supabase Storage
        $this->deleteSingleFromSupabaseStorage($photo->storage_path);

        // Delete photo record
        $photo->delete();

        /*
         * If the deleted photo was the cover,
         * automatically select another remaining photo.
         */
        if ($wasCover) {

            $newCover = $album->photos()
                ->orderBy('created_at')
                ->first();

            $album->update([
                'cover_photo_url' => $newCover
                    ? $newCover->photo_url
                    : null,
            ]);
        }

        return redirect()
            ->route('profile.albums.show', $album->album_id)
            ->with('success', 'Photo deleted successfully.');
    }


    /**
     * Change the album cover photo.
     */
    public function updateCover($albumId, $photoId)
    {
        $user = Auth::user();

        $album = Album::where('album_id', $albumId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $photo = AlbumPhoto::where('album_photo_id', $photoId)
            ->where('album_id', $album->album_id)
            ->firstOrFail();

        $album->update([
            'cover_photo_url' => $photo->photo_url,
        ]);

        return redirect()
            ->route('profile.albums.show', $album->album_id)
            ->with('success', 'Album cover updated successfully!');
    }


    // ============================================================
    // PRIVATE HELPER METHODS
    // ============================================================

    /**
     * Upload one image to Supabase Storage.
     */
    private function uploadPhotoToSupabase($photo)
    {
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        $filename =
            time() .
            '_' .
            uniqid() .
            '.' .
            $photo->getClientOriginalExtension();

        $storagePath = 'album_photos/' . $filename;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$serviceRoleKey}",
            'apikey' => $serviceRoleKey,
            'Content-Type' => $photo->getMimeType(),
        ])
        ->withBody(
            file_get_contents($photo->getRealPath()),
            $photo->getMimeType()
        )
        ->post(
            "{$baseUrl}/storage/v1/object/community-images/{$storagePath}"
        );

        if ($response->failed()) {
            throw new \RuntimeException(
                'Supabase upload failed. Status: ' .
                $response->status() .
                ' Response: ' .
                $response->body()
            );
        }

        return "{$baseUrl}/storage/v1/object/public/community-images/{$storagePath}";
    }


    /**
     * Get the Supabase storage path from a public image URL.
     */
    private function getStoragePathFromUrl($publicUrl)
    {
        $marker = '/storage/v1/object/public/community-images/';

        $position = strpos($publicUrl, $marker);

        if ($position === false) {
            return null;
        }

        return substr(
            $publicUrl,
            $position + strlen($marker)
        );
    }


    /**
     * Delete multiple photos from Supabase Storage.
     */
    private function deleteFromSupabaseStorage($photos)
    {
        if ($photos->isEmpty()) {
            return;
        }

        foreach ($photos as $photo) {
            if ($photo->storage_path) {
                $this->deleteSingleFromSupabaseStorage(
                    $photo->storage_path
                );
            }
        }
    }


    /**
     * Delete one photo from Supabase Storage.
     */
    private function deleteSingleFromSupabaseStorage($storagePath)
    {
        if (!$storagePath) {
            return;
        }

        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        Http::withHeaders([
            'Authorization' => "Bearer {$serviceRoleKey}",
            'apikey' => $serviceRoleKey,
        ])->delete(
            "{$baseUrl}/storage/v1/object/community-images/{$storagePath}"
        );
    }
}