<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Moderasi Laporan"
            description="Laporan komunitas hanya tampil untuk publik setelah disetujui. Laporan tidak pernah mengubah status resmi." />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="mb-4 max-w-xs">
            <x-input-label for="filter" value="Filter status" />
            <select id="filter" wire:model.live="filter"
                class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600">
                <option value="">Semua</option>
                {{-- daftar tetap: pilihan yang ditetapkan di kode, tidak pernah kosong. --}}
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if ($reports->isEmpty())
            <x-ui.card>
                <p class="text-sm text-secondary">Tidak ada laporan pada filter ini.</p>
            </x-ui.card>
        @else
            <ul class="space-y-4">
                @foreach ($reports as $report)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-primary">{{ $report->trail->name }}</h2>
                                    <p class="text-sm text-secondary">
                                        {{ $report->trail->mountain->name }}
                                        @if ($report->segment)
                                            &middot; segmen {{ $report->segment->name }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-muted">
                                        Dilaporkan oleh {{ $report->authorLabel() }} &middot;
                                        pendakian {{ $report->hike_date->translatedFormat('d M Y') }} &middot;
                                        dikirim {{ $report->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="rounded-control bg-surface-sunken px-2.5 py-1 text-xs font-medium text-secondary">
                                    {{ $report->moderation_status->label() }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($report->tags() as $tag)
                                    <span class="rounded-full bg-community-50 px-2.5 py-0.5 text-xs text-community-900">{{ $tag->label() }}</span>
                                @endforeach
                            </div>

                            @if ($report->note)
                                <p class="mt-3 text-sm text-secondary">{{ $report->note }}</p>
                            @endif

                            {{-- PRD §67: moderasi foto hanya mungkin bila fotonya terlihat. --}}
                            @if ($report->photo_path)
                                <figure class="mt-3">
                                    <img src="{{ route('reports.photo', $report) }}"
                                        alt="Foto kondisi jalur yang dilampirkan pada laporan tanggal {{ $report->hike_date->translatedFormat('d M Y') }}"
                                        loading="lazy"
                                        class="max-h-64 rounded-control border border-subtle">
                                    <figcaption class="mt-1 text-xs text-muted">
                                        Foto dari pelapor. Periksa sebelum menyetujui.
                                    </figcaption>
                                </figure>
                            @endif

                            <div class="mt-4 space-y-2">
                                <label class="block text-sm text-secondary" for="reason-{{ $report->id }}">
                                    Alasan tindakan (opsional)
                                </label>
                                <input id="reason-{{ $report->id }}" type="text"
                                    wire:model="reasons.{{ $report->id }}"
                                    class="block w-full rounded-control border-control text-sm focus:border-brand-600 focus:ring-brand-600">

                                <p class="text-xs text-secondary">
                                    Alasan pada penolakan dan penghapusan ditampilkan kepada pelapor di riwayatnya.
                                </p>

                                {{--
                                    Menolak atau menghapus membuang intel lapangan yang tidak dapat
                                    diambil ulang: pendakian itu sudah lewat. Menyetujui dan menandai
                                    tidak dihalangi, karena keduanya dapat diubah lagi.
                                --}}
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($actions as $action)
                                        <x-ui.button variant="secondary" size="sm"
                                            wire:click="act({{ $report->id }}, '{{ $action->value }}')"
                                            :confirm="in_array($action->value, ['REJECT', 'REMOVE'], true)
                                                ? $action->label().' laporan ini? Pendakian yang dilaporkan sudah lewat, jadi intelnya tidak dapat diambil ulang.'
                                                : null">
                                            {{ $action->label() }}
                                        </x-ui.button>
                                    @endforeach
                                </div>
                            </div>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $reports->links() }}</div>
        @endif
    </div>
</div>
