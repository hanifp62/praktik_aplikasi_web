<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Logout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'title' => 'Masuk',
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Tujuan sesudah masuk bisa rute mana saja yang tadi ditolak middleware auth,
     * termasuk yang bukan halaman Inertia (foto laporan). Kunjungan Inertia yang
     * mengikuti redirect ke rute seperti itu berakhir sebagai galat, jadi tujuannya
     * dibuka dengan muat penuh (409 + X-Inertia-Location). Muat penuh sekaligus
     * menyegarkan token CSRF dan props bersama sesudah sesi berganti. Permintaan
     * non-Inertia tetap mendapat redirect biasa.
     */
    public function store(LoginRequest $request): SymfonyResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return Inertia::location(redirect()->intended(route('dashboard', absolute: false)));
    }

    public function destroy(Logout $logout): RedirectResponse
    {
        $logout();

        return redirect('/');
    }
}
