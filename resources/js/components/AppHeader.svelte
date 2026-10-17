<script lang="ts">
    import type { Snippet } from 'svelte';

    let { title, subtitle, actions }: { title: string; subtitle?: string; actions?: Snippet } = $props();
</script>

<header class="bg-ink-950 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex items-center justify-between gap-4 border-b border-white/10 py-4">
            <div class="flex items-center gap-4">
                <img src="/SPP_logo.png" alt="Science Park Publisher" class="h-9 w-auto sm:h-10" />
                <span class="hidden h-6 w-px bg-white/15 sm:block" aria-hidden="true"></span>
                <span class="label-caps hidden text-ink-300 sm:block">Conference Manager</span>
            </div>
            {@render actions?.()}
        </div>

        <div class="pt-8 pb-16 sm:pt-10 sm:pb-20">
            <p class="label-caps text-brand-300">Team schedule board</p>
            <h1 class="mt-2 font-serif text-3xl font-semibold tracking-tight sm:text-4xl">{title}</h1>
            {#if subtitle}
                <p class="mt-3 text-sm text-ink-300">{subtitle}</p>
            {/if}
        </div>
    </div>
    <div class="h-0.5 bg-linear-to-r from-brand-teal via-brand-400 to-transparent" aria-hidden="true"></div>
</header>
