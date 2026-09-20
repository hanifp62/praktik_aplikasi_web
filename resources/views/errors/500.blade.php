@extends('errors.layout')

@section('kode', 'Galat 500')
@section('judul', 'Ada yang bermasalah di sisi kami')

@section('penjelasan')
    <p>Kesalahan ini bukan berasal dari yang Anda lakukan. Kejadiannya sudah tercatat untuk diperiksa.</p>
    <p>Kalau Anda sedang bersiap berangkat, jangan menunggu aplikasi ini: pastikan status jalur langsung ke pengelola atau basecamp.</p>
@endsection

@section('tindakan')
    <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">Kembali ke halaman utama</a>
@endsection
