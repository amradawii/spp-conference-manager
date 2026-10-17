<script lang="ts">
    import type { Summary } from '@/lib/stats';

    let { summary }: { summary: Summary } = $props();

    function percent(part: number, total: number): number {
        return total === 0 ? 0 : Math.round((part / total) * 100);
    }

    const cells = $derived([
        {
            label: 'Speakers confirmed',
            value: `${summary.confirmed}/${summary.speakers}`,
            progress: percent(summary.confirmed, summary.speakers),
            tone: 'neutral',
        },
        {
            label: 'Assets ready',
            value: `${summary.assetsReady}/${summary.assetsTracked}`,
            progress: percent(summary.assetsReady, summary.assetsTracked),
            tone: 'neutral',
        },
        { label: 'Not attending', value: String(summary.notAttending), tone: summary.notAttending ? 'negative' : 'neutral' },
        { label: 'At risk', value: String(summary.atRisk), hint: 'Absent, no assets', tone: summary.atRisk ? 'negative' : 'neutral' },
        { label: 'Topics TBD', value: String(summary.topicsTbd), tone: summary.topicsTbd ? 'pending' : 'neutral' },
        {
            label: 'Missing moderator',
            value: String(summary.sessionsMissingModerator),
            hint: summary.sessionsMissingModerator === 1 ? 'session' : 'sessions',
            tone: summary.sessionsMissingModerator ? 'pending' : 'neutral',
        },
    ] as { label: string; value: string; progress?: number; hint?: string; tone: 'neutral' | 'pending' | 'negative' }[]);

    const valueTone = {
        neutral: 'text-ink-900',
        pending: 'text-amber-700',
        negative: 'text-rose-700',
    };
</script>

<section aria-label="Preparation summary" class="overflow-hidden rounded-xl border border-line bg-line shadow-[0_8px_24px_-12px_rgba(11,20,38,0.18)]">
    <dl class="grid grid-cols-2 gap-px sm:grid-cols-3 lg:grid-cols-6">
        {#each cells as cell (cell.label)}
            <div class="bg-white px-5 py-4">
                <dt class="text-xs font-medium text-ink-500">{cell.label}</dt>
                <dd class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-semibold tracking-tight tabular-nums {valueTone[cell.tone]}">{cell.value}</span>
                    {#if cell.hint}
                        <span class="text-xs text-ink-400">{cell.hint}</span>
                    {/if}
                </dd>
                {#if cell.progress !== undefined}
                    <div class="mt-3 h-1 overflow-hidden rounded-full bg-ink-100" aria-hidden="true">
                        <div class="h-full rounded-full bg-emerald-500 transition-[width] duration-300" style:width="{cell.progress}%"></div>
                    </div>
                {/if}
            </div>
        {/each}
    </dl>
</section>
