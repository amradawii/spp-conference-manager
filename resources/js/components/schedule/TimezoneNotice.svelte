<script lang="ts">
    import { formatUtcOffset, pluralize, teamLeadHours } from '@/lib/format';
    import type { Conference } from '@/types/models';

    let { days, cities }: { days: Conference['days']; cities: Conference['cities'] } = $props();

    const leads = $derived(days.map((day, index) => ({ day, number: index + 1, hours: teamLeadHours(day) })));
    const clocksChange = $derived(new Set(leads.map((lead) => lead.hours)).size > 1);
</script>

<aside class="rounded-lg border border-line border-l-2 border-l-brand-500 bg-white px-5 py-4">
    <p class="label-caps text-ink-500">Time zones</p>
    <p class="mt-1.5 text-sm leading-relaxed text-ink-700">
        Every time is shown twice: <span class="font-semibold text-ink-900">{cities.team}</span> first, then
        {cities.conference} (the time printed in the official programme).
        {#if clocksChange}
            Clocks change during the conference, so the gap is not the same every day:
            {#each leads as lead, index (lead.day.id)}
                on <span class="font-medium text-ink-900">Day {lead.number} {cities.team} is {pluralize(lead.hours, 'hour')} ahead</span>
                <span class="text-ink-500">({cities.conference} {formatUtcOffset(lead.day.utc_offsets.conference)})</span>{index < leads.length - 1 ? '; ' : '.'}
            {/each}
        {/if}
    </p>
</aside>
