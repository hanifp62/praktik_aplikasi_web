<!DOCTYPE html>
<html lang="id" {{ \App\Support\ThemePreference::htmlAttribute(request()) }}>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php($shell = \App\Support\AsetLuring::entriShell())
        {{--
            "Buka paket luring" di pesan jaringan membuka halaman ini dengan ?dibuka=paket (ruling
            W6-9k bagian b): pendaki sendiri yang membukanya, jadi judul dan kalimatnya menyebut apa
            yang ditampilkan. Hanya selama shell dimuat, karena tanpa shell halaman ini tidak
            menampilkan paket apa pun. Tanpa parameter, termasuk salinan /offline yang disimpan
            service worker untuk navigasi yang gagal, kalimat W6-9j tetap.
        --}}
        @php($dibukaPaket = $shell !== null && request()->query('dibuka') === 'paket')
        <title>{{ $dibukaPaket ? 'Paket luring di perangkat ini' : 'Halaman ini belum dapat dimuat' }}</title>
        <x-brand.head :fonts="false" />

        {{--
            Shell luring (spec v3 bagian 10.4; ruling W6-R9). Halaman ini disimpan service worker
            dan dibuka setiap kali dokumen gagal dimuat, jadi isinya sama untuk semua orang: tidak
            ada data pengguna, status, atau cuaca dari server di sini. Selama paket lapangan
            belum dibuka (hiking.field_pack.enabled), halaman ini hanya teks; sesudahnya entri
            shell membaca paket yang diunduh pendaki dari IndexedDB perangkat ini.

            Penanda memount-shell-luring menyebut path entri shell (ruling W6-9b): Tugas 10 hanya
            menyebut paket "Siap luring" bila /offline yang tersimpan memuat penanda ini dan skrip
            yang dirujuknya juga tersimpan. Tanpa entri shell di manifest build, halaman ini jatuh
            ke app.css saja tanpa penanda, bukan 500 (PT9-16 bagian d).
        --}}
        @if ($shell !== null)
            <meta name="memount-shell-luring" content="{{ $shell }}">
            @vite(['resources/css/app.css', 'resources/js/luring/shell.tsx'])
        @else
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="min-h-dvh bg-canvas font-sans text-primary antialiased">
        {{--
            Halaman ini muncul persis ketika pengguna paling mungkin berada di lapangan.
            Karena itu ia tidak menyebut satu pun kondisi jalur, cuaca, atau status resmi:
            apa pun yang ditampilkan dari ingatan lama akan terbaca sebagai keadaan
            sekarang, dan itu yang dilarang §94 dan §95. Paket lapangan di perangkat ini
            ditampilkan shell dengan umurnya, sebagai terakhir diketahui.

            Service worker membuka halaman ini setiap kali dokumen gagal: tanpa sinyal, server
            tidak terjangkau, atau tidak menjawab dalam 8 detik selagi sinyal lemah (ruling W6-9f
            dan W6-9j). Kalimatnya karena itu menyebut ketiga kemungkinan sebab, bukan satu.
        --}}
        <main id="luring-pesan" class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-12 sm:px-6">
            <p class="text-sm font-medium text-muted">Luring</p>

            <h1 class="mt-2 text-2xl font-semibold text-primary sm:text-3xl">
                {{ $dibukaPaket ? 'Paket luring di perangkat ini' : 'Halaman ini belum dapat dimuat' }}
            </h1>

            <div class="mt-4 space-y-3 text-base text-secondary">
                @if ($dibukaPaket)
                    <p>
                        Halaman ini menampilkan paket yang tersimpan di perangkat ini, dibaca dari perangkat
                        tanpa meminta data baru ke {{ config('app.name') }}.
                    </p>
                @else
                    <p>
                        Perangkat ini tidak mendapat jawaban dari {{ config('app.name') }}. Sinyal mungkin hilang
                        atau terlalu lemah, atau server sedang tidak dapat dijangkau.
                    </p>
                @endif
                <p class="rounded-control border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                    Aplikasi sengaja tidak menampilkan salinan lama halaman saat luring. Status jalur
                    dan prakiraan cuaca berubah, dan salinan kemarin yang terlihat seperti keadaan
                    sekarang lebih berbahaya daripada tidak ada tampilan sama sekali.
                </p>
                <p>
                    Kalau Anda sedang bersiap berangkat, tanyakan keadaan jalur langsung kepada
                    pengelola atau basecamp.
                </p>
            </div>

            {{--
                Dua wilayah umpan balik shell yang selalu terpasang (PT9-2, PT9-3; konvensi DESIGN.md
                "Umpan balik aksi di wilayah yang selalu terpasang"): kabar keadaan paket di
                role="status", kegagalan membuka mode lapangan di role="alert" yang menerima fokus.
                Bingkainya hanya tampil bila ada isi. <p>, bukan <div> (DesignSystemTest).
            --}}
            <p id="luring-kabar" role="status" aria-atomic="true" class="mt-6 text-base text-primary empty:mt-0"></p>
            <p id="luring-galat" role="alert" tabindex="-1" class="mt-4 rounded-control border border-danger-300 bg-danger-50 p-3 text-sm text-danger-900 empty:mt-0 empty:border-0 empty:p-0 focus:outline-hidden focus:ring-2 focus:ring-brand-600 focus:ring-offset-2"></p>
            <div id="paket-luring"></div>

            <div class="mt-8">
                <button type="button" onclick="location.reload()"
                    class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-on-brand transition hover:bg-brand-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Coba muat ulang
                </button>
            </div>
        </main>
        <div id="app"></div>
    </body>
</html>
