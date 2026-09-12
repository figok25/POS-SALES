<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Sales</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 pb-16">
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="px-4 py-3 flex items-center justify-between">
            <span class="font-semibold text-gray-800">{{ config('app.name', 'POS & Sales') }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600">Logout</button>
            </form>
        </div>
        @isset($header)
            <div class="px-4 pb-2 text-sm text-gray-500">{{ $header }}</div>
        @endisset
    </header>

    <main class="p-4 max-w-md mx-auto">
        {{ $slot }}
    </main>

    {{-- Bottom nav mobile-friendly, struktur sesuai Blueprint #48 Sales Navigation --}}
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t flex justify-around text-xs text-gray-600 py-2">
        <a href="{{ route('sales.dashboard') }}" class="flex flex-col items-center gap-0.5">
            <span>Dashboard</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-0.5">
            <span>Customer</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-0.5">
            <span>Transaksi</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-0.5">
            <span>Stock</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-0.5">
            <span>Profile</span>
        </a>
    </nav>
</body>
</html>
