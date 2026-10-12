# syntax=docker/dockerfile:1

# Image produksi Memount untuk platform kontainer (Render, render.yaml). FrankenPHP 1.x dengan PHP 8.5, sama dengan
# target runtime proyek (CLAUDE.md R-020). Dua tahap: tahap build memasang dependensi Composer dan Node lalu membangun
# aset Vite. Build Vite memanggil `php artisan wayfinder:generate`, jadi PHP dan Node harus ada di tahap yang sama.
# Tahap akhir hanya membawa kode, vendor, dan public/build, tanpa Node dan tanpa node_modules. Diuji job CI `image`.

FROM dunglas/frankenphp:1-php8.5-alpine AS build

RUN install-php-extensions pdo_pgsql gd opcache zip \
    && apk add --no-cache libstdc++ libgcc

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && node --version \
    && npm --version

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY package.json package-lock.json ./
RUN npm ci --include=dev --no-audit --no-fund

COPY . .

# .env sementara hanya untuk build (Wayfinder membaca rute) dan dibuang sebelum tahap akhir: konfigurasi produksi
# datang dari variabel lingkungan platform, bukan dari berkas di dalam image.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && cp .env.example .env \
    && php artisan key:generate --force --no-interaction \
    && npm run build \
    && rm -rf .env node_modules

FROM dunglas/frankenphp:1-php8.5-alpine

# Foto laporan ditulis ulang lewat GD (ImageSanitizer), jadi JPEG, PNG, dan WebP wajib ada; build gagal bila tidak.
RUN install-php-extensions pdo_pgsql gd opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && php -r 'exit(gd_info()["JPEG Support"] && gd_info()["PNG Support"] && gd_info()["WebP Support"] ? 0 : 1);'

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-memount.ini"

WORKDIR /app
COPY --from=build /app /app

RUN chmod +x docker/start.sh \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

USER www-data

ENV PORT=10000
EXPOSE 10000

CMD ["docker/start.sh"]
