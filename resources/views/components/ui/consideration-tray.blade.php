@props(['trails'])

{{--
    Baki timbangan, ikut di halaman tempat orang memilih.

    Yang ditunjukkan bukan jumlah melainkan namanya, karena yang ingin diketahui pendaki
    "apa saja yang sedang saya timbang", bukan "berapa". Angka tanpa nama memaksa membuka
    halaman lain untuk mengingat, dan itu persis titik sakit yang ditemukan riset AllTrails
    dan Traveloka.
--}}
@if ($trails->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'sticky bottom-0 z-40 border-t border-subtle bg-surface/95 px-4 py-3 backdrop-blur']) }}
        style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px))">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-2">
            <p class="text-sm text-secondary">
                Sedang ditimbang ({{ $trails->count() }}/{{ \App\Services\ConsiderationService::BATAS }}):
                <span class="font-medium text-primary">{{ $trails->pluck('name')->implode(', ') }}</span>
            </p>

            @if ($trails->count() >= 2)
                {{--
                    route('trails.compare') TELANJANG, tanpa parameter trails: mengirim id
                    di sini membuat RouteComparison masuk mode URL-eksplisit (baca-saja),
                    sehingga removeTrail() di sana hanya menyunting URL dan baris di
                    trail_considerations tidak pernah terhapus -- jalur hilang dari tabel
                    tapi tetap "Sedang ditimbang" begitu kembali ke Jelajah. Baki ini
                    SATU-SATUNYA jalan kebanyakan pendaki menuju halaman perbandingan,
                    jadi tautan telanjang di sini wajib supaya mode timbangan-tersimpan
                    (yang menulis balik) benar-benar terjangkau, bukan mati langkah.
                --}}
                <x-ui.button size="sm" href="{{ route('trails.compare') }}">
                    Bandingkan {{ $trails->count() }} jalur
                </x-ui.button>
            @endif
        </div>
    </div>
@endif
