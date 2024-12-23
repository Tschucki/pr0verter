<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" sizes="32x32 16x16" href="/favicon.ico">
    <script defer data-domain="pr0verter.de" src="https://plausible.marcelwagner.dev/js/script.js"></script>
    @routes
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body>
@inertia
</body>
</html>
