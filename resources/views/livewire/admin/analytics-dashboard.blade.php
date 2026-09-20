<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Analitik"
            description="Funnel perencanaan pendakian, dari profil sampai laporan kondisi." />

        <x-ui.admin-nav />

        <x-ui.card class="mb-6" title="North star metric"
            subtitle="Persentase pemilihan jalur yang berlanjut sampai pre-departure check.">
            <p class="text-3xl font-semibold text-primary">
                {{ $northStar !== null ? $northStar.'%' : 'Belum ada data' }}
            </p>
        </x-ui.card>

        <x-ui.card title="Funnel">
            <ol class="space-y-2">
                @forelse ($funnel as $event => $count)
                    @php $enum = \App\Enums\AnalyticsEvent::tryFrom($event); @endphp
                    <li class="flex items-center justify-between rounded-md border border-subtle px-3 py-2 text-sm">
                        <span class="text-secondary">{{ $enum?->label() ?? $event }}</span>
                        <span class="font-semibold text-primary">{{ $count }}</span>
                    </li>
                @empty
                    <li>
                        <p class="font-medium text-primary">Belum ada peristiwa tercatat</p>
                        <p class="mt-1 max-w-prose text-secondary">Corong dihitung dari peristiwa yang benar-benar terjadi. Kosongnya berarti belum ada yang memakai alur ini, bukan alurnya rusak.</p>
                    </li>
                @endforelse
            </ol>
        </x-ui.card>
    </div>
</div>
