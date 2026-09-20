<div class="py-8">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.admin-nav />

        <x-ui.page-header title="Studi Kegunaan"
            description="Bukti dari manusia adalah satu-satunya yang tidak dapat dihasilkan sistem sendiri. Halaman ini mengumpulkannya dan menghitungnya." />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        {{--
            Ringkasan diletakkan di atas formulir supaya yang pertama terbaca adalah
            posisi studi saat ini, bukan kotak kosong. Fasilitator yang tahu tinggal
            berapa peserta lagi mengambil keputusan berbeda dari yang tidak tahu.
        --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card title="Peserta uji tugas">
                <p class="text-2xl font-semibold text-primary">{{ $ringkasan['peserta_tugas'] }}</p>
                <p class="mt-1 text-sm text-secondary">
                    Perkiraan masalah kegunaan yang tertemukan:
                    <span class="font-medium text-primary">{{ round($ringkasan['cakupan_masalah'] * 100) }}%</span>
                </p>
                @if ($ringkasan['peserta_kurang'] > 0)
                    <p class="mt-2 text-sm text-warn-900">
                        {{ $ringkasan['peserta_kurang'] }} peserta lagi untuk mencapai sekitar 84%.
                    </p>
                @else
                    <p class="mt-2 text-sm text-secondary">
                        Ambang lima peserta tercapai. Peserta berikutnya tetap menambah, hanya makin landai.
                    </p>
                @endif
            </x-ui.card>

            <x-ui.card title="Skor SUS">
                @if ($ringkasan['sus_rata'] === null)
                    <p class="text-sm text-secondary">Belum ada responden yang melengkapi kuesioner.</p>
                @else
                    <p class="text-2xl font-semibold text-primary">
                        {{ number_format($ringkasan['sus_rata'], 1) }}
                        <span class="text-base font-medium text-secondary">({{ $ringkasan['sus_grade'] }})</span>
                    </p>
                    {{-- Ditulis tanpa tanda persen karena SUS memang bukan persentase. --}}
                    <p class="mt-1 text-xs text-muted">Skala 0-100, bukan persentase. Rata-rata seluruh produk sekitar 68.</p>
                @endif

                @if (! $ringkasan['sus_dapat_diandalkan'])
                    <p class="mt-2 text-sm text-warn-900">
                        Dari {{ $ringkasan['responden_sus'] }} responden. Butuh
                        {{ $ringkasan['responden_sus_kurang'] }} lagi sebelum angka ini layak dikutip.
                    </p>
                @endif
            </x-ui.card>

            <x-ui.card title="Pembaca layar">
                <p class="text-2xl font-semibold text-primary">{{ $ringkasan['sesi_pembaca_layar'] }}</p>
                <p class="mt-1 text-sm text-secondary">
                    Penelusuran yang sudah tercatat. Pemeriksaan otomatis tidak dapat menggantikannya.
                </p>
            </x-ui.card>
        </div>

        <x-ui.card title="Hasil per tugas"
            subtitle="Berhasil dengan kesulitan dihitung setengah, karena tugas yang selesai setelah tersesat bukan tugas yang berhasil.">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <caption class="sr-only">Tingkat keberhasilan tiap tugas protokol</caption>
                    <thead>
                        <tr class="border-b border-subtle text-left text-secondary">
                            <th scope="col" class="py-2 pr-3 font-medium">Tugas</th>
                            <th scope="col" class="py-2 pr-3 font-medium">Diamati</th>
                            <th scope="col" class="py-2 pr-3 font-medium">Gagal</th>
                            <th scope="col" class="py-2 font-medium">Tingkat berhasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ringkasan['tugas'] as $kode => $tugas)
                            <tr class="border-b border-subtle">
                                <td class="py-2 pr-3">
                                    <span class="font-medium text-primary">{{ $kode }}</span>
                                    <span class="text-secondary">{{ $tugas['judul'] }}</span>
                                    @if ($tugas['inti'])
                                        <span class="ml-1 rounded bg-brand-100 px-1.5 py-0.5 text-xs font-medium text-brand-900">inti</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-secondary">{{ $tugas['diamati'] }}</td>
                                <td class="py-2 pr-3 text-secondary">{{ $tugas['gagal'] }}</td>
                                <td class="py-2 text-primary">
                                    {{ $tugas['tingkat_berhasil'] === null ? 'belum diamati' : round($tugas['tingkat_berhasil'] * 100).'%' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card title="Catat sesi baru">
            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-form.field name="participant_code" label="Kode peserta" required
                        hint="Kode, bukan nama." />
                    <x-form.select name="kind" label="Jenis sesi" required
                        :options="collect($jenis)->mapWithKeys(fn ($k) => [$k->value => $k->label()])->all()" />
                    <x-form.select name="experience_level" label="Tingkat pengalaman (opsional)"
                        placeholder="Tidak dicatat"
                        :options="collect($tingkat)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" />
                </div>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-medium text-primary">Hasil tugas</legend>

                    {{-- daftar tetap: pilihan yang ditetapkan di kode, tidak pernah kosong. --}}
                    @foreach ($daftarTugas as $kode => $tugas)
                        <div class="rounded-control border border-subtle p-3">
                            <p class="text-sm font-medium text-primary">{{ $kode }}. {{ $tugas['judul'] }}</p>
                            {{-- Kriteria berhasilnya ikut tertulis di sebelah pilihannya supaya
                                 fasilitator tidak menafsirkan ulang dari ingatan. --}}
                            <p class="mt-1 text-xs text-secondary">Berhasil bila: {{ $tugas['berhasil'] }}</p>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label for="outcome-{{ $kode }}" class="block text-xs font-medium text-secondary">Hasil</label>
                                    <select id="outcome-{{ $kode }}" wire:model="tasks.{{ $kode }}.outcome"
                                        class="mt-1 block w-full rounded-control border-control text-sm focus:border-brand-600 focus:ring-brand-600">
                                        <option value="">Tidak dijalankan</option>
                                        @foreach ($hasilTugas as $hasil)
                                            <option value="{{ $hasil->value }}">{{ $hasil->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="seconds-{{ $kode }}" class="block text-xs font-medium text-secondary">Waktu (detik)</label>
                                    <input id="seconds-{{ $kode }}" type="number" min="0"
                                        wire:model="tasks.{{ $kode }}.seconds"
                                        class="mt-1 block w-full rounded-control border-control text-sm focus:border-brand-600 focus:ring-brand-600">
                                </div>
                                <div>
                                    <label for="note-{{ $kode }}" class="block text-xs font-medium text-secondary">Kutipan atau masalah</label>
                                    <input id="note-{{ $kode }}" type="text" wire:model="tasks.{{ $kode }}.note"
                                        class="mt-1 block w-full rounded-control border-control text-sm focus:border-brand-600 focus:ring-brand-600">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </fieldset>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium text-primary">SUS</legend>
                    <p class="text-xs text-secondary">
                        Diisi setelah semua tugas selesai. 1 sangat tidak setuju, 5 sangat setuju.
                        Lembar yang tidak lengkap tetap tersimpan, hanya tidak menyumbang skor.
                    </p>

                    @foreach ($pernyataanSus as $nomor => $pernyataan)
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-subtle pb-2">
                            <label for="sus-{{ $nomor }}" class="text-sm text-primary">{{ $nomor }}. {{ $pernyataan }}</label>
                            <select id="sus-{{ $nomor }}" wire:model="sus.{{ $nomor }}"
                                class="rounded-control border-control text-sm focus:border-brand-600 focus:ring-brand-600">
                                <option value="">-</option>
                                @foreach (range(1, 5) as $nilai)
                                    <option value="{{ $nilai }}">{{ $nilai }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </fieldset>

                <div>
                    <x-input-label for="notes" value="Catatan sesi (opsional)" />
                    <textarea id="notes" wire:model="notes" rows="3"
                        class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <x-ui.button type="submit" target="save">Simpan sesi</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Sesi tercatat">
            @forelse ($sesi as $baris)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-subtle py-2 text-sm">
                    <div>
                        <span class="font-medium text-primary">{{ $baris->participant_code }}</span>
                        <span class="text-secondary">&middot; {{ $baris->kind->label() }}</span>
                        @if ($baris->facilitator)
                            <span class="text-secondary">&middot; {{ $baris->facilitator->name }}</span>
                        @endif
                    </div>
                    <div class="text-secondary">
                        {{ $baris->sus_score === null ? 'tanpa SUS' : 'SUS '.number_format($baris->sus_score, 1) }}
                        &middot; {{ \App\Support\Timezone::display($baris->conducted_at, \App\Support\Timezone::DEFAULT) }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-secondary">Belum ada sesi tercatat.</p>
            @endforelse

            <div class="mt-4">{{ $sesi->links() }}</div>
        </x-ui.card>
    </div>
</div>
