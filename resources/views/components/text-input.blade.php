@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-control focus:border-brand-600 focus:ring-brand-600 rounded-md']) }}>
