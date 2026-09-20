@props(['rows' => 3])

{{--
    Bentuk saat memuat.

    Bentuk yang sudah terlihat memberi tahu apa yang sedang ditunggu; pemutar lingkaran
    hanya memberi tahu bahwa sesuatu sedang terjadi. Perbedaannya paling terasa di
    jaringan lambat, dan jaringan lambat adalah keadaan normal bagi pengguna ini.

    Bentuknya disembunyikan dari pembaca layar dan keadaannya diumumkan sebagai teks:
    membacakan lima kotak kosong memperpanjang tanpa menyampaikan apa pun.

    Lebar barisnya tidak seragam. Blok yang lebarnya sama persis terbaca sebagai tabel
    yang belum terisi, bukan sebagai teks yang sedang datang.

    Denyutnya berhenti sendiri ketika pengguna meminta gerak dikurangi: aturan menyeluruh
    di app.css memangkas setiap animasi, jadi tidak ada yang perlu ditambahkan di sini.
--}}
<div {{ $attributes }}>
    <div aria-hidden="true" class="space-y-3">
        @for ($i = 0; $i < (int) $rows; $i++)
            <div data-skeleton-row class="h-4 animate-pulse rounded bg-surface-sunken"
                style="width: {{ [100, 85, 92][$i % 3] }}%"></div>
        @endfor
    </div>

    <p aria-live="polite" class="sr-only">Memuat isi halaman.</p>
</div>
