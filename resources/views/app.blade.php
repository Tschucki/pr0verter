<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" sizes="32x32 16x16" href="/favicon.ico">
    <!-- Privacy-friendly analytics by Plausible -->
    <script async src="https://plausible.marcelwagner.dev/js/pa-kCJsKz6aQVDj5gyj8CUE5.js"></script>
    <script>
      window.plausible=window.plausible||function(){(plausible.q=plausible.q||[]).push(arguments)},plausible.init=plausible.init||function(i){plausible.o=i||{}};
      plausible.init()
    </script>
    @routes
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body>
@inertia
</body>
</html>
