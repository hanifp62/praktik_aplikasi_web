<?php

namespace App\Livewire\History;

use App\Services\HikerProgressService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman progres pendaki.
 *
 * Satu-satunya halaman di aplikasi ini yang tidak melayani satu pendakian tertentu,
 * dan karena itu satu-satunya alasan membukanya ketika tidak sedang merencanakan apa
 * pun. Dalam kerangka Fogg, di sinilah motivation berada; sebelumnya aplikasi ini hanya
 * melayani ability.
 */
#[Layout('layouts.app')]
#[Title('Progres Pendakian')]
class HikerProgress extends Component
{
    public function render(HikerProgressService $progres)
    {
        $user = auth()->user();

        return view('livewire.history.hiker-progress', [
            'angka' => $progres->forUser($user),
            'gunung' => $progres->summitedMountains($user),
        ]);
    }
}
