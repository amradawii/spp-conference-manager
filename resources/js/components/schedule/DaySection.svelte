<script lang="ts">
    import { getBoard } from '@/lib/board';
    import { formatClockRange, formatDay, formatUtcOffset } from '@/lib/format';
    import type { Day } from '@/types/models';
    import SessionBlock from './SessionBlock.svelte';

    let { day, number }: { day: Day; number: number } = $props();

    const board = getBoard();
</script>

<section aria-labelledby="day-{day.id}-heading" class="print:not-first:break-before-page">
    <div class="sticky top-0 z-10 -mx-4 border-b-2 border-line-strong bg-canvas/90 px-4 py-3 backdrop-blur-md sm:-mx-6 sm:px-6 print:static print:bg-white">
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
            <div class="min-w-0">
                <p class="kicker text-brand-700">Day {number} · {day.title}</p>
                <h2 id="day-{day.id}-heading" class="mt-0.5 font-serif text-2xl font-semibold tracking-tight text-ink-900">
                    {formatDay(day.date)}
                </h2>
            </div>
            {#if day.time}
                <div class="grid grid-cols-[auto_auto_auto] items-baseline gap-x-2.5 text-sm tabular-nums">
                    <span class="font-semibold text-ink-900">{formatClockRange(day.time.team_start, day.time.team_end)}</span>
                    <span class="text-xs font-medium text-ink-600">{board.cities.team}</span>
                    <span class="text-xs text-ink-400">{formatUtcOffset(day.utc_offsets.team)}</span>
                    <span class="text-ink-500">{formatClockRange(day.time.start, day.time.end)}</span>
                    <span class="text-xs text-ink-500">{board.cities.conference}</span>
                    <span class="text-xs text-ink-400">{formatUtcOffset(day.utc_offsets.conference)}</span>
                </div>
            {/if}
        </div>
    </div>

    {#if day.zoom_url || day.note}
        <div class="mt-5 rounded-lg border border-line bg-white px-5 py-3.5 text-sm sm:px-6">
            {#if day.zoom_url}
                <p class="kicker text-brand-700">Zoom link for Day {number}</p>
                <a
                    href={day.zoom_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-0.5 inline-block font-medium break-all text-ink-900 underline decoration-ink-200 underline-offset-4 transition-colors hover:decoration-ink-500"
                >
                    {day.zoom_url}
                </a>
            {/if}
            {#if day.note}
                <p class="leading-relaxed text-ink-600 {day.zoom_url ? 'mt-2' : ''}">{day.note}</p>
            {/if}
        </div>
    {/if}

    <div class="mt-5 space-y-5">
        {#each day.sessions as session (session.id)}
            <SessionBlock {session} />
        {/each}

        {#if !board.isFiltering && !board.readonly}
            <button
                type="button"
                onclick={() => board.addSession(day)}
                class="flex w-full items-center justify-center rounded-xl border border-dashed print:hidden border-line-strong py-4 text-sm font-medium text-ink-500 transition-colors hover:border-ink-300 hover:bg-white hover:text-ink-800"
            >
                + Add session to Day {number}
            </button>
        {/if}
    </div>
</section>
