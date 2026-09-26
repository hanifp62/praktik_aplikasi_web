<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ConfirmablePasswordController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword', [
            'title' => 'Konfirmasi Kata Sandi',
        ]);
    }

    /**
     * Tujuannya dibuka dengan muat penuh karena bisa rute yang bukan halaman Inertia,
     * sama seperti sesudah masuk (AuthenticatedSessionController::store).
     *
     * @throws ValidationException
     */
    public function store(ConfirmPasswordRequest $request): SymfonyResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->validated('password'),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return Inertia::location(redirect()->intended(route('dashboard', absolute: false)));
    }
}
