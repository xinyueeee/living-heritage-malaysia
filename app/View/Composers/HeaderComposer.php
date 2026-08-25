<?php

namespace App\View\Composers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HeaderComposer
{
    public function compose(View $view): void
    {
        $unreadCount = Auth::check()
            ? Notification::query()
                ->where('user_id', Auth::id())
                ->where('is_read', false)
                ->count()
            : 0;

        $view->with('unreadCount', $unreadCount);
    }
}
