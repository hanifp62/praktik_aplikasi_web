<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kirim ulang email verifikasi, dari halaman verifikasi maupun dari halaman Profil.
 * Pengguna yang sudah terverifikasi diteruskan ke tujuannya dengan muat penuh, karena
 * tujuan itu bisa rute yang bukan halaman Inertia (AuthenticatedSessionController::store).
 */
class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Inertia::location(redirect()->intended(route('dashboard', absolute: false)));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
