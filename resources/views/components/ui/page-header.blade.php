@props(['title', 'description' => null])

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-primary">{{ $title }}</h1>
    @if ($description)
        <p class="mt-1 text-sm text-secondary">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
