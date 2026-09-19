<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-ui.page-header
                title="Selamat datang, {{ auth()->user()->name }}"
                description="Alurnya: lengkapi profil, tentukan rencana, lihat kecocokan jalur, siapkan kebutuhan, lalu periksa kondisi sebelum berangkat." />

            @if (! auth()->user()->hasCompletedProfile())
                <x-ui.card title="Lengkapi profil pendaki"
                    subtitle="Rekomendasi jalur membutuhkan data pengalaman dan preferensi Anda.">
                    <a href="{{ route('onboarding') }}" wire:navigate
                        class="inline-block rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        Isi profil sekarang
                    </a>
                </x-ui.card>
            @else
                <x-ui.card title="Mulai rencana pendakian"
                    subtitle="Tentukan target perjalanan Anda untuk melihat jalur yang sesuai.">
                    <a href="{{ route('goals.create') }}" wire:navigate
                        class="inline-block rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        Buat rencana pendakian
                    </a>
                </x-ui.card>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.card title="Jalur">
                    <a href="{{ route('trails.index') }}" wire:navigate class="text-sm text-emerald-700 underline">
                        Jelajahi jalur
                    </a>
                </x-ui.card>
                <x-ui.card title="Trip">
                    <a href="{{ route('trips.index') }}" wire:navigate class="text-sm text-emerald-700 underline">
                        Rencana trip saya
                    </a>
                </x-ui.card>
                <x-ui.card title="Riwayat">
                    <a href="{{ route('history') }}" wire:navigate class="text-sm text-emerald-700 underline">
                        Riwayat pendakian
                    </a>
                </x-ui.card>
            </div>

            <x-ui.card>
                <p class="text-sm text-gray-600">
                    Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu
                    gunung atau jalur aman, dan tidak menggantikan informasi resmi dari pengelola.
                </p>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
