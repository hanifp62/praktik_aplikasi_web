<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-subtle">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-primary" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('goals.create')" :active="request()->routeIs('goals.*')" wire:navigate>
                        Buat Rencana
                    </x-nav-link>
                    <x-nav-link :href="route('trails.index')" :active="request()->routeIs('trails.*')" wire:navigate>
                        Jalur
                    </x-nav-link>
                    <x-nav-link :href="route('trips.index')" :active="request()->routeIs('trips.*')" wire:navigate>
                        Trip
                    </x-nav-link>
                    <x-nav-link :href="route('history')" :active="request()->routeIs('history')" wire:navigate>
                        Riwayat
                    </x-nav-link>
                    <x-nav-link :href="route('progress')" :active="request()->routeIs('progress')" wire:navigate>
                        Progres
                    </x-nav-link>
                    <x-nav-link :href="route('news')" :active="request()->routeIs('news')" wire:navigate>
                        Kabar
                    </x-nav-link>
                    @if (auth()->user()?->isModerator())
                        <x-nav-link :href="route('moderation.queue')" :active="request()->routeIs('moderation.*')" wire:navigate>
                            Moderasi
                        </x-nav-link>
                    @endif
                    @if (auth()->user()?->isAdmin())
                        <x-nav-link :href="route('admin.trails')" :active="request()->routeIs('admin.*')" wire:navigate>
                            Admin
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center min-h-11 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-muted bg-white hover:text-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="flex min-h-11 w-full items-center text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex min-h-11 min-w-11 items-center justify-center p-2 rounded-md text-muted hover:text-muted hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:bg-surface-sunken focus-visible:text-muted transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        {{--
            Menu ini harus mencerminkan menu desktop. PRD §88 menempatkan ponsel sebagai
            platform utama, sehingga navigasi yang hanya memuat Dashboard berarti seluruh
            aplikasi tidak terjangkau justru di tempat ia paling sering dipakai.
        --}}
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                Dasbor
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('goals.create')" :active="request()->routeIs('goals.*')" wire:navigate>
                Buat Rencana
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('trails.index')" :active="request()->routeIs('trails.*')" wire:navigate>
                Jalur
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('trips.index')" :active="request()->routeIs('trips.*')" wire:navigate>
                Trip
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('history')" :active="request()->routeIs('history')" wire:navigate>
                Riwayat
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('progress')" :active="request()->routeIs('progress')" wire:navigate>
                Progres
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('news')" :active="request()->routeIs('news')" wire:navigate>
                Kabar
            </x-responsive-nav-link>
            @if (auth()->user()?->isModerator())
                <x-responsive-nav-link :href="route('moderation.queue')" :active="request()->routeIs('moderation.*')" wire:navigate>
                    Moderasi
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()?->isAdmin())
                <x-responsive-nav-link :href="route('admin.trails')" :active="request()->routeIs('admin.*')" wire:navigate>
                    Admin
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-subtle">
            <div class="px-4">
                <div class="font-medium text-base text-primary" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-muted">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="flex min-h-11 w-full items-center text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
