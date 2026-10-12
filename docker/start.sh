#!/bin/sh
# Start kontainer produksi (Dockerfile). Konfigurasi, rute, dan view di-cache dengan variabel lingkungan platform;
# migrasi dijalankan (semuanya aditif, DK-28, dan tanpa migrasi tertunda ia selesai seketika, jadi aman diulang setiap
# instans bangun); penjadwal berjalan di latar selama instans hidup; lalu FrankenPHP melayani public/ di port yang
# diberikan platform. Kegagalan langkah mana pun menghentikan kontainer, sehingga deploy terlihat gagal, bukan setengah jalan.
set -e

php artisan optimize --no-interaction
php artisan migrate --force --no-interaction

php artisan schedule:work --no-interaction &

exec frankenphp php-server --listen ":${PORT:-10000}" --root public/
