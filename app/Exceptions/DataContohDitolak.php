<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Data contoh (DK-35) menolak ditulis ke mana pun selain berkas SQLite lokal di folder
 * database/. Dilempar PenjagaDataContoh dan DemoSeeder sebelum satu baris pun ditulis.
 */
final class DataContohDitolak extends RuntimeException {}
