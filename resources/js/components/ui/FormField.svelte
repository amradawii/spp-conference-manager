<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        label,
        for: id,
        error,
        hint,
        optional = false,
        children,
    }: { label: string; for: string; error?: string; hint?: string; optional?: boolean; children: Snippet } = $props();
</script>

<div>
    <label for={id} class="flex items-baseline justify-between text-sm font-medium text-ink-800">
        {label}
        {#if optional}
            <span class="text-xs font-normal text-ink-400">Optional</span>
        {/if}
    </label>
    <div class="mt-1.5">
        {@render children()}
    </div>
    {#if error}
        <p class="mt-1.5 text-xs font-medium text-rose-700">{error}</p>
    {:else if hint}
        <p class="mt-1.5 text-xs text-ink-500">{hint}</p>
    {/if}
</div>
