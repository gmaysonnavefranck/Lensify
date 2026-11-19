<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="motion-safe:scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Theme before first paint, so dark mode doesn't flash white: saved choice, else the system setting.
             Pages before the app (landing, login, registration) are always light. -->
        @unless ($page['component'] === 'Welcome' || str_starts_with($page['component'], 'Auth/'))
        <script>
            try {
                const theme = localStorage.getItem('lensify.theme');
                if (theme === 'dark' || (!theme && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>
        @endunless

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-inter antialiased select-none">
        @inertia
    </body>
</html>
