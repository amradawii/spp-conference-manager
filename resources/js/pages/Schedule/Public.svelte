<script lang="ts">
    import AppHeader from '@/components/AppHeader.svelte';
    import DaySection from '@/components/schedule/DaySection.svelte';
    import FilterBar from '@/components/schedule/FilterBar.svelte';
    import TimezoneNotice from '@/components/schedule/TimezoneNotice.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { setBoard } from '@/lib/board';
    import { applyFilters, countSpeakers, emptyFilters, isFiltering, parseFilters, toQueryString, type Filters } from '@/lib/filters';
    import { formatDateSpan } from '@/lib/format';
    import { createRoutes } from '@/lib/routes';
    import { eventLogo } from '@/lib/ui';
    import type { Conference } from '@/types/models';
    import { router } from '@inertiajs/svelte';

    let { conference }: { conference: Conference } = $props();

    // The public payload has no statuses, so status filters from a pasted URL are dropped.
    // svelte-ignore state_referenced_locally
    let filters = $state<Filters>({ ...parseFilters(window.location.search, conference.days), speaker: null, assets: null, flag: null });

    const visibleDays = $derived(applyFilters(conference.days, filters));
    const filtering = $derived(isFiltering(filters));
    const dayNumbers = $derived(new Map(conference.days.map((day, index) => [day.id, index + 1])));
    const subtitle = $derived(`${formatDateSpan(conference.days.map((day) => day.date))} · Online via Zoom`);

    const noop = () => {};
    // svelte-ignore state_referenced_locally
    const routes = createRoutes(conference.url);

    setBoard({
        readonly: true,
        routes,
        get cities() {
            return conference.cities;
        },
        get query() {
            return filters.q;
        },
        get isFiltering() {
            return filtering;
        },
        openNotes: noop,
        addSession: noop,
        editSession: noop,
        addItem: noop,
        editItem: noop,
    });

    $effect(() => {
        const url = conference.url + toQueryString(filters);
        const timer = setTimeout(() => {
            if (url !== window.location.pathname + window.location.search) {
                router.replace({ url, preserveState: true, preserveScroll: true });
            }
        }, 250);

        return () => clearTimeout(timer);
    });

    function clearFilters(): void {
        filters = emptyFilters();
    }
</script>

<svelte:head>
    <title>{conference.name} · Programme</title>
</svelte:head>

{#snippet headerActions()}
    <a
        href={conference.pdf_url}
        download
        class="rounded-md px-2.5 py-1 text-xs font-medium text-white ring-1 ring-white/20 transition-colors hover:bg-white/10 print:hidden"
        title="Download the programme as a PDF"
    >
        Save PDF
    </a>
{/snippet}

<a
    href="#schedule"
    class="sr-only z-50 rounded-md bg-white px-3 py-2 text-sm font-medium text-ink-900 shadow-lg focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
>
    Skip to programme
</a>

<AppHeader title={conference.name} {subtitle} kicker="Conference programme" logo={eventLogo} actions={headerActions} />

<main class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 print:pb-0">
    <div class="relative -mt-10 space-y-4">
        <TimezoneNotice days={conference.days} cities={conference.cities} />
        <FilterBar
            days={conference.days}
            bind:filters
            shown={countSpeakers(visibleDays)}
            total={countSpeakers(conference.days)}
            {filtering}
            statusFilters={false}
            onClear={clearFilters}
        />
    </div>

    {#if visibleDays.length}
        <div id="schedule" tabindex="-1" class="mt-10 space-y-14 focus:outline-none">
            {#each visibleDays as day (day.id)}
                <DaySection {day} number={dayNumbers.get(day.id) ?? 0} />
            {/each}
        </div>
    {:else}
        <div id="schedule" class="mt-10 rounded-xl border border-dashed border-line-strong bg-white/60 px-6 py-16 text-center">
            <p class="font-serif text-xl font-semibold text-ink-900">Nothing matches these filters</p>
            <p class="mt-2 text-sm text-ink-500">Try a different spelling, or pick another day or session.</p>
            <Button class="mt-6" onclick={clearFilters}>Clear filters</Button>
        </div>
    {/if}
</main>

<footer class="border-t border-line py-8 text-center text-xs text-ink-500">Science Park Publisher · Conference programme</footer>
