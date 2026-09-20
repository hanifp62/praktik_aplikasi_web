<div class="py-8">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Audit Log"
            description="Catatan tindakan kritis: perubahan status resmi, data jalur dan gunung, moderasi, sumber data, dan peran pengguna." />

        <x-ui.admin-nav />

        <div class="mb-4 max-w-sm">
            <x-input-label for="action" value="Filter aksi" />
            <x-text-input id="action" wire:model.live.debounce.400ms="action" class="mt-1 block w-full"
                placeholder="trail.published" />
        </div>

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Daftar audit log</caption>
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500">
                        <th scope="col" class="p-4">Waktu</th>
                        <th scope="col" class="p-4">Aktor</th>
                        <th scope="col" class="p-4">Aksi</th>
                        <th scope="col" class="p-4">Entitas</th>
                        <th scope="col" class="p-4">Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr class="border-b border-gray-100 align-top">
                            <td class="p-4 text-gray-700">{{ \App\Support\Timezone::display($log->created_at, \App\Support\Timezone::DEFAULT) }}</td>
                            <td class="p-4 text-gray-700">{{ $log->actor?->name ?? 'Sistem' }}</td>
                            <td class="p-4 font-medium text-gray-900">{{ $log->action }}</td>
                            <td class="p-4 text-gray-700">
                                {{ class_basename($log->entity_type ?? '-') }}
                                @if ($log->entity_id)
                                    #{{ $log->entity_id }}
                                @endif
                            </td>
                            <td class="p-4 text-xs text-gray-600">
                                @if ($log->after)
                                    <details>
                                        <summary class="cursor-pointer">Lihat detail</summary>
                                        <pre class="mt-2 max-w-md overflow-x-auto rounded bg-gray-50 p-2">{{ json_encode(['before' => $log->before, 'after' => $log->after], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $logs->links() }}</div>
    </div>
</div>
