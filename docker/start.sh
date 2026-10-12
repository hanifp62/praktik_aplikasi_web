#!/bin/sh
# Start kontainer produksi (Dockerfile). Konfigurasi, rute, dan view di-cache dengan variabel lingkungan platform, lalu
# FrankenPHP melayani public/ di port yang diberikan platform (Vercel: 80). Dua hal sengaja tidak dijalankan di sini.
# Migrasi: setiap instans yang bangun akan menjalankannya dan panduan Vercel melarangnya, jadi skema Supabase diperbarui
# lewat workflow "Migrasi Supabase". Penjadwal: instans tidur sesudah lima menit tanpa trafik, jadi tugas terjadwal
# dipicu Vercel Cron lewat /api/cron (vercel.json, CronController), bukan schedule:work. Kegagalan langkah mana pun
# menghentikan kontainer, sehingga instans tidak pernah melayani dalam keadaan setengah siap. Di Vercel kegagalan itu
# tampak sebagai galat permintaan dan di log runtime, bukan sebagai deploy gagal; job CI `image` menangkapnya lebih dulu.
set -e

php artisan optimize --no-interaction

exec frankenphp php-server --listen ":${PORT:-80}" --root public/
