@extends('errors.layout')

@section('kode', 'Galat 404')
@section('judul', 'Halaman ini tidak ditemukan')

@section('penjelasan')
    <p>Alamatnya mungkin salah ketik, atau halamannya sudah dipindahkan. Jalur yang diarsipkan juga tidak lagi dapat dibuka.</p>
@endsection

@section('tindakan')
    <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">Kembali ke halaman utama</a>
    <a href="{{ url('/trails') }}" class="inline-flex min-h-11 items-center justify-center rounded-control border border-control bg-white px-4 py-2 text-sm font-medium text-gray-800 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2">Telusuri jalur</a>
@endsection
