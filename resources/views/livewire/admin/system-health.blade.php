<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.admin-nav />

        <x-ui.page-header title="Kesehatan Sistem"
            description="Tugas terjadwal yang berhenti berjalan tidak menghasilkan galat. Data lamanya tetap di tempatnya dan setiap halaman tetap tampak normal, jadi satu-satunya tanda adalah sesuatu yang tidak terjadi." />

        @if ($perluDiurus)
            <x-ui.alert variant="warning">
                Ada tugas terjadwal yang tidak berjalan sebagaimana mestinya. Selama itu berlangsung,
                prakiraan cuaca berhenti diperbarui dan status resmi yang kedaluwarsa berhenti diperiksa.
            </x-ui.alert>
        @endif

        <div class="space-y-4">
            @foreach ($tugas as $baris)
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-primary">{{ $baris['judul'] }}</h2>
                            <p class="mt-1 text-sm text-secondary">{{ $baris['jadwal'] }}</p>
                            <p class="mt-1 font-mono text-xs text-muted">{{ $baris['kunci'] }}</p>
                        </div>

                        <span @class([
                            'rounded-md px-2.5 py-1 text-xs font-medium',
                            'bg-brand-100 text-brand-900' => $baris['keadaan'] === \App\Services\SchedulerHealthService::SEHAT,
                            'bg-warn-100 text-warn-900' => $baris['keadaan'] === \App\Services\SchedulerHealthService::TERLAMBAT,
                            'bg-danger-100 text-danger-900' => in_array($baris['keadaan'], [
                                \App\Services\SchedulerHealthService::GAGAL,
                                \App\Services\SchedulerHealthService::BELUM_PERNAH,
                            ], true),
                        ])>{{ $kesehatan->label($baris['keadaan']) }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted">Terakhir berjalan</dt>
                            <dd class="font-medium text-primary">
                                {{ $baris['terakhir_jalan']
                                    ? \App\Support\Timezone::display($baris['terakhir_jalan'], \App\Support\Timezone::DEFAULT)
                                    : 'belum pernah' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted">Terakhir berhasil</dt>
                            <dd class="font-medium text-primary">
                                {{ $baris['terakhir_berhasil']
                                    ? \App\Support\Timezone::display($baris['terakhir_berhasil'], \App\Support\Timezone::DEFAULT)
                                    : 'belum pernah' }}
                            </dd>
                        </div>
                    </dl>

                    @if ($baris['ringkasan'])
                        <p class="mt-3 rounded-md bg-danger-50 px-3 py-2 text-sm text-danger-900">{{ $baris['ringkasan'] }}</p>
                    @endif

                    @if ($baris['keadaan'] === \App\Services\SchedulerHealthService::BELUM_PERNAH)
                        {{-- Penyebab paling sering, dan perbaikannya satu baris. Menyebutnya di
                             sini menghemat penelusuran yang tidak perlu. --}}
                        <p class="mt-3 text-sm text-secondary">
                            Kalau aplikasi baru dipasang, pastikan scheduler dijalankan:
                            <code class="rounded bg-surface-sunken px-1.5 py-0.5 text-xs">php artisan schedule:work</code>
                            saat pengembangan, atau satu entri cron per menit yang memanggil
                            <code class="rounded bg-surface-sunken px-1.5 py-0.5 text-xs">schedule:run</code> di server.
                        </p>
                    @endif

                    <p class="mt-3 text-xs text-muted">
                        Ditandai terlambat bila tidak ada keberhasilan selama {{ $baris['toleransi_jam'] }} jam.
                        Ambangnya sengaja lebih longgar dari jadwalnya, agar penandanya tidak menyala
                        tanpa ada yang rusak.
                    </p>
                </x-ui.card>
            @endforeach
        </div>

        <x-ui.card title="Dua puluh jalan terakhir">
            @forelse ($riwayat as $run)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-subtle py-2 text-sm">
                    <span class="font-mono text-xs text-secondary">{{ $run->task }}</span>
                    <span class="flex items-center gap-3">
                        <span @class([
                            'font-medium',
                            'text-brand-900' => $run->isSuccess(),
                            'text-danger-900' => ! $run->isSuccess(),
                        ])>{{ $run->isSuccess() ? 'berhasil' : 'gagal' }}</span>
                        @if ($run->runtime_ms !== null)
                            <span class="text-secondary">{{ number_format($run->runtime_ms / 1000, 2) }} dtk</span>
                        @endif
                        <span class="text-secondary">
                            {{ \App\Support\Timezone::display($run->ran_at, \App\Support\Timezone::DEFAULT) }}
                        </span>
                    </span>
                </div>
            @empty
                <p class="text-sm text-secondary">Belum ada satu pun tugas terjadwal yang tercatat berjalan.</p>
            @endforelse
        </x-ui.card>
    </div>
</div>
