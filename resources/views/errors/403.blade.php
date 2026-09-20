@extends('errors.layout')

@section('kode', 'Galat 403')
@section('judul', 'Anda tidak punya akses ke halaman ini')

@section('penjelasan')
    <p>Halaman ini milik pengguna lain, atau memerlukan peran yang tidak Anda miliki. Kalau menurut Anda ini keliru, hubungi pengelola aplikasi.</p>
@endsection

@section('tindakan')
    <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">Kembali ke halaman utama</a>
@endsection
