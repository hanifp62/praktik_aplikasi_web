<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Tidak ada koneksi</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-800 antialiased">
        {{--
            Halaman ini muncul persis ketika pengguna paling mungkin berada di lapangan.
            Karena itu ia tidak menyebut satu pun kondisi jalur, cuaca, atau status resmi:
            apa pun yang ditampilkan dari ingatan lama akan terbaca sebagai keadaan
            sekarang, dan itu yang dilarang §94 dan §95.
        --}}
        <main class="mx-auto flex min-h-screen max-w-xl flex-col justify-center px-4 py-12 sm:px-6">
            <p class="text-sm font-medium text-gray-500">Luring</p>

            <h1 class="mt-2 text-2xl font-semibold text-gray-900 sm:text-3xl">
                Tidak ada koneksi saat ini
            </h1>

            <div class="mt-4 space-y-3 text-base text-gray-700">
                <p>
                    Halaman ini tidak dapat dimuat karena perangkat Anda sedang tanpa jaringan.
                </p>
                <p class="rounded-md border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                    Aplikasi sengaja tidak menampilkan data lama saat luring. Status jalur dan
                    prakiraan cuaca berubah, dan salinan kemarin yang terlihat seperti keadaan
                    sekarang lebih berbahaya daripada tidak ada tampilan sama sekali.
                </p>
                <p>
                    Kalau Anda sedang bersiap berangkat, tanyakan keadaan jalur langsung kepada
                    pengelola atau basecamp.
                </p>
            </div>

            <div class="mt-8">
                <button type="button" onclick="location.reload()"
                    class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Coba muat ulang
                </button>
            </div>
        </main>
    </body>
</html>
