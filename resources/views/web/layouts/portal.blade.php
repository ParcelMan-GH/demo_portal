<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Parcelman is an app-led parcel pickup and delivery service for Ghanaian vendors, shops, and growing businesses.')">
    <meta name="keywords" content="@yield('meta_keywords', 'Parcelman Ghana, parcel delivery Ghana, vendor delivery Ghana, pickup request app Ghana, delivery app for Ghanaian vendors')">
    <title>@yield('title', 'Parcelman Portal')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_15%,rgba(251,146,60,0.22),transparent_35%),radial-gradient(circle_at_85%_5%,rgba(59,130,246,0.25),transparent_38%),radial-gradient(circle_at_50%_100%,rgba(14,116,144,0.28),transparent_42%)]"></div>
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_bottom,rgba(15,23,42,0.7),rgba(2,6,23,0.92))]"></div>

        <div class="relative z-10">
            @yield('content')
        </div>
    </div>
</body>
</html>
