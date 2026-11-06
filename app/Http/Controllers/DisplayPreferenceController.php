<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisplayPreferenceRequest;
use App\Support\DisplayPreference;
use Illuminate\Http\RedirectResponse;

class DisplayPreferenceController extends Controller
{
    /**
     * Menyimpan pilihan Tampilan ke cookie lalu kembali ke halaman asal. Hanya pilihan
     * yang dikirim yang berubah, supaya satu sakelar tidak mengembalikan yang lain.
     */
    public function store(StoreDisplayPreferenceRequest $request): RedirectResponse
    {
        $respons = back();

        foreach ([DisplayPreference::COOKIE_EFEK => 'efek', DisplayPreference::COOKIE_GERAK => 'gerak'] as $cookie => $isian) {
            if ($request->has($isian)) {
                $respons = $respons->withCookie(DisplayPreference::cookieFor($cookie, $request->validated($isian)));
            }
        }

        return $respons;
    }
}
