<?php

use Tests\Pratinjau\PenyajiStatis;

/*
 * Router `php -S` untuk harness pratinjau (ruling W1-R4, W1-K8, W1-10a).
 *
 * Router ini membaca dan mengirim sendiri setiap berkas yang lolos
 * PenyajiStatis::berkasUntuk(), dan alamat yang ditolak dijawab 404 di sini juga. Tidak ada
 * jalan yang menyerahkan keputusan penyajian kembali ke php -S: peninjauan W1-10a
 * membuktikan live bahwa php -S menafsirkan ulang URI mentah dengan caranya sendiri,
 * berbeda dari PenyajiStatis, sehingga alamat seperti host semu ("//x/berkas") atau
 * traversal ("/../public/berkas") bisa berakhir menjalankan public/index.php walau
 * PenyajiStatis sendiri menilainya sah, mem-boot Laravel dengan .env produksi. Dengan
 * router ini yang selalu membaca badan responsnya sendiri, php -S tidak pernah lagi
 * diberi kesempatan menafsirkan URI mentah, baik untuk alamat yang lolos maupun yang
 * ditolak. Pemakaian dari root repo:
 *
 *   DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
 */

require __DIR__.'/PenyajiStatis.php';

$tipeKonten = [
    'html' => 'text/html; charset=UTF-8',
    'js' => 'text/javascript; charset=UTF-8',
    'css' => 'text/css; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'webmanifest' => 'application/manifest+json; charset=UTF-8',
    'txt' => 'text/plain; charset=UTF-8',
    'map' => 'application/json; charset=UTF-8',
    'svg' => 'image/svg+xml; charset=UTF-8',
    'woff2' => 'font/woff2',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'jpg' => 'image/jpeg',
    'ico' => 'image/x-icon',
];

$metodeHead = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD';

$berkas = PenyajiStatis::berkasUntuk($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['DOCUMENT_ROOT'] ?? __DIR__.'/../../public');

if ($berkas === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');

    if (! $metodeHead) {
        echo 'Tidak ada di pratinjau statis.';
    }
} else {
    $ekstensi = strtolower(pathinfo($berkas, PATHINFO_EXTENSION));

    header('Content-Type: '.($tipeKonten[$ekstensi] ?? 'application/octet-stream'));
    header('Content-Length: '.filesize($berkas));

    if (! $metodeHead) {
        readfile($berkas);
    }
}
