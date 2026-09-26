<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\PetunjukKataSandi;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'title' => 'Daftar',
            'petunjukSandi' => PetunjukKataSandi::teks(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        // features/07: "Setelah berhasil, pengguna langsung masuk dan diarahkan mengisi
        // preferensi awal." Profil pendakian yang menentukan seluruh rekomendasi, jadi
        // langkah pertama sesudah mendaftar adalah mengisinya, bukan dasbor kosong.
        return redirect(route('onboarding', absolute: false));
    }
}
