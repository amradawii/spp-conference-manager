<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { HTMLButtonAttributes } from 'svelte/elements';

    type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'danger-solid';
    type Size = 'sm' | 'md';

    let {
        variant = 'secondary',
        size = 'md',
        type = 'button',
        class: className = '',
        children,
        ...rest
    }: HTMLButtonAttributes & { variant?: Variant; size?: Size; children: Snippet } = $props();

    const variants: Record<Variant, string> = {
        primary: 'bg-ink-900 text-white hover:bg-ink-800 disabled:bg-ink-400',
        secondary: 'bg-white text-ink-800 ring-1 ring-inset ring-line-strong hover:bg-ink-50',
        ghost: 'text-ink-600 hover:bg-ink-50 hover:text-ink-900',
        danger: 'text-rose-700 hover:bg-rose-50',
        'danger-solid': 'bg-rose-600 text-white hover:bg-rose-700',
    };

    const sizes: Record<Size, string> = {
        sm: 'h-8 gap-1.5 px-3 text-xs',
        md: 'h-10 gap-2 px-4 text-sm',
    };
</script>

<button
    {type}
    class="inline-flex shrink-0 items-center justify-center rounded-lg font-medium whitespace-nowrap transition-colors disabled:cursor-not-allowed disabled:opacity-60 {variants[variant]} {sizes[size]} {className}"
    {...rest}
>
    {@render children()}
</button>
