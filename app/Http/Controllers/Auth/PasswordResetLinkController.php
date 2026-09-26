<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'title' => 'Lupa Kata Sandi',
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return back()->with('status', __($status));
    }
}
