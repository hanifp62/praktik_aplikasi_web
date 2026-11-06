# Sintesis riset UI/UX dan IMK Memount v2

**Tanggal:** 25 September 2026. **Sumber:** empat laporan di folder ini (01 bahasa desain OS,
02 benchmark aplikasi outdoor dan konteks Indonesia, 03 IMK dan aksesibilitas, 04 platform web).
**Status:** keputusan turunan dari arahan pemilik 25 September 2026 (spec
`docs/superpowers/specs/2026-09-25-memount-v2-design.md`, DK-25, DK-26). Label bukti mengikuti
laporan sumbernya; tidak ada angka hasil uji pengguna di sini, karena uji itu belum dijalankan.

## 1. Diagnosis "masih CRUD sederhana"

Ketiga laporan sampai pada simpulan yang sama dari arah berbeda:

1. **Susunan per entitas, bukan per momen perjalanan** (02 temuan 1; 03 §3.11). Setiap layar
   menampilkan satu entitas dan tombol simpan; layar tidak tahu posisi pengguna dalam siklus trip.
2. **Tindakan tanpa hasil yang segera terlihat** (03 §3.11): tanpa umpan balik 100 ms, tanpa
   optimistic UI, tanpa undo.
3. **Kekosongan tampil sebagai kotak kosong** (03 §3.11), padahal data lapangan memang belum ada
   (0 geometri, 0 pos berkoordinat).
4. **Gerak dan material disetel paling tenang** oleh turunan agen (`DESIGN.md` MOTION 1), tetapi
   laporan 01 dan 03 sepakat: animasi sendirian tidak menyelesaikan tiga hal di atas. Hierarki
   yang tegas dan kesinambungan lebih berpengaruh daripada kaca (01 temuan 3).

## 2. Keputusan desain v2

| # | Keputusan | Dasar | Aturan yang dijaga |
|---|---|---|---|
| D1 | Beranda berpusat pada satu kartu "Perjalanan berikutnya" yang isinya berganti menurut fase trip (H-30 izin dibuka, H-2 izin tutup, H-7 persiapan kritis, H-1 cek kesiapan, hari H mulai mendaki, pasca catat dan lapor), satu aksi utama per fase, dengan status resmi dan cuaca bersumber | 02 H1; 03 §3.1 | R-012, R-013, R-014 |
| D2 | Kesiapan ditulis sebagai hitungan dan faktor pendorong ("5 dari 8 butir siap", "masih kurang 3 hal"), tidak pernah persen, cincin, atau gelombang | 01 §5.2; 02 T4; 03 §4.8 | R-005, R-014 |
| D3 | Ruang kerja trip berbasis linimasa: Fit, Rencana, Persiapan, Cek kesiapan, Mendaki, Laporkan, dengan rel perjalanan bermotif tanda "Jalur Berpos" | 02 T1; 03 §3.1 | R-001, R-002 |
| D4 | Tab Mendaki punya enam keadaan (S0 sampai S5); pendakian aktif muncul sebagai strip tetap di atas bilah tab di semua tab; tombol Back mengecilkan mode lapangan, bukan mengakhirinya | 03 §3.3; 01 temuan 5 | R-014 |
| D5 | Mode lapangan: tanpa kaca, kontras tinggi, aksi utama 56 sampai 64 px, pos berikutnya dengan jarak berpembulatan dan berlabel ("garis lurus" bila geometri belum ada), akurasi ± m dan umur posisi selalu terlihat, waktu tempuh sebagai rentang bersumber, Layar tetap menyala sebagai sakelar bawaan mati | 03 §4.3 sampai §4.5; 02 K2 | R-005 |
| D6 | Pos berikutnya dihitung di perangkat dari daftar pos yang sudah dimuat, supaya bekerja tanpa sinyal; pos yang tercapai diantrekan dan dikirim saat daring | 03 §4.7 temuan 2 | Privasi lokasi (data tetap di perangkat) |
| D7 | Paket lapangan luring (ubin di-host sendiri, data stabil disimpan, data cepat berubah berlabel "terakhir diketahui") **menunggu izin pemilik**: mengubah kebijakan service worker | 02 K1; 03 §4.7 | R-012, R-013 |
| D8 | Material: kanvas dan isi solid; chrome (bilah tab, bilah atas, strip pendakian aktif) solid sebagai bawaan; kaca hanya peningkatan bersyarat di luar peta; paling banyak dua elemen kaca per layar; pengaturan Efek transparan dan Gerak di Profil | 01 §4.1, §5.1 | WCAG 1.4.3 |
| D9 | Radius konsentris (kontrol 12 px, kartu 16 px, lapisan luar 24 px, chip dan segmen penuh) | 01 §5.1 | Tidak ada |
| D10 | Gerak dial 2 dengan daftar tertutup: umpan balik tekan, buka-tutup lembar, kartu jalur ke rincian (View Transitions), tiba di pos, konfirmasi butir persiapan. Kurva pegas dihitung (redaman 0,9 posisi, 1,0 pudar, 0,75 sesudah gestur); tanpa animasi masuk, tanpa loop; `prefers-reduced-motion` dan pengaturan Gerak mengganti gerak dengan pudar singkat | 01 §5.1; 03 §3.11 | WCAG 2.3.3 |
| D11 | Bilah tab: lima tab tetap terlihat (tidak menyusut saat gulir), label satu kata minimal 12 px tanpa elipsis, ikon aktif berbobot isi plus label, garis, dan `aria-current`; lencana hanya untuk hal yang menuntut tindakan | 01 temuan 4; 03 §3.2 | WCAG 1.4.1, 2.4.11 |
| D12 | Layar lebar: rail kiri dengan lima tujuan yang sama, urutan dan label identik | 01 §5.2; 03 §3.9 | WCAG 3.2.3 |
| D13 | Visual utama kartu jalur digambar dari data jalur (bentuk rute atau siluet profil elevasi), sejalan dengan tanda "Jalur Berpos"; tanpa foto komunitas sebagai hero | 01 §5.2 | R-017 |
| D14 | Status resmi sebagai chip netral dengan sumber dan waktu, tanpa hijau dan centang; UNKNOWN bergaya sendiri (garis putus-putus, ikon, "Belum diketahui") | 01 §5.1; 03 §4.8 | R-006, R-012 |
| D15 | Setiap fakta penting membawa chip sumber yang dapat diketuk (otoritas dalam teks, lembaga, waktu relatif dan mutlak dalam zona gunung, cakupan, verifikasi) | 03 §4.9 | R-009, R-013 |
| D16 | Community sebagai lapisan dengan tata bahasa visual tetap: biru komunitas selalu bersama kata "Laporan pendaki", terpisah dari keterangan resmi; titik masuk kontekstual di Beranda, rincian jalur, ringkasan pendakian | 01 §5.2; 03 §3.4 | R-009, R-010, R-017 |
| D17 | Progres membandingkan diri sendiri ("pendakian ini dibanding sebelumnya"), berbalik sebelum puncak tampil sebagai keputusan sah, tanpa streak, peringkat, atau level | 02 temuan 10; 03 §5.6 | R-015 |
| D18 | Keadaan kosong tiga jenis (pertama kali, tersaring habis, data belum ada) masing-masing dengan langkah berikutnya | 03 §3.11 | R-006, R-007 |
| D19 | Optimistic UI dan undo untuk simpan jalur, centang persiapan, dan tambah ke banding; tidak untuk status resmi, hasil cek kesiapan, atau publikasi | 03 §3.11 | R-012 |
| D20 | Ukuran teks mengikuti OS (`<meta name="text-scale">`, Chrome 146) | 03 §6.8 | WCAG 1.4.4 |

