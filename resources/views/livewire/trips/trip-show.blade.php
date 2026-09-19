<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$trip->name"
            :description="$trip->trail->name.' - '.$trip->trail->mountain->name">
            <p class="mt-1 text-sm text-gray-600">
                {{ $trip->planned_date->translatedFormat('d M Y') }}
                @if ($trip->start_time)
                    &middot; mulai {{ \Illuminate\Support\Carbon::parse($trip->start_time)->format('H:i') }}
                @endif
                &middot; {{ $trip->trip_type->label() }}
            </p>
            <span class="mt-2 inline-block rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                {{ $trip->status->label() }}
            </span>
        </x-ui.page-header>

        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.card title="Persiapan">
                <p class="text-2xl font-semibold text-gray-900">{{ $trip->preparationCompletionPercent() }}%</p>
                <a href="{{ route('trips.preparation', $trip) }}" wire:navigate
                    class="mt-2 inline-block text-sm text-emerald-700 underline">Buka daftar persiapan</a>
            </x-ui.card>

            <x-ui.card title="Kesiapan">
                <p class="text-sm text-gray-900">
                    {{ $trip->latestReadinessCheck?->computed_state->label() ?? 'Belum dinilai' }}
                </p>
                <a href="{{ route('trips.readiness', $trip) }}" wire:navigate
                    class="mt-2 inline-block text-sm text-emerald-700 underline">Cek kesiapan</a>
            </x-ui.card>
        </div>

        @if ($trip->notes)
            <x-ui.card title="Catatan">
                <p class="text-sm text-gray-700">{{ $trip->notes }}</p>
            </x-ui.card>
        @endif

        @if ($trip->status->isActive())
            <x-ui.card title="Tindakan">
                <div class="flex flex-wrap gap-3">
                    @if ($trip->status !== \App\Enums\TripStatus::IN_PROGRESS)
                        <button wire:click="startHike"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            Mulai hike mode
                        </button>
                    @else
                        <a href="{{ route('trips.hike', $trip) }}" wire:navigate
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Buka hike mode
                        </a>
                    @endif
                    <button wire:click="cancel"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        Batalkan trip
                    </button>
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        <x-input-error :messages="$errors->get('personal_notes')" class="mt-2" />
                    </div>

                    <button type="submit"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        Tandai selesai
                    </button>
                </form>
            </x-ui.card>
        @endif
    </div>
</div>
