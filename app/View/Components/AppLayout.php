<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Komponen Blade punya scope terisolasi, sehingga judul yang dikirim lewat data
     * view tidak otomatis sampai ke layout. Menerimanya sebagai prop membuat halaman
     * biasa punya judul sendiri sebagaimana halaman Livewire.
     */
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
