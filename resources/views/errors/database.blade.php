<x-guest-layout>
    <div class="text-center py-12">
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">Terjadi Kesalahan</h1>
        <p class="text-gray-600">{{ $message }}</p>
        <a href="{{ url()->previous() }}" class="inline-block mt-6 text-indigo-600 hover:underline">
            &larr; Kembali
        </a>
    </div>
</x-guest-layout>
