@php
    $riwayat = $trip->history;
    $jalur = $trip->trail;
    $pos = $this->posTerjauh();
    $durasi = $this->durasi();

    $penandaPos = $jalur->checkpoints
        ->filter(fn ($p) => $p->latitude !== null && $p->longitude !== null)
        ->sortBy('sequence')
        ->map(fn ($p) => [
            'lng' => (float) $p->longitude,
            'lat' => (float) $p->latitude,
            'label' => $p->sequence.'. '.$p->name,
        ])
        ->values()
        ->all();
@endphp

<div class="py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        {{--
            Nadanya mencatat, bukan menilai. Pendakian yang dibatalkan karena cuaca
            adalah keputusan yang benar, dan halaman ini tidak boleh membuatnya terbaca
            seperti kegagalan.
        --}}
        <x-ui.page-header :title="$jalur->name"
            :description="$jalur->mountain->name.' · '.$jalur->mountain->province">
            <p class="mt-1 text-sm text-secondary">
                {{ $riwayat->completed_at->translatedFormat('l, d F Y') }}
            </p>
            <span class="mt-2 inline-block rounded-control bg-surface-sunken px-2.5 py-1 text-sm font-medium text-secondary">
                {{ $riwayat->completion_state->label() }}
            </span>
        </x-ui.page-header>

        {{-- Satu kelompok angka, bukan empat kartu setara yang tidak memilih apa pun. --}}
        <dl class="grid grid-cols-2 gap-x-8 gap-y-6 border-y border-subtle py-6 sm:grid-cols-4">
            <div>
                <dt class="text-sm text-muted">Lama pendakian</dt>
                {{-- Null bukan nol: hike mode tidak selalu dibuka, dan "0 jam" akan
                     menjadi pernyataan yang salah tentang pendakian yang nyata. --}}
                <dd data-angka class="mt-1 text-xl font-semibold text-primary">{{ $durasi ?? 'tidak tercatat' }}</dd>
            </div>

            <div>
                <dt class="text-sm text-muted">Pos terjauh</dt>
                <dd class="mt-1 text-xl font-semibold text-primary">{{ $pos?->name ?? 'tidak tercatat' }}</dd>
            </div>

            <div>
                <dt class="text-sm text-muted">Elevation gain jalur</dt>
                <dd data-angka class="mt-1 text-xl font-semibold text-primary">
                    {{ $jalur->elevation_gain_m ? number_format($jalur->elevation_gain_m, 0, ',', '.').' m' : 'belum ada' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-muted">Persiapan terkonfirmasi</dt>
                <dd data-angka class="mt-1 text-xl font-semibold text-primary">{{ $riwayat->preparation_completion_percent }}%</dd>
            </div>
        </dl>

        {{--
            Jejak pendaki didahulukan di atas garis jalur resmi ketika ada, karena inilah
            yang membedakan halaman hasil dari sekadar ringkasan: yang digambar adalah
            perjalanannya sendiri.

            Keduanya tidak boleh tertukar. Jejak yang kebetulan mirip jalur resmi membuat
            pendaki mengira aplikasi ini memverifikasi bahwa ia berjalan di jalur yang
            benar, dan aplikasi ini tidak melakukan itu.
        --}}
        @if ($jejakSaya = $this->jejak())
            <x-ui.card title="Jejak yang Anda tempuh"
                subtitle="Rekaman dari perangkat Anda, bukan penilaian apakah Anda berjalan di jalur yang benar.">
                <x-ui.map id="peta-jejak" :geometry="$jejakSaya" :markers="$penandaPos"
                    :label="'Jejak pendakian Anda di '.$jalur->name" height="h-96" class="mt-2" />

                <form method="POST" action="{{ route('hike.track.destroy', $trip->hikingSession) }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    {{-- Hak menghapus berada di tempat jejaknya terlihat, bukan
                         disembunyikan di halaman pengaturan yang terpisah. --}}
                    <x-ui.button type="submit" variant="secondary" size="sm"
                        confirm="Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.">
                        Hapus jejak ini
                    </x-ui.button>
                </form>
            </x-ui.card>
        @endif

        @if ($geometri = $jalur->readGeoJson('geometry'))
            <x-ui.card title="Jalur yang ditempuh"
                subtitle="Garis ini adalah jalur resmi, bukan rekaman GPS perjalanan Anda.">
                <x-ui.map id="peta-hasil" :geometry="$geometri" :markers="$penandaPos"
                    :label="'Peta jalur '.$jalur->name" class="mt-2" />
            </x-ui.card>
        @elseif ($penandaPos !== [])
            <x-ui.card title="Pos di jalur ini">
                <x-ui.map id="peta-hasil" :markers="$penandaPos"
                    :label="'Peta pos jalur '.$jalur->name" />
            </x-ui.card>
        @endif

        @if ($jalur->elevation_profile)
            <x-ui.card title="Profil elevasi">
                <x-ui.elevation-profile :profile="$jalur->elevation_profile" />
            </x-ui.card>
        @endif

        @if ($jalur->checkpoints->isNotEmpty())
            <x-ui.card title="Pos yang dilalui">
                <x-ui.checkpoint-journey :checkpoints="$jalur->checkpoints" class="space-y-1" />
            </x-ui.card>
        @endif

        @if ($riwayat->personal_notes)
            <x-ui.card title="Catatan Anda">
                <p class="text-sm text-secondary">{{ $riwayat->personal_notes }}</p>
            </x-ui.card>
        @endif

        <div class="flex flex-wrap gap-3">
            <x-ui.button variant="secondary" href="{{ route('history') }}">Kembali ke riwayat</x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('trails.show', $jalur) }}">Buka halaman jalur</x-ui.button>
        </div>
    </div>
</div>
