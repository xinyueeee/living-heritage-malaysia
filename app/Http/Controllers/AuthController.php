<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function callback(): View
    {
        return view('auth.callback');
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        try {
            $jwks = Cache::remember('supabase.jwks', 3600, function () {
                return Http::get(rtrim(config('services.supabase.url'), '/').'/auth/v1/.well-known/jwks.json')
                    ->throw()
                    ->json();
            });

            $payload = JWT::decode($validated['access_token'], JWK::parseKeySet($jwks));
        } catch (Throwable $e) {
            Log::warning('Supabase session verification failed.', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Your session could not be verified.'], 401);
        }

        $metadata = (array) ($payload->user_metadata ?? []);

        $user = User::updateOrCreate(
            ['user_id' => $payload->sub],
            [
                'user_name' => $metadata['full_name'] ?? $metadata['name'] ?? $payload->email,
                'user_email' => $payload->email,
                'profile_photo' => $metadata['avatar_url'] ?? $metadata['picture'] ?? null,
            ]
        );

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('home')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
