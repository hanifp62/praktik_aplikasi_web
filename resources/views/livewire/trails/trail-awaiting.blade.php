<div class="py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        {{--
            Halaman untuk jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak
            yang berwenang.

            Nadanya sengaja bukan permintaan maaf. Kekosongan ini bukan kerusakan,
            melainkan tahapan: jalurnya nyata dan dikenal pendaki, dan yang belum ada
            adalah keterangan dari pihak yang berhak menyatakannya. Menyebut siapa yang
            ditunggu mengubah layar kosong menjadi keterangan.
        --}}
        <x-ui.page-header :title="$trail->name">
            <p class="mt-1 text-sm text-gray-600">
                {{ $trail->mountain->name }} &middot; {{ $trail->mountain->province }} &middot;
                {{ $trail->mountain->elevation_mdpl }} mdpl
            </p>
        </x-ui.page-header>

        <x-ui.card title="Data jalur ini belum tersedia">
            <p class="text-sm text-gray-700">
                Jalur ini sudah dikenali sistem, tetapi keterangannya belum dimasukkan
                @if ($badan)
                    <span class="font-medium text-gray-900">{{ $badan->displayName() }}</span>
                    atau pemandu bersertifikat yang disahkan untuk kawasan ini.
                @else
                    pihak yang berwenang atas kawasan ini.
                @endif
            </p>

            @if ($menunggu !== [])
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-900">Yang belum tersedia:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">
                        @foreach ($menunggu as $butir)
                            <li>{{ $butir }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{--
                Bagian terpenting halaman ini. Pendaki yang sampai ke sini sedang mencari
                jawaban, dan jawabannya memang ada, hanya bukan di sini.
            --}}
            <p class="mt-4 rounded-md border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                Selama keterangannya belum ada, jangan menganggap jalur ini terbuka maupun
                tertutup. Tanyakan langsung ke
                {{ $badan?->displayName() ?? 'pengelola kawasan' }} atau ke basecamp sebelum
                merencanakan keberangkatan.
            </p>
        </x-ui.card>

        <x-ui.card title="Mengapa halaman ini ada">
            <p class="text-sm text-gray-700">
                Sistem ini menampilkan jalur yang datanya belum lengkap alih-alih
                menyembunyikannya, supaya terlihat apa yang memang belum diketahui. Jalur
                akan tampil utuh setelah sumber data, karakteristik, daftar pos, status
                resmi, dan garis jalurnya di peta dilengkapi.
            </p>

            @if ($badan?->website)
                <p class="mt-3 text-sm text-gray-700">
                    Kanal resmi pengelola:
                    <a href="{{ $badan->website }}" rel="noopener noreferrer" target="_blank"
                        class="text-brand-700 underline">{{ $badan->website }}</a>
                </p>
            @endif
        </x-ui.card>

        <div class="flex flex-wrap gap-3">
            <x-ui.button variant="secondary" href="{{ route('trails.index') }}">Telusuri jalur lain</x-ui.button>
        </div>
    </div>
</div>
