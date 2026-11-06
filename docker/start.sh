#!/bin/sh
# Start kontainer produksi (Dockerfile). Konfigurasi, rute, dan view di-cache dengan variabel lingkungan platform, lalu
# FrankenPHP melayani public/ di port yang diberikan platform (Vercel: 80). Dua hal sengaja tidak dijalankan di sini.
# Migrasi: setiap instans yang bangun akan menjalankannya dan panduan Vercel melarangnya, jadi skema Supabase diperbarui
# lewat workflow "Migrasi Supabase". Penjadwal: instans tidur sesudah lima menit tanpa trafik, jadi tugas terjadwal
# dipicu Vercel Cron lewat /api/cron (vercel.json, CronController), bukan schedule:work. Kegagalan langkah mana pun
# menghentikan kontainer, sehingga instans tidak pernah melayani dalam keadaan setengah siap. Di Vercel kegagalan itu
# tampak sebagai galat permintaan dan di log runtime, bukan sebagai deploy gagal; job CI `image` menangkapnya lebih dulu.
set -e

# Image ini hanya melayani produksi publik di belakang proxy Vercel, jadi dua nilai tidak diserahkan ke variabel
# platform (13 Oktober 2026: Vercel sempat berisi APP_ENV=local dan APP_DEBUG=true). Mode debug memperlihatkan jejak
# galat dan header permintaan, termasuk token OIDC Vercel, kepada siapa pun yang memicu galat; APP_ENV selain production
# mematikan URL https dan memuat rute pengembangan. Keduanya dipaksa, dan nilai yang diabaikan dicatat di log runtime.
# Proxy tepercaya dan kanal log hanya diberi bawaan: semua trafik datang lewat proxy Vercel, yang menimpa
# X-Forwarded-For, dan berkas log di dalam kontainer hilang bersama instansnya.
if [ "${APP_ENV:-production}" != production ] || [ "${APP_DEBUG:-false}" != false ]; then
    echo "start.sh: APP_ENV=${APP_ENV:-} dan APP_DEBUG=${APP_DEBUG:-} diabaikan; image produksi selalu APP_ENV=production dan APP_DEBUG=false." >&2
fi
export APP_ENV=production APP_DEBUG=false
export TRUSTED_PROXIES="${TRUSTED_PROXIES:-*}" LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

php artisan optimize --no-interaction

exec frankenphp php-server --listen ":${PORT:-80}" --root public/
