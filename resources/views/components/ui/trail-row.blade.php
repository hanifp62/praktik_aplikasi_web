@props(['trail', 'fit' => null, 'ditimbang' => false])

@php
    use App\Enums\WaterAvailability;
    use App\Support\Durasi;

    $jarak = $trail->distance_km !== null ? (float) $trail->distance_km : null;
    $naik = $trail->elevation_gain_m;

    // Kecuraman diturunkan dari dua angka yang sudah tersimpan, bukan kolom baru. Meter
    // per kilometer adalah ukuran yang dipakai pendaki, dan dua jalur sepanjang lima
    // kilometer bisa sangat berbeda karenanya.
    //
    // Hanya muncul ketika kedua angkanya ada. Membagi dengan nol atau dengan ketiadaan
    // menghasilkan angka yang terlihat pasti tanpa berdasar apa pun (PRD §91).
    $curam = ($jarak && $naik) ? round($naik / $jarak) : null;

    // Air yang belum diketahui tidak ikut. Labelnya "Belum diketahui", dan menaruhnya
    // sebaris dengan "Bisa berkemah" menyajikan ketiadaan keterangan seolah ia
    // keterangan: yang membaca sekilas akan mengira ada sesuatu yang sudah diperiksa.
    $kemudahan = collect([
        $trail->camping_available ? 'Bisa berkemah' : null,
        $trail->water_availability !== WaterAvailability::UNKNOWN
            ? $trail->water_availability?->label()
            : null,
    ])->filter()->values();
@endphp

{{--
    Baris jalur, bukan baris basis data.

    Yang digantikan: judul, satu anak judul, lalu tiga pasang dt/dd. Sepuluh kolom
    tersedia pada sebuah jalur dan kartunya memakai tiga, dan yang tidak ikut justru
    estimasi durasi, yaitu angka yang paling menentukan ketika orang memutuskan jalur
    mana yang akan didaki akhir pekan ini.

    Susunannya horizontal dan dipisah garis rambut, bukan kotak dalam kisi. Kotak dua
    kolom memuat empat jalur di satu layar; baris memuat delapan, dan yang dilakukan
    orang di halaman ini membandingkan, bukan membaca satu per satu.

    Seluruh baris menjadi sasaran tautan lewat after:absolute, bukan hanya judulnya:
    tautan selebar judul memaksa ketepatan yang tidak dimiliki orang yang sedang berjalan.
--}}
<article {{ $attributes->merge(['class' => 'group relative border-b border-subtle py-5']) }}>
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h2 class="text-lg font-semibold text-primary">
            <a href="{{ route('trails.show', $trail) }}" wire:navigate
                class="rounded-sm after:absolute after:inset-0 group-hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                {{ $trail->name }}
            </a>
        </h2>

        {{-- Tingkat teknis membawa katanya sendiri, tidak pernah warna saja (WCAG 1.4.1). --}}
        <span class="shrink-0 rounded-control border border-subtle bg-surface-sunken px-2 py-0.5 text-xs font-medium text-secondary">
            {{ $trail->technical_demand->label() }}
        </span>
    </div>

    <p class="mt-0.5 text-sm text-secondary">
        {{ $trail->mountain->name }} &middot; {{ $trail->mountain->province }}
    </p>

    {{--
        Durasi mendapat bobot terbesar. Traveloka menaruh harga di posisi tetap yang
        selalu terbaca lebih dulu; di sini angka setara itu bukan jarak, melainkan berapa
        lama ia akan berjalan.
    --}}
    <dl class="mt-3 flex flex-wrap items-baseline gap-x-6 gap-y-2">
        <div class="flex items-baseline gap-1.5">
            <dt class="sr-only">Perkiraan durasi</dt>
            <dd data-angka class="text-xl font-semibold leading-none text-primary">{{ Durasi::pendek($trail->estimated_duration_minutes) }}</dd>
        </div>

        <div class="flex items-baseline gap-1.5 text-sm">
            <dt class="text-muted">Jarak</dt>
            <dd data-angka class="font-medium text-primary">{{ $jarak !== null ? number_format($jarak, 1, ',', '.').' km' : '-' }}</dd>
        </div>

        <div class="flex items-baseline gap-1.5 text-sm">
            <dt class="text-muted">Tanjakan</dt>
            <dd data-angka class="font-medium text-primary">{{ $naik !== null ? number_format($naik, 0, ',', '.').' m' : '-' }}</dd>
        </div>

        @if ($curam !== null)
            <div class="flex items-baseline gap-1.5 text-sm">
                <dt class="text-muted">Kecuraman</dt>
                <dd data-angka class="font-medium text-primary">{{ number_format($curam, 0, ',', '.') }} m/km</dd>
            </div>
        @endif
    </dl>

    @if ($kemudahan->isNotEmpty())
        <p class="mt-2 text-xs text-muted">{{ $kemudahan->implode(' · ') }}</p>
    @endif

    @if ($fit)
        <x-ui.fit-line :summary="$fit" />
    @endif

    {{-- Di atas lapisan tautan baris lewat relative z-10: tanpa itu, after:inset-0
         milik judul menutupi tombolnya dan menimbang jalur justru membuka jalurnya. --}}
    <div class="relative z-10 mt-3">
        <x-ui.button size="sm" variant="secondary" wire:click="timbang({{ $trail->id }})">
            {{ $ditimbang ? 'Keluarkan dari timbangan' : 'Timbang jalur ini' }}
        </x-ui.button>
    </div>
</article>
