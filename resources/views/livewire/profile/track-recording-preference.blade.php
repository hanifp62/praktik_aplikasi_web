<section>
    <header>
        <h2 class="text-lg font-medium text-primary">Perekaman jejak pendakian</h2>

        <p class="mt-1 text-sm text-secondary">
            Aplikasi dapat merekam jejak perjalanan Anda selama mendaki, lalu
            menggambarnya di halaman hasil pendakian.
        </p>
    </header>

    {{--
        Persetujuan tanpa keterangan bukan persetujuan. Ketiga kalimat ini menyebut apa
        yang direkam, di mana ia berada selama pendakian, kapan ia berpindah ke server,
        dan bahwa ia dapat ditarik kembali. Semuanya dibaca sebelum sakelarnya disentuh,
        bukan sesudah.
    --}}
    <ul class="mt-4 space-y-2 text-sm text-secondary">
        <li class="flex gap-2">
            <span aria-hidden="true">&middot;</span>
            <span>Selama mendaki, titik-titiknya disimpan <strong>hanya di perangkat Anda</strong>. Tidak ada yang dikirim saat Anda masih di jalur.</span>
        </li>
        <li class="flex gap-2">
            <span aria-hidden="true">&middot;</span>
            <span>Jejaknya dikirim <strong>setelah Anda turun</strong> dan mendapat sinyal kembali.</span>
        </li>
        <li class="flex gap-2">
            <span aria-hidden="true">&middot;</span>
            <span>Jejak setiap pendakian <strong>dapat dihapus</strong> kapan saja dari halaman hasil pendakiannya, tanpa menghapus pendakiannya.</span>
        </li>
        <li class="flex gap-2">
            <span aria-hidden="true">&middot;</span>
            <span>Merekam posisi terus-menerus memakai baterai lebih cepat dari biasanya.</span>
        </li>
    </ul>

    <form wire:submit="simpan" class="mt-5 space-y-4">
        <label class="flex items-start gap-3">
            <input type="checkbox" wire:model="record_track"
                class="mt-0.5 h-5 w-5 rounded border-control text-brand-700 focus:ring-brand-600">
            <span class="text-sm text-primary">Rekam jejak pendakian saya</span>
        </label>

        <div class="flex items-center gap-4">
            <x-ui.button type="submit" target="simpan">Simpan</x-ui.button>

            @if (session('status'))
                <p class="text-sm text-secondary">{{ session('status') }}</p>
            @endif
        </div>
    </form>
</section>
