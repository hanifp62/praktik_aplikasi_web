<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$trip->name"
            :description="$trip->trail->name.' - '.$trip->trail->mountain->name">
            <p class="mt-1 text-sm text-secondary">
                {{ $trip->planned_date->translatedFormat('d M Y') }}
                @if ($trip->start_time)
                    {{-- Jam ini rencana pengguna, bukan instan UTC, jadi tidak dikonversi.
                         Penandanya tetap disebut: "mulai 06:00" ambigu bagi pendaki Jakarta
                         yang merencanakan Rinjani. --}}
                    &middot; mulai {{ \Illuminate\Support\Carbon::parse($trip->start_time)->format('H:i') }}
                    {{ \App\Support\Timezone::label(\App\Support\Timezone::forTrail($trip->trail)) }}
                @endif
                &middot; {{ $trip->trip_type->label() }}
            </p>
            <span class="mt-2 inline-block rounded-md bg-surface-sunken px-2.5 py-1 text-xs font-medium text-secondary">
                {{ $trip->status->label() }}
            </span>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.card title="Persiapan">
                <p class="text-2xl font-semibold text-primary">{{ $trip->preparationCompletionPercent() }}%</p>
                <a href="{{ route('trips.preparation', $trip) }}" wire:navigate
                    class="mt-2 inline-block text-sm text-brand-700 underline">Buka daftar persiapan</a>
            </x-ui.card>

            <x-ui.card title="Kesiapan">
                {{--
                    Vonis tersimpan tidak pernah disajikan sebagai keadaan sekarang ketika
                    status resmi jalurnya sudah berubah sejak penilaian. Menyebut label
                    lamanya di samping pengakuan ini justru memberi pendaki dua jawaban
                    yang bertentangan, jadi labelnya diganti, bukan didampingi.
                --}}
                @if ($trip->trailIsWithdrawn())
                    <p class="text-sm font-medium text-warn-900">Jalur ditarik dari katalog</p>
                    <p class="mt-1 text-xs text-secondary">
                        Keterangan jalur ini tidak lagi diperbarui di sini. Tanyakan keadaannya
                        langsung kepada pengelola kawasan.
                    </p>
                @elseif ($trip->readinessIsStale())
                    <p class="text-sm font-medium text-warn-900">Status jalur berubah, perlu dinilai ulang</p>
                    <p class="mt-1 text-xs text-secondary">
                        Penilaian terakhir dibuat ketika status resminya masih berbeda.
                    </p>
                @else
                    <p class="text-sm text-primary">
                        {{ $trip->latestReadinessCheck?->computed_state->label() ?? 'Belum dinilai' }}
                    </p>
                @endif
                <a href="{{ route('trips.readiness', $trip) }}" wire:navigate
                    class="mt-2 inline-block text-sm text-brand-700 underline">Cek kesiapan</a>
            </x-ui.card>
        </div>

        {{--
            Jendela pemesanan bergerak relatif terhadap tanggal yang sudah dipilih, jadi
            peringatan sekali pada saat rekomendasi tidak cukup. Nadanya mengikuti §43:
            sistem ini menyebut aturan dan penyelenggaranya, tidak pernah menyatakan
            izinnya sudah aman atau belum.
        --}}
        @if ($jendelaIzin)
            <x-ui.card title="Izin pendakian">
                <p @class([
                    'text-sm',
                    'font-medium text-warn-900' => $jendelaIzin['state'] === \App\Services\PermitService::BOOKING_DITUTUP,
                    'text-secondary' => $jendelaIzin['state'] !== \App\Services\PermitService::BOOKING_DITUTUP,
                ])>{{ $jendelaIzin['message'] }}</p>

                @if ($jendelaIzin['requirement']->booking_url)
                    <a href="{{ $jendelaIzin['requirement']->booking_url }}" rel="noopener noreferrer" target="_blank"
                        class="mt-2 inline-block text-sm text-brand-700 underline">Buka kanal pemesanan resmi</a>
                @endif
            </x-ui.card>
        @endif

        @if ($trip->notes)
            <x-ui.card title="Catatan">
                <p class="text-sm text-secondary">{{ $trip->notes }}</p>
            </x-ui.card>
        @endif

        {{-- Trip yang sudah selesai punya hasilnya sendiri, dan dari sinilah pendaki
             paling mungkin mencarinya, bukan lewat daftar riwayat. --}}
        @if ($trip->history)
            <x-ui.card title="Pendakian ini sudah selesai">
                <x-ui.button href="{{ route('history.summary', $trip) }}">Lihat hasil pendakian</x-ui.button>
            </x-ui.card>
        @endif

        @if ($trip->status->isActive())
            <x-ui.card title="Tindakan">
                <div class="flex flex-wrap gap-3">
                    @if ($trip->status !== \App\Enums\TripStatus::IN_PROGRESS)
                        <x-ui.button wire:click="startHike">Mulai hike mode</x-ui.button>
                    @else
                        <x-ui.button href="{{ route('trips.hike', $trip) }}">Buka hike mode</x-ui.button>
                    @endif
                    <x-ui.button variant="secondary" size="sm" wire:click="cancel">
                        Batalkan trip</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Selesaikan trip"
                subtitle="Tandai trip selesai untuk menyimpannya ke riwayat dan melaporkan kondisi jalur.">
                <form wire:submit="complete" class="space-y-4">
                    <x-form.select name="completion_state" label="Hasil pendakian" required
                        placeholder="Pilih hasil"
                        :options="collect($completionStates)->mapWithKeys(fn ($state) => [$state->value => $state->label()])->all()" />

                    <div>
                        <x-input-label for="personal_notes" value="Catatan pribadi (opsional)" />
                        <textarea id="personal_notes" wire:model="personal_notes" rows="3"
                            class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('personal_notes')" class="mt-2" />
                    </div>

                    <x-ui.button type="submit" target="complete">Tandai selesai</x-ui.button>
                </form>
            </x-ui.card>
        @endif
    </div>
</div>
