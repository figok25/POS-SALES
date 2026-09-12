<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-700">Selamat datang, <strong>{{ auth()->user()->name }}</strong>.</p>
        <p class="text-sm text-gray-500 mt-2">
            Ini adalah Dashboard Admin (Fase 1 — Core Infrastructure). Modul Master Data, Inventory,
            Distribution, Finance, Operations, Reports akan dibangun pada fase-fase berikutnya
            sesuai Development Priority di blueprint.
        </p>
    </div>
</x-admin-layout>
