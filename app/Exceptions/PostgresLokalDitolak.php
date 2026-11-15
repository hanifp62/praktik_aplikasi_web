<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat persiapan PostgreSQL lokal (`memount:lokal` atau `LocalPostgisSeeder`)
 * mendeteksi lingkungan selain lokal, pengemudi selain PostgreSQL, atau sasaran host
 * di luar loopback mesin pengembang.
 */
final class PostgresLokalDitolak extends RuntimeException {}
