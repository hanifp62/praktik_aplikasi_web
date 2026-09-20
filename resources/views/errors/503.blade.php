@extends('errors.layout')

@section('kode', 'Galat 503')
@section('judul', 'Aplikasi sedang dalam perawatan')

@section('penjelasan')
    <p>Layanan dihentikan sementara dan akan kembali. Coba beberapa saat lagi.</p>
    <p>Kalau Anda sedang bersiap berangkat, pastikan status jalur langsung ke pengelola atau basecamp.</p>
@endsection

@section('tindakan')
    <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">Coba lagi</a>
@endsection
