<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center min-h-11 px-4 py-2 bg-white border border-control rounded-control font-semibold text-xs text-secondary uppercase tracking-widest hover:bg-surface-sunken focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
