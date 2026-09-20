<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Rencana Pendakian yang Sesuai Kemampuan Anda</title>
        <meta name="description" content="Bantu pendaki menentukan jalur yang sesuai profilnya, apa yang perlu disiapkan, dan apakah sudah layak berangkat.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-800 antialiased">
        {{--
            Halaman ini sebelumnya adalah halaman bawaan Laravel, lengkap dengan tautan
            Laracasts dan gambar latar dari laravel.com. Ini juga halaman yang dituju
            setelah pengguna keluar, jadi bukan halaman yang jarang dilihat.

            Susunannya mengikuti satu pertanyaan per layar: apa ini, bagaimana cara
            kerjanya, dari mana datanya. Tiga langkah, bukan tujuh, supaya pengunjung
            tidak perlu menahan banyak hal di kepala sebelum memutuskan mendaftar.
        --}}
        <header class="border-b border-gray-200">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                <span class="font-semibold text-gray-900">Rencana Pendakian</span>

                @auth
                    <x-ui.button size="sm" href="{{ route('dashboard') }}">Buka dasbor</x-ui.button>
                @else
                    <x-ui.button variant="secondary" size="sm" href="{{ route('login') }}">Masuk</x-ui.button>
                @endauth
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16">
                <h1 class="max-w-2xl text-3xl font-semibold leading-tight text-gray-900 sm:text-4xl">
                    Cari tahu jalur mana yang sesuai kemampuan Anda, sebelum berangkat
                </h1>

                <p class="mt-4 max-w-2xl text-base text-gray-700 sm:text-lg">
                    Informasi pendakian tersebar di banyak tempat: karakteristik jalur, prakiraan cuaca,
                    status resmi jalur, dan daftar persiapan. Aplikasi ini menyatukannya, lalu
                    mencocokkannya dengan pengalaman dan rencana Anda sendiri.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <x-ui.button href="{{ route('dashboard') }}">Buka dasbor</x-ui.button>
                        <x-ui.button variant="secondary" href="{{ route('trails.index') }}">Telusuri jalur</x-ui.button>
                    @else
                        <x-ui.button href="{{ route('register') }}">Mulai, buat profil pendakian</x-ui.button>
                        <x-ui.button variant="secondary" href="{{ route('login') }}">Masuk</x-ui.button>
                    @endauth
                </div>

                <p class="mt-4 text-sm text-gray-600">
                    Membuat profil memakan waktu beberapa menit dan menentukan seluruh rekomendasi
                    berikutnya.
                </p>
            </section>

            <section class="border-t border-gray-200 bg-gray-50">
                <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                    <h2 class="text-xl font-semibold text-gray-900">Bagaimana cara kerjanya</h2>

                    <ol class="mt-6 grid gap-6 sm:grid-cols-3">
                        @foreach ([
                            ['Ceritakan pengalaman Anda', 'Berapa kali mendaki, medan apa saja yang pernah dilalui, seberapa jauh kemampuan navigasi Anda.'],
                            ['Lihat kecocokan jalurnya', 'Setiap jalur dinilai terhadap profil dan rencana Anda, lengkap dengan alasannya. Jalur yang tidak cocok tetap ditampilkan beserta sebabnya.'],
                            ['Siapkan dan periksa', 'Daftar persiapan menyesuaikan jalur dan jenis trip, lalu diperiksa ulang sebelum hari keberangkatan.'],
                        ] as $nomor => [$judul, $penjelasan])
                            <li>
                                <span class="inline-flex size-8 items-center justify-center rounded-full bg-brand-700 text-sm font-semibold text-white">
                                    {{ $nomor + 1 }}
                                </span>
                                <h3 class="mt-3 font-medium text-gray-900">{{ $judul }}</h3>
                                <p class="mt-1 text-sm text-gray-700">{{ $penjelasan }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                <h2 class="text-xl font-semibold text-gray-900">Dari mana datanya</h2>

                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    <div>
                        <h3 class="font-medium text-gray-900">Prakiraan cuaca BMKG</h3>
                        <p class="mt-1 text-sm text-gray-700">
                            Prakiraan diambil untuk wilayah administrasi di sekitar jalur, bukan untuk
                            titik puncaknya. Cuaca gunung dapat berbeda jauh dari area di bawahnya.
                        </p>
                    </div>

                    <div>
                        <h3 class="font-medium text-gray-900">Status resmi jalur</h3>
                        <p class="mt-1 text-sm text-gray-700">
                            Status dicatat beserta sumber dan tanggalnya. Selama belum ada keterangan
                            resmi, status ditampilkan sebagai belum diketahui, bukan ditebak sebagai
                            terbuka.
                        </p>
                    </div>
                </div>

                <p class="mt-8 rounded-lg border border-warn-300 bg-warn-50 p-4 text-sm text-warn-900">
                    Aplikasi ini membantu Anda memutuskan, tidak menggantikan keputusan Anda. Keterangan
                    dari pengelola jalur dan basecamp tetap yang paling menentukan di lapangan.
                </p>
            </section>
        </main>

        <footer class="border-t border-gray-200">
            <div class="mx-auto max-w-5xl px-4 py-6 text-sm text-gray-600 sm:px-6">
                Sumber prakiraan cuaca: BMKG.
            </div>
        </footer>
    </body>
</html>
