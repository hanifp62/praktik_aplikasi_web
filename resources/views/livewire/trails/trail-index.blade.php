<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Jelajahi Jalur"
            description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />

        @if (! auth()->user()?->hasCompletedProfile())
            {{-- Ditawarkan jalan keluarnya, bukan dinilai diam-diam. Kecocokan tanpa
                 profil adalah label yang terlihat pasti dan berdasar ketiadaan. --}}
            <x-ui.card class="mb-6" title="Lengkapi profil untuk melihat kecocokan"
                subtitle="Tanpa data pengalaman Anda, daftar ini hanya katalog jalur. Dengan profil, tiap jalur menyebut cocok atau tidaknya untuk Anda beserta alasannya.">
                <x-ui.button href="{{ route('onboarding') }}">Isi profil sekarang</x-ui.button>
            </x-ui.card>
        @endif

        <form class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 sm:grid-cols-3"
            role="search" aria-label="Filter jalur">
            <div>
                <x-input-label for="search" value="Cari jalur atau gunung" />
                <x-text-input id="search" wire:model.live.debounce.400ms="search" class="mt-1 block w-full"
                    placeholder="Merbabu" />
            </div>
            <div>
                <x-input-label for="technical" value="Tingkat teknis" />
                <select id="technical" wire:model.live="technical"
                    class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600">
                    <option value="">Semua</option>
                    {{-- daftar tetap: pilihan yang ditetapkan di kode, tidak pernah kosong. --}}
                    @foreach ($technicalLevels as $level)
                        <option value="{{ $level->value }}">{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="region" value="Wilayah" />
                <x-text-input id="region" wire:model.live.debounce.400ms="region" class="mt-1 block w-full"
                    placeholder="Jawa Tengah" />
            </div>
        </form>

        @if ($trails->isEmpty())
            <x-ui.empty-state title="Tidak ada jalur yang cocok"
                description="Saringan yang sedang aktif tidak menemukan jalur. Melonggarkan tingkat teknis atau mengosongkan wilayah biasanya menolong lebih cepat daripada mengubah kata pencarian." />
        @else
            {{--
                Daftar, bukan kisi kartu.

                Kisi dua kolom memuat empat jalur di satu layar; daftar memuat delapan,
                dan yang dilakukan orang di halaman ini membandingkan, bukan membaca satu
                per satu. Dipisah garis rambut, tanpa kotak: kotak di sekeliling setiap
                baris menggambar sembilan bingkai untuk memisahkan sembilan hal yang
                urutannya sudah jelas.
            --}}
            <p class="mb-2 text-sm text-muted">{{ $trails->total() }} jalur cocok</p>

            <div class="border-t border-subtle">
                @foreach ($trails as $trail)
                    <x-ui.trail-row :trail="$trail" :fit="$ringkasanFit[$trail->id] ?? null" />
                @endforeach
            </div>

            <div class="mt-6">{{ $trails->links() }}</div>
        @endif

        {{--
            Bagian terpisah, tidak pernah dicampur ke hasil di atas.

            Pendaki yang mencari gunung yang jalurnya belum berdata tanpa ini hanya
            melihat layar kosong, seolah gunungnya tidak ada. Padahal yang belum ada
            adalah keterangannya, dan itu keadaan yang berbeda.
        --}}
        @if ($menunggu->isNotEmpty())
            <section class="mt-10 border-t border-subtle pt-6">
                <h2 class="text-lg font-semibold text-primary">Jalur yang datanya belum tersedia</h2>
                <p class="mt-1 text-sm text-secondary">
                    Jalur berikut sudah dikenali sistem, tetapi keterangannya belum dimasukkan
                    pihak yang berwenang. Belum dapat direncanakan dari sini.
                </p>

                <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($menunggu as $jalur)
                        <li>
                            <a href="{{ route('trails.show', $jalur->slug) }}" wire:navigate
                                class="block rounded-lg border border-subtle p-4 transition hover:bg-surface-sunken
                                    focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                <span class="block font-medium text-primary">{{ $jalur->name }}</span>
                                <span class="mt-0.5 block text-sm text-secondary">
                                    {{ $jalur->mountain->name }} &middot; {{ $jalur->mountain->province }}
                                </span>
                                <span class="mt-2 inline-block rounded-control bg-warn-100 px-2 py-0.5 text-xs font-medium text-warn-900">
                                    Menunggu masukan pengelola
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
