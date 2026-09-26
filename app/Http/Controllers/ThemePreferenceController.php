<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThemePreferenceRequest;
use App\Support\ThemePreference;
use Illuminate\Http\RedirectResponse;

class ThemePreferenceController extends Controller
{
    /**
     * Menyimpan pilihan tema ke cookie lalu kembali ke halaman asal, yang dirender ulang
     * oleh server dengan atribut data-theme yang baru. Tidak ada state di sesi maupun
     * basis data, jadi endpoint ini sama murahnya untuk tamu dan pengguna masuk.
     */
    public function store(StoreThemePreferenceRequest $request): RedirectResponse
    {
        return back()->withCookie(ThemePreference::cookieFor($request->validated('tema')));
    }
}
