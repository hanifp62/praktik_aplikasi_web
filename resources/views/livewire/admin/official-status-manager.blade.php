<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Status Resmi"
            description="Status dapat melekat pada gunung, jalur, atau segmen. Status gunung yang terbuka tidak otomatis berarti semua jalurnya terbuka." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        {{--
            Panel ini diletakkan sebelum formulir karena mendesak: status yang kedaluwarsa
            membuat jalurnya berjalan tanpa status resmi, dan §95 membuat kemundurannya
            senyap. Admin harus melihat apa yang perlu ditindak sebelum menambah yang baru.
        --}}
        @if ($tinjau['kedaluwarsa']->isNotEmpty() || $tinjau['segera']->isNotEmpty() || $tinjau['basi']->isNotEmpty())
            <x-ui.card class="mb-6" title="Perlu ditinjau">
                <div class="space-y-5">
                    @foreach ([
                        'kedaluwarsa' => ['Sudah kedaluwarsa', 'Jalur ini sekarang berjalan tanpa status resmi dan ditampilkan sebagai belum diketahui.'],
                        'segera' => ['Akan segera kedaluwarsa', 'Masih sempat diperpanjang sebelum statusnya hilang.'],
                        'basi' => ['Lama tidak diverifikasi', 'Catatannya masih berlaku, tetapi belum dipastikan ulang ke pengelola.'],
                    ] as $kunci => [$judul, $penjelasan])
                        @if ($tinjau[$kunci]->isNotEmpty())
                            <div>
                                <h3 class="text-sm font-semibold text-primary">
                                    {{ $judul }} ({{ $tinjau[$kunci]->count() }})
                                </h3>
                                <p class="mt-0.5 text-xs text-secondary">{{ $penjelasan }}</p>

                                <ul class="mt-2 space-y-1 text-sm">
                                    @foreach ($tinjau[$kunci] as $item)
                                        <li class="flex flex-wrap items-baseline gap-x-2 text-secondary">
                                            <span class="font-medium text-primary">
                                                {{ $item->statusable?->name ?? 'Objek terhapus' }}
                                            </span>
                                            <x-ui.status-badge :status="$item->status" />
                                            <span class="text-xs text-secondary">
                                                @if ($kunci === 'basi')
                                                    {{ $item->verified_at
                                                        ? 'terakhir diverifikasi '.$item->verified_at->translatedFormat('d M Y')
                                                        : 'belum pernah diverifikasi' }}
                                                @else
                                                    berlaku sampai {{ $item->expires_at->translatedFormat('d M Y') }}
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <x-ui.card class="mb-6" title="Catat status resmi">
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.select name="scope" label="Cakupan" required placeholder="Pilih cakupan" live
                        :options="collect($scopes)->mapWithKeys(fn ($scope) => [$scope->value => $scope->label()])->all()" />
                    <x-form.select name="statusable_id" label="Objek" required placeholder="Pilih objek"
                        :options="$targets" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.select name="status" label="Status" required placeholder="Pilih status"
                        :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
                    <x-form.select name="data_source_id" label="Sumber data terdaftar" placeholder="Belum ditentukan"
                        :options="$sources->mapWithKeys(fn ($source) => [$source->id => $source->source_name])->all()" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field name="source" label="Nama sumber (teks bebas)" />
                    <x-form.field name="source_url" type="url" label="URL sumber" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-form.field name="published_at" type="date" label="Tanggal publikasi sumber" />
                    <x-form.field name="effective_at" type="date" label="Berlaku mulai" />
                    <x-form.field name="expires_at" type="date" label="Berlaku sampai (opsional)" />
                </div>

                <x-form.field name="reason" label="Alasan" />

                <div>
                    <x-input-label for="notes" value="Catatan" />
                    <textarea id="notes" wire:model="notes" rows="2"
                        class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <x-ui.button type="submit" target="save">Simpan status</x-ui.button>
            </form>
        </x-ui.card>

        <div class="overflow-x-auto rounded-lg bg-white">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Riwayat status resmi</caption>
                <thead>
                    <tr class="border-b border-subtle text-left text-muted">
                        <th scope="col" class="p-4">Objek</th>
                        <th scope="col" class="p-4">Cakupan</th>
                        <th scope="col" class="p-4">Status</th>
                        <th scope="col" class="p-4">Sumber</th>
                        <th scope="col" class="p-4">Berlaku</th>
                        <th scope="col" class="p-4">Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="border-b border-subtle">
                            <td class="p-4 font-medium text-primary">{{ $record->statusable?->name ?? '-' }}</td>
                            <td class="p-4 text-secondary">{{ $record->scope->label() }}</td>
                            <td class="p-4"><x-ui.status-badge :status="$record->status" /></td>
                            <td class="p-4 text-secondary">{{ $record->source ?? '-' }}</td>
                            <td class="p-4 text-secondary">
                                {{ $record->effective_at?->translatedFormat('d M Y') ?? '-' }}
                                @if ($record->expires_at)
                                    &ndash; {{ $record->expires_at->translatedFormat('d M Y') }}
                                @endif
                            </td>
                            <td class="p-4 text-secondary">{{ $record->recordedBy?->name ?? 'Sistem' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4">
                                <p class="font-medium text-primary">Belum ada status resmi tercatat</p>
                                <p class="mt-1 max-w-prose text-secondary">Jalur tanpa status resmi bukan berarti jalur terbuka. Sistem menampilkannya sebagai belum diketahui, dan itu memang keadaan yang sebenarnya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $records->links() }}</div>
    </div>
</div>
