<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title,
        subtitle,
        kicker = 'Team schedule board',
        logo,
        actions,
    }: { title: string; subtitle?: string; kicker?: string; logo?: { src: string; alt: string }; actions?: Snippet } = $props();
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

        <div class="flex flex-col-reverse items-start gap-5 pt-6 pb-16 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:pt-10 sm:pb-20">
            <div class="min-w-0">
                <p class="label-caps text-brand-300">{kicker}</p>
                <h1 class="mt-2 max-w-4xl font-serif text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{title}</h1>
                {#if subtitle}
                    <p class="mt-3 text-sm text-ink-300">{subtitle}</p>
                {/if}
            </div>
            {#if logo}
                <img src={logo.src} alt={logo.alt} class="size-16 shrink-0 rounded-full bg-white p-1 shadow-lg ring-1 ring-white/20 sm:size-32 sm:p-1.5" />
            {/if}
        </div>
    </div>
    <div class="h-0.5 bg-linear-to-r from-brand-teal via-brand-400 to-transparent" aria-hidden="true"></div>
</header>
