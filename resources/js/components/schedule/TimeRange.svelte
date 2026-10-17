<script lang="ts">
    import type { Conference, TimeRange } from '@/types/models';

    let { time, cities, emptyLabel = 'Time not set' }: { time: TimeRange | null; cities: Conference['cities']; emptyLabel?: string } = $props();
</script>

{#if time}
    <div class="grid grid-cols-[auto_auto] items-baseline justify-start gap-x-2.5 gap-y-0.5 tabular-nums">
        <span class="text-sm font-semibold text-ink-900">{time.team_start}–{time.team_end}</span>
        <span class="text-[11px] font-medium text-ink-500">{cities.team}</span>
        <span class="text-sm text-ink-500">{time.start}–{time.end}</span>
        <span class="text-[11px] text-ink-400">{cities.conference}</span>
    </div>
{:else}
    <span class="text-sm text-ink-400">{emptyLabel}</span>
{/if}
