<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1a202c;
            margin: 0;
            padding: 0;
        }
        /* Reset any default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 md:p-0">
    <div class="flex flex-col md:flex-row w-full max-w-6xl mx-auto bg-gray-900 rounded-lg shadow-xl overflow-hidden">
        <!-- Left Column: Image and Promotional Text -->
        <div class="md:w-1/2 flex flex-col items-center justify-center p-0 text-white text-center bg-gray-800 relative overflow-hidden rounded-l-lg min-h-[600px]">
            <!-- Background Image (fully visible) -->
            <img
                src="/images/safeguardx.jpg"
                alt="SafeguardX Graphic"
                class="w-full h-full object-cover"
                style="position: absolute; inset: 0; z-index: 0; background: #222;"
                onerror="this.onerror=null;this.src='https://placehold.co/600x400/374151/FFFFFF?text=Error+Loading+Image';"
            >
            <!-- Content over the image (optional, can be removed if you want only the image) -->
            <div class="relative z-10 space-y-6 bg-black/40 w-full py-8">
                <div class="text-4xl font-bold text-green-400">{{ config('app.name', 'Laravel') }}</div>
                <h2 class="text-3xl md:text-4xl font-semibold leading-tight mt-4">
                    Secure Your Data. <br> Launch It Beyond Threats.
                </h2>
                <p class="text-lg text-gray-300 mt-2">
                    Protecting your digital assets with cutting-edge technology.
                </p>
            </div>
        </div>

        <!-- Right Column: Content -->
        <div class="md:w-1/2 bg-gray-900 p-8 md:p-12 flex flex-col justify-center rounded-r-lg min-h-[600px] max-w-xl w-full mx-auto relative">
            <!-- Back to Welcome Button (moved above the form) -->
            <a href="{{ url('/') }}" class="mb-6 self-start flex items-center space-x-2 bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow transition duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span>Back to Home</span>
            </a>
            {{ $slot }}

        
        </div>
    </div>
</body>
</html>