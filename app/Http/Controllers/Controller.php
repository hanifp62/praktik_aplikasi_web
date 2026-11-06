<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Laravel 12 ke atas tidak lagi menyertakan trait otorisasi pada controller dasar.
 * Menyertakannya di sini membuat $this->authorize() tersedia di seluruh controller,
 * sehingga pemeriksaan policy tidak terlewat hanya karena lupa menambahkan trait.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
