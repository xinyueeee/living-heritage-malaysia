<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Discover authentic cultural experiences across Malaysia.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Living Heritage Malaysia')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<script>
async function updateNotificationBadge()
{
    try
    {
        const response = await fetch("{{ route('notifications.count') }}");
        const data = await response.json();

        const badge = document.getElementById("notificationBadge");

        if (data.count > 0)
        {
            badge.style.display = "flex";
            badge.textContent = data.count > 9 ? "9+" : data.count;
        }
        else
        {
            badge.style.display = "none";
        }
    }
    catch (error)
    {
        console.error(error);
    }
}

// Load immediately
updateNotificationBadge();

// Refresh every 10 seconds
setInterval(updateNotificationBadge, 10000);
</script>
<body>
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @auth
        @include('components.saved-experience-picker', ['collections' => $savePickerCollections])
    @endauth

    @include('partials.footer')

    @if (request()->routeIs('home', 'experiences.*', 'recommendations.*'))
        <x-discovery-assistant />
    @endif

    @stack('styles')
    @stack('scripts')
</body>
</html>
