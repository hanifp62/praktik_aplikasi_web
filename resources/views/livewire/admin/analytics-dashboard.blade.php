<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Analitik"
            description="Funnel perencanaan pendakian, dari profil sampai laporan kondisi." />

        <x-ui.card class="mb-6" title="North star metric"
            subtitle="Persentase pemilihan jalur yang berlanjut sampai pre-departure check.">
            <p class="text-3xl font-semibold text-gray-900">
                {{ $northStar !== null ? $northStar.'%' : 'Belum ada data' }}
            </p>
        </x-ui.card>

        <x-ui.card title="Funnel">
            <ol class="space-y-2">
                @foreach ($funnel as $event => $count)
                    @php $enum = \App\Enums\AnalyticsEvent::tryFrom($event); @endphp
                    <li class="flex items-center justify-between rounded-md border border-gray-100 px-3 py-2 text-sm">
                        <span class="text-gray-700">{{ $enum?->label() ?? $event }}</span>
                        <span class="font-semibold text-gray-900">{{ $count }}</span>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>
    </div>
</div>
