<script lang="ts">
    import type { Filters } from '@/lib/filters';
    import type { Summary } from '@/lib/stats';

    type Shortcut = Partial<Pick<Filters, 'speaker' | 'assets' | 'flag'>>;
    type Tone = 'neutral' | 'pending' | 'negative';

    interface Cell {
        label: string;
        value: string;
        tone: Tone;
        progress?: number;
        hint?: string;
        /** What a click filters to, and how many rows that would show. */
        shortcut: Shortcut;
        count: number;
        caption?: string;
    }

    let { summary, filters, onShortcut }: { summary: Summary; filters: Filters; onShortcut: (shortcut: Shortcut) => void } = $props();

    function percent(part: number, total: number): number {
        return total === 0 ? 0 : Math.round((part / total) * 100);
    }

    const speakersPending = $derived(summary.speakers - summary.confirmed - summary.notAttending);
    const assetsPending = $derived(summary.assetsTracked - summary.assetsReady);

    const cells: Cell[] = $derived([
        {
            label: 'Speakers confirmed',
            value: `${summary.confirmed}/${summary.speakers}`,
            progress: percent(summary.confirmed, summary.speakers),
            tone: 'neutral',
            shortcut: { speaker: 'pending' },
            count: speakersPending,
            caption: speakersPending ? `${speakersPending} pending` : 'All answered',
        },
        {
            label: 'Assets ready',
            value: `${summary.assetsReady}/${summary.assetsTracked}`,
            progress: percent(summary.assetsReady, summary.assetsTracked),
            tone: 'neutral',
            shortcut: { assets: 'pending' },
            count: assetsPending,
            caption: assetsPending ? `${assetsPending} pending` : 'All received',
        },
        {
            label: 'Not attending',
            value: String(summary.notAttending),
            tone: summary.notAttending ? 'negative' : 'neutral',
            shortcut: { speaker: 'not_attending' },
            count: summary.notAttending,
        },
        {
            label: 'At risk',
            value: String(summary.atRisk),
            hint: 'Absent, no assets',
            tone: summary.atRisk ? 'negative' : 'neutral',
            shortcut: { flag: 'at_risk' },
            count: summary.atRisk,
        },
        {
            label: 'Topics TBD',
            value: String(summary.topicsTbd),
            tone: summary.topicsTbd ? 'pending' : 'neutral',
            shortcut: { flag: 'topic_tbd' },
            count: summary.topicsTbd,
        },
        {
            label: 'Missing moderator',
            value: String(summary.sessionsMissingModerator),
            hint: summary.sessionsMissingModerator === 1 ? 'session' : 'sessions',
            tone: summary.sessionsMissingModerator ? 'pending' : 'neutral',
            shortcut: { flag: 'missing_moderator' },
            count: summary.sessionsMissingModerator,
        },
    ]);

    function isActive(shortcut: Shortcut): boolean {
        return Object.entries(shortcut).every(([key, value]) => filters[key as keyof Shortcut] === value);
    }

    const valueTone: Record<Tone, string> = {
        neutral: 'text-ink-900',
        pending: 'text-amber-700',
        negative: 'text-rose-700',
    };
</script>

<section aria-label="Preparation summary" class="overflow-hidden rounded-xl border border-line bg-line shadow-[0_8px_24px_-12px_rgba(11,20,38,0.18)]">
    <ul class="grid grid-cols-2 gap-px sm:grid-cols-3 lg:grid-cols-6">
        {#each cells as cell (cell.label)}
            {@const active = isActive(cell.shortcut)}
            <li class="flex">
                <button
                    type="button"
                    onclick={() => onShortcut(cell.shortcut)}
                    disabled={cell.count === 0 && !active}
                    aria-pressed={active}
                    title={active ? 'Click to clear this filter' : 'Click to show only these'}
                    class="relative flex w-full flex-col px-5 py-4 text-left transition-colors focus-visible:z-10 disabled:cursor-default {active
                        ? 'bg-ink-50'
                        : 'bg-white enabled:hover:bg-ink-50/60'}"
                >
                    {#if active}
                        <span class="absolute inset-x-0 top-0 h-0.5 bg-ink-900" aria-hidden="true"></span>
                    {/if}
                    <span class="text-xs font-medium text-ink-500">{cell.label}</span>
                    <span class="mt-1.5 flex items-baseline gap-1.5">
                        <span class="text-2xl font-semibold tracking-tight tabular-nums {valueTone[cell.tone]}">{cell.value}</span>
                        {#if cell.hint}
                            <span class="text-xs text-ink-400">{cell.hint}</span>
                        {/if}
                    </span>
                    {#if cell.progress !== undefined}
                        <span class="mt-3 block h-1 overflow-hidden rounded-full bg-ink-100" aria-hidden="true">
                            <span class="block h-full rounded-full bg-emerald-500 transition-[width] duration-300" style:width="{cell.progress}%"></span>
                        </span>
                    {/if}
                    {#if cell.caption}
                        <span class="mt-2 text-xs {cell.count ? 'font-medium text-amber-700' : 'text-ink-400'}">{cell.caption}</span>
                    {/if}
                </button>
            </li>
        {/each}
    </ul>
</section>
