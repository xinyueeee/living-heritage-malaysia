<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Local-only shortcut to sign in as a seeded dummy user without going
 * through the real Google/Supabase OAuth flow. Never registered outside
 * app()->environment('local') — see routes/web.php.
 */
class DevLoginController extends Controller
{
    public function __construct()
    {
        abort_unless(app()->environment('local'), 404);
    }

    public function index(): View
    {
        $users = User::orderBy('user_name')->get();

        return view('dev.login', compact('users'));
    }

    public function login(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
