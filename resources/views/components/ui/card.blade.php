@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'rounded-lg bg-white p-5 shadow-sm']) }}>
    @if ($title)
        <h2 class="text-base font-semibold text-primary">{{ $title }}</h2>
    @endif
    @if ($subtitle)
        <p class="mt-1 text-sm text-secondary">{{ $subtitle }}</p>
    @endif
    <div @class(['mt-3' => $title || $subtitle])>{{ $slot }}</div>
</section>
