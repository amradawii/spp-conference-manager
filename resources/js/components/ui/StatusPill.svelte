<script lang="ts">
    import type { Tone } from '@/lib/status';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';

    let { tone, label, caret = false }: { tone: Tone; label: string; caret?: boolean } = $props();

    const styles: Record<Tone, { pill: string; dot: string }> = {
        pending: { pill: 'bg-amber-50 text-amber-800 ring-amber-600/20', dot: 'bg-amber-500' },
        positive: { pill: 'bg-emerald-50 text-emerald-800 ring-emerald-600/20', dot: 'bg-emerald-500' },
        negative: { pill: 'bg-rose-50 text-rose-800 ring-rose-600/20', dot: 'bg-rose-500' },
        muted: { pill: 'bg-ink-50 text-ink-600 ring-ink-500/15', dot: 'bg-ink-300' },
    };
</script>

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs leading-none font-medium whitespace-nowrap ring-1 ring-inset {styles[tone].pill}">
    <span class="size-1.5 rounded-full {styles[tone].dot}" aria-hidden="true"></span>
    {label}
    {#if caret}
        <ChevronDown class="-mr-0.5 size-3 opacity-60" aria-hidden="true" />
    {/if}
</span>
