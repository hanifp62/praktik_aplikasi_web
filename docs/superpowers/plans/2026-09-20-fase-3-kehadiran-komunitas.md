# Fase 3: Kehadiran Komunitas dan Pertumbuhan — Rencana Implementasi

**Spec:** `docs/superpowers/specs/2026-09-20-pengalaman-pendaki-design.md`

**Tujuan:** Memberi alasan membuka aplikasi ketika tidak sedang merencanakan apa pun, dan
membuat aplikasi ini terlihat oleh orang yang mencari nama gunung di mesin pencari.

## Revisi yang dibawa riset

Aturan adaptasi 1 pada spec sudah direvisi: **papan peringkat kontribusi dibatalkan.**

Pemetaan sistematis atas efek negatif gamifikasi menemukan kehilangan performa sebagai
efek yang paling sering muncul dengan papan peringkat sebagai elemen paling sering
disebut; penelitian pada Stack Overflow menemukan kebutuhan memenangkan reputasi
memengaruhi kualitas jawaban. Untuk produk ini, memberi peringkat atas jumlah laporan
menghasilkan laporan bervolume tinggi bermutu rendah, dan itu menyerang §60.

Gantinya pengakuan atas kegunaan: ucapan terima kasih dari sesama pendaki, dan umpan
balik berapa orang membaca laporan itu.

## Pengukuran keadaan awal

| Yang dibutuhkan | Ada? |
|---|---|
| Konsep mengikuti gunung | **Tidak ada** |
| Notifikasi | **Tidak ada** direktorinya |
| Rute publik | **Satu**, yaitu halaman depan |
| Peristiwa analitik | Ada delapan, semuanya alur perencanaan |
| Moderasi laporan | Ada dan berjalan |

## Batasan menyeluruh

- **§92**: resmi dan komunitas tidak pernah tertukar, termasuk di halaman publik.
- **§94 dan §95**: halaman publik tidak boleh menyajikan status basi sebagai keadaan
  kini. Ini batasan terberat fase ini dan dibahas tersendiri di Tugas 4.
- **Tidak ada mekanisme yang memberi hadiah pada volume.**
- WCAG 2.2 AA, mobile-first, anggaran query, suite hijau, Pint bersih.
- Setiap tugas: test gagal dulu, perbaikan, Pint, commit.

---

## Tugas 1: Terima kasih pada laporan kondisi

**Berkas:** migrasi `report_thanks`, `app/Models/ReportThank.php`,
`app/Livewire/Trails/ThankReport.php`, test.

Menghargai laporan yang menolong, bukan laporan yang banyak.

- [ ] Test gagal dulu: pendaki dapat berterima kasih sekali pada satu laporan; ucapan
      kedua tidak menggandakan; ia dapat menariknya kembali.
- [ ] Test: pelapor **tidak dapat** berterima kasih pada laporannya sendiri.
- [ ] Test: hanya laporan yang lolos moderasi yang dapat diberi terima kasih, karena
      laporan tertunda belum terlihat siapa pun.
- [ ] Test: jumlahnya tampil di laporan, dan anggaran query halaman jalur tidak naik per
      laporan.
- [ ] Pint, suite, commit.

## Tugas 2: Umpan balik dampak bagi pelapor

**Berkas:** `app/Livewire/History/HikerProgress.php`, layanan, test.

- [ ] Test gagal dulu: pelapor melihat berapa laporannya yang terbit dan berapa ucapan
      terima kasih yang diterimanya, di halaman progresnya sendiri.
- [ ] Test: angka itu miliknya sendiri, tidak pernah tercampur pengguna lain, dan tidak
      pernah ditampilkan sebagai peringkat.
- [ ] Pint, suite, commit.

## Tugas 3: Kabar jalur

**Berkas:** `app/Models/MountainFollow.php` beserta migrasinya,
`app/Livewire/Feed/TrailNews.php`, test.

Satu-satunya Trigger dalam kerangka Fogg: alasan membuka aplikasi ketika tidak sedang
merencanakan.

- [ ] Test gagal dulu: pendaki dapat mengikuti dan berhenti mengikuti sebuah gunung.
- [ ] Test: kabarnya memuat perubahan status resmi dan laporan yang lolos moderasi untuk
      gunung yang diikuti, terurut waktu, dan **tidak memuat** gunung yang tidak diikuti.
- [ ] Test: laporan tertunda dan ditolak tidak pernah muncul di kabar.
- [ ] Test: §92 tetap berlaku di kabar, yaitu perubahan status resmi terbedakan dari
      laporan komunitas secara visual.
- [ ] Test: pendaki yang belum mengikuti gunung mana pun mendapat keadaan awal yang
      mengajaknya memilih, bukan layar kosong.
- [ ] Pint, suite, commit.

## Tugas 4: Halaman jalur publik

**Berkas:** rute publik, `app/Livewire/Public/PublicTrail.php`, sitemap, test.

Mesin pertumbuhan AllTrails, dan saat ini nol: seluruh halaman jalur berada di balik
login, sehingga aplikasi ini tidak terlihat oleh siapa pun yang mencari nama gunung.

**Batasan terberat fase ini.** Halaman publik akan diindeks, disalin, dan ditampilkan
sebagai cuplikan yang berumur lebih panjang daripada isinya. §94 dan §95 melarang
menyajikan status basi sebagai keadaan kini, dan mesin pencari justru membuat persis itu.

Rancangannya menjawab dengan tidak menampilkan yang berumur pendek:

- [ ] Test gagal dulu: halaman jalur publik dapat dibuka tanpa login dan memuat nama,
      gunung, provinsi, karakteristik, dan pos.
- [ ] Test: halaman publik **tidak memuat status resmi maupun prakiraan cuaca**, karena
      keduanya berumur pendek dan cuplikan pencarian akan mengawetkannya. Sebagai
      gantinya halaman menyebut bahwa status terkini ada di dalam aplikasi.
- [ ] Test: halaman publik tidak memuat data pribadi siapa pun, termasuk nama pelapor.
- [ ] Test: jalur yang belum terbit atau sudah diarsipkan tidak dapat diakses publik.
- [ ] Test: sitemap hanya memuat jalur yang terbit.
- [ ] Test: halaman yang sudah ada tetap berada di balik login; membuka satu pintu tidak
      membuka yang lain.
- [ ] Pint, suite, commit.

---

## Tinjauan mandiri

- Tidak ada tugas yang memberi hadiah pada volume.
- Tugas 1 dan 2 berpasangan: yang pertama mengumpulkan sinyalnya, yang kedua
  mengembalikannya kepada pelapor.
- Tugas 4 berdiri sendiri dan dapat dikerjakan lebih dulu bila pertumbuhan lebih
  mendesak daripada kehadiran komunitas.
