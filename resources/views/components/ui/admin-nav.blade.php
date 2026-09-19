{{--
    Navigasi antar-halaman admin.

    Sebelumnya hanya `admin.trails` yang tertaut dari navigasi utama, sehingga halaman
    admin lain hanya dapat dicapai dengan mengetik URL-nya. Kurator data bekerja
    berpindah-pindah antara gunung, jalur, checkpoint, sumber, dan status — memaksanya
    menghafal alamat adalah hambatan yang tidak perlu.
--}}
@php
    $links = [
        'admin.mountains' => 'Gunung',
        'admin.trails' => 'Jalur',
        'admin.sources' => 'Sumber Data',
        'admin.statuses' => 'Status Resmi',
        'admin.permits' => 'Perizinan',
        'admin.audit' => 'Jejak Audit',
        'admin.analytics' => 'Analitik',
    ];
@endphp

<nav aria-label="Navigasi admin" class="mb-6 border-b border-gray-200">
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
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