## 3. Butuh keputusan pemilik (tidak dibangun sebelum ada jawaban)

| Hal | Laporan | Catatan |
|---|---|---|
| Paket lapangan luring dan sumber ubin yang di-host sendiri | 02 K1; 03 §4.7 | Mengubah kebijakan service worker di PRODUCT.md "Operating Context" |
| Notifikasi perubahan status dan jendela izin | 02 H2 | Tabel notifikasi, kanal, dan kebijakan data pribadi |
| Grade resmi Kemenhut di Route Fit | 02 E2 | Menyentuh R-003 dan gerbang publikasi |
| PVMBG/MAGMA sebagai otoritas baru | 02 E1 | Hierarki R-009; status bahaya tidak dipetakan ke OPEN/CLOSED (DK-22) |
| "Apa yang dapat mengubah label" di penjelasan Route Fit | 02 T7; 03 §5.2 | Hipotesis risiko: mendorong pengguna menggelembungkan isian profil |
| Definisi satu kalimat dan saran tindakan untuk COCOK, PERLU PERSIAPAN, KURANG COCOK | 03 §4.8 | Syarat agar tiga label bekerja sebagai komunikasi risiko |
| Laporan tertambat pos dan tag laporan baru | 02 C1 | Enum tag baru |

## 4. Yang tidak ditiru

Tab bar yang menyusut saat gulir; kaca di atas peta, kartu, atau kaca lain; refraksi SVG;
bar yang berganti warna mengikuti isi; partikel, halo, efek 3D dekoratif; morphing bentuk
sebagai hiasan; progres gelombang atau cincin persen untuk kesiapan dan kecocokan; foto
komunitas sebagai hero; streak, level, segmen, papan peringkat; unggahan jalur bebas (jalur
ilegal diblokir pengelola); rute buatan AI; keselamatan berbayar; feed sosial sebagai tab;
getar atau warna sebagai satu-satunya umpan balik. Rinciannya di 01 §6 dan 02 §6.8.
