## Ringkasan

<!-- Apa yang berubah dan kenapa, dalam satu atau dua kalimat. Tautkan isu, tugas rencana, atau keputusan (DK) yang terkait. -->

## Bukti

<!-- Nama test, keluaran perintah dengan angka persis, dan tangkapan 390 px serta 1440 px untuk perubahan UI. Tanpa bukti, tidak ada PASS (R-023). -->

## Aturan yang tersentuh

<!-- Sebut aturan R dan BR yang disentuh perubahan ini dan bagaimana dipatuhi, misalnya R-004 label kecocokan atau R-012 status Buka bukan jaminan. -->

## Daftar periksa

- [ ] Test ditulis lebih dulu dan gagal sebelum implementasi
- [ ] `php artisan test --compact --testsuite=Unit,Feature` hijau
- [ ] Suite `Spatial` dijalankan bila menyentuh kolom spasial, transaksi, atau constraint (R-024)
- [ ] `npm run types` nol galat
- [ ] `npm run build` sukses
- [ ] `vendor/bin/pint --test` bersih
- [ ] Delivery Gate antislop PASS dengan bukti per butir untuk perubahan UI dan teks
- [ ] Aturan R dan BR yang tersentuh disebut di atas dan dipatuhi
- [ ] Tidak ada kredensial, `.env`, atau dump basis data; `SecretsNotInRepoTest` hijau
- [ ] Migrasi baru tidak dijalankan di Supabase tanpa izin pemilik (R-019)
- [ ] Dokumen yang terdampak diperbarui; `HANDOVER.md` bagian 0 diperbarui di akhir gelombang
- [ ] Dokumen baru di folder docs didaftarkan di `docs/README.md`
- [ ] Rekap memuat persentase progres beserta metodenya
