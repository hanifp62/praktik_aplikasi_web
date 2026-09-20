{{--
    Tombol utama halaman auth dan profil, diteruskan ke tombol produk.

    Delapan halaman warisan Breeze memakai tombol netral gelap sementara seluruh
    aplikasi memakai tombol merek, jadi layar pertama yang dilihat orang membawa tombol
    yang berbeda dari produknya. Berkas ini dipertahankan alih-alih dihapus supaya
    delapan halaman itu tidak perlu disunting satu per satu, dan supaya bentuk tombol
    tetap punya satu definisi.
--}}
<x-ui.button type="submit" {{ $attributes }}>{{ $slot }}</x-ui.button>
