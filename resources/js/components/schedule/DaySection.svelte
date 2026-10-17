<script lang="ts">
    import { formatDay, formatUtcOffset } from '@/lib/format';
    import type { Conference, Day } from '@/types/models';
    import SessionBlock from './SessionBlock.svelte';

    let { day, number, cities }: { day: Day; number: number; cities: Conference['cities'] } = $props();
</script>

<section aria-labelledby="day-{day.id}-heading">
    <div class="sticky top-0 z-10 -mx-4 border-b border-line bg-canvas/90 px-4 py-3 backdrop-blur-md sm:-mx-6 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
            <div class="min-w-0">
                <p class="label-caps text-ink-500">Day {number} · {day.title}</p>
                <h2 id="day-{day.id}-heading" class="mt-0.5 font-serif text-2xl font-semibold tracking-tight text-ink-900">
                    {formatDay(day.date)}
                </h2>
            </div>
            {#if day.time}
                <div class="grid grid-cols-[auto_auto_auto] items-baseline gap-x-2.5 text-sm tabular-nums">
                    <span class="font-semibold text-ink-900">{day.time.team_start}–{day.time.team_end}</span>
                    <span class="text-xs font-medium text-ink-600">{cities.team}</span>
                    <span class="text-xs text-ink-400">{formatUtcOffset(day.utc_offsets.team)}</span>
                    <span class="text-ink-500">{day.time.start}–{day.time.end}</span>
                    <span class="text-xs text-ink-500">{cities.conference}</span>
                    <span class="text-xs text-ink-400">{formatUtcOffset(day.utc_offsets.conference)}</span>
                </div>
            {/if}
        </div>
    </div>

    <div class="mt-5 space-y-5">
        {#each day.sessions as session (session.id)}
            <SessionBlock {session} {cities} />
        {/each}
    </div>
</section>
