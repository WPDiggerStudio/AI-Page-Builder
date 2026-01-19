<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'WP Jarvis')</title>
    @stack('styles')
</head>
<body class="wpjarvis-admin">
    <div class="wpjarvis-wrapper">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
