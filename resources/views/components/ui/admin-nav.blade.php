{{--
    Navigasi antar-halaman admin.

    Sebelumnya hanya `admin.trails` yang tertaut dari navigasi utama, sehingga halaman
    admin lain hanya dapat dicapai dengan mengetik URL-nya. Kurator data bekerja
    berpindah-pindah antara gunung, jalur, checkpoint, sumber, dan status, memaksanya
    menghafal alamat adalah hambatan yang tidak perlu.
--}}
@php
    $links = [
        'admin.mountains' => 'Gunung',
        'admin.trails' => 'Jalur',
        'admin.sources' => 'Sumber Data',
        'admin.statuses' => 'Status Resmi',
        'admin.permits' => 'Perizinan',
        'admin.authorities' => 'Badan Resmi',
        'admin.credentials' => 'Kredensial Ahli',
        'admin.audit' => 'Jejak Audit',
        'admin.analytics' => 'Analitik',
        'admin.usability' => 'Studi Kegunaan',
        'admin.health' => 'Kesehatan Sistem',
    ];

    // Satu query agregat. Angkanya sengaja dibawa ke setiap halaman admin: status yang
    // kedaluwarsa membuat jalur kehilangan status resmi tanpa suara (§95), jadi admin
    // harus melihatnya tanpa perlu membuka halaman status lebih dulu.
    $perluDitinjau = app(App\Services\DataFreshnessService::class)->reviewCount();
@endphp

<nav aria-label="Navigasi admin" class="mb-6 border-b border-subtle">
    <ul class="-mb-px flex flex-wrap gap-1">
        @foreach ($links as $route => $label)
            @php $active = request()->routeIs($route); @endphp
            <li>
                <a href="{{ route($route) }}" wire:navigate
                    @if ($active) aria-current="page" @endif
                    class="inline-flex min-h-11 items-center border-b-2 px-3 text-sm font-medium transition
                        focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600
                        {{ $active
                            ? 'border-brand-600 text-brand-900'
                            : 'border-transparent text-muted hover:border-control hover:text-secondary' }}">
                    {{ $label }}

                    @if ($route === 'admin.statuses' && $perluDitinjau > 0)
                        <span class="ml-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-warn-100 px-1.5 py-0.5 text-xs font-semibold text-warn-900">
                            {{ $perluDitinjau }}
                            <span class="sr-only">status perlu ditinjau</span>
                        </span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
