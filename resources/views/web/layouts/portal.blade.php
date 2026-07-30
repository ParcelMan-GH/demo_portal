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
    
    <!-- Google Fonts: Bricolage Grotesque & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-white text-slate-900 antialiased">
    <div class="relative min-h-screen">
        @yield('content')
    </div>
</body>
</html>