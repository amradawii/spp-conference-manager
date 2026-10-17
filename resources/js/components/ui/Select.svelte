<script lang="ts">
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import type { Snippet } from 'svelte';
    import type { HTMLSelectAttributes } from 'svelte/elements';

    let {
        value = $bindable(),
        class: className = '',
        children,
        ...rest
    }: Omit<HTMLSelectAttributes, 'value'> & { value: unknown; children: Snippet } = $props();
</script>

<div class="relative {className}">
    <select
        bind:value
        class="h-10 w-full cursor-pointer appearance-none rounded-lg border-0 bg-white py-0 pr-9 pl-3 text-sm text-ink-900 ring-1 ring-line-strong ring-inset hover:ring-ink-300 focus:ring-2 focus:ring-ink-700 focus:outline-none"
        {...rest}
    >
        {@render children()}
    </select>
    <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink-400" aria-hidden="true" />
</div>
