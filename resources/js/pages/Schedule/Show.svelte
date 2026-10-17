<script lang="ts">
    import AppHeader from '@/components/AppHeader.svelte';
    import DaySection from '@/components/schedule/DaySection.svelte';
    import SummaryBar from '@/components/schedule/SummaryBar.svelte';
    import TimezoneNotice from '@/components/schedule/TimezoneNotice.svelte';
    import { formatDateSpan } from '@/lib/format';
    import { summarize } from '@/lib/stats';
    import type { Conference } from '@/types/models';

    let { conference }: { conference: Conference } = $props();

    const summary = $derived(summarize(conference.days));
    const subtitle = $derived(`${formatDateSpan(conference.days.map((day) => day.date))} · Online via Zoom`);
</script>

<svelte:head>
    <title>{conference.name} · SPP Conference Manager</title>
</svelte:head>

<AppHeader title={conference.name} {subtitle} />

<main class="mx-auto max-w-6xl px-4 pb-24 sm:px-6">
    <div class="relative -mt-10 space-y-4">
        <SummaryBar {summary} />
        <TimezoneNotice days={conference.days} cities={conference.cities} />
    </div>

    <div class="mt-12 space-y-14">
        {#each conference.days as day, index (day.id)}
            <DaySection {day} number={index + 1} cities={conference.cities} />
        {/each}
    </div>
</main>

<footer class="border-t border-line py-8 text-center text-xs text-ink-400">
    Science Park Publisher · Internal team schedule
</footer>
