<?php

namespace App\Livewire\Feed;

use App\Services\TrailNewsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Kabar dari gunung yang diikuti pendaki.
 *
 * Satu-satunya layar di aplikasi ini yang tidak melayani satu rencana tertentu, dan
 * karena itu satu-satunya alasan membukanya ketika tidak sedang merencanakan apa pun.
 *
 * Isinya keadaan gunung, bukan aktivitas orang. Tidak ada siapa mendaki apa, tidak ada
 * peringkat, tidak ada yang mengundang perbandingan.
 */
#[Layout('layouts.app')]
#[Title('Kabar Jalur')]
class TrailNews extends Component
{
    public function render(TrailNewsService $kabar)
    {
        $user = auth()->user();

        return view('livewire.feed.trail-news', [
            'kabar' => $kabar->forUser($user),
            'gunung' => $kabar->followedMountains($user),
        ]);
    }
}
