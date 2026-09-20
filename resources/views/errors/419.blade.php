@extends('errors.layout')

@section('kode', 'Galat 419')
@section('judul', 'Sesi Anda sudah berakhir')

@section('penjelasan')
    <p>Ini biasa terjadi ketika halaman dibiarkan terbuka lama, misalnya dibuka di rumah lalu dilanjutkan di basecamp. Bukan kesalahan Anda, dan tidak ada data yang hilang selain isian yang belum sempat tersimpan.</p>
    <p>Masuk kembali, lalu ulangi langkah terakhir Anda.</p>
@endsection

@section('tindakan')
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">Masuk kembali</a>
@endsection
