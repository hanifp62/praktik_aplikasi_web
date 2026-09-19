<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Moderasi Laporan"
            description="Laporan komunitas hanya tampil untuk publik setelah disetujui. Laporan tidak pernah mengubah status resmi." />

        <div class="mb-4 max-w-xs">
            <x-input-label for="filter" value="Filter status" />
            <select id="filter" wire:model.live="filter"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if ($reports->isEmpty())
            <x-ui.card>
                <p class="text-sm text-gray-600">Tidak ada laporan pada filter ini.</p>
            </x-ui.card>
        @else
            <ul class="space-y-4">
                @foreach ($reports as $report)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900">{{ $report->trail->name }}</h2>
                                    <p class="text-sm text-gray-600">
                                        {{ $report->trail->mountain->name }}
                                        @if ($report->segment)
                                            &middot; segmen {{ $report->segment->name }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Dilaporkan oleh {{ $report->user->name }} &middot;
                                        pendakian {{ $report->hike_date->translatedFormat('d M Y') }} &middot;
                                        dikirim {{ $report->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    {{ $report->moderation_status->label() }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($report->tags() as $tag)
                                    <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs text-sky-900">{{ $tag->label() }}</span>
                                @endforeach
                            </div>

                            @if ($report->note)
                                <p class="mt-3 text-sm text-gray-700">{{ $report->note }}</p>
                            @endif

                            {{-- PRD §67: moderasi foto hanya mungkin bila fotonya terlihat. --}}
                            @if ($report->photo_path)
                                <figure class="mt-3">
                                    <img src="{{ route('reports.photo', $report) }}"
                                        alt="Foto kondisi jalur yang dilampirkan pada laporan tanggal {{ $report->hike_date->translatedFormat('d M Y') }}"
                                        loading="lazy"
                                        class="max-h-64 rounded-md border border-gray-200">
                                    <figcaption class="mt-1 text-xs text-gray-500">
                                        Foto dari pelapor. Periksa sebelum menyetujui.
                                    </figcaption>
                                </figure>
                            @endif

                            <div class="mt-4 space-y-2">
                                <label class="block text-sm text-gray-700" for="reason-{{ $report->id }}">
                                    Alasan tindakan (opsional)
                                </label>
                                <input id="reason-{{ $report->id }}" type="text"
                                    wire:model="reasons.{{ $report->id }}"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">

                                <div class="flex flex-wrap gap-2">
                                    @foreach ($actions as $action)
                                        <button wire:click="act({{ $report->id }}, '{{ $action->value }}')"
                                            class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                            {{ $action->label() }}
                                        </button>
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
