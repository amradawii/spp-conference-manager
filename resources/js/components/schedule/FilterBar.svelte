<script lang="ts">
    import Select from '@/components/ui/Select.svelte';
    import { flagLabels, sessionLabel, type Filters } from '@/lib/filters';
    import { formatShortDay, pluralize } from '@/lib/format';
    import { assetsStatusOptions, speakerStatusOptions } from '@/lib/status';
    import type { Day } from '@/types/models';
    import Search from '@lucide/svelte/icons/search';
    import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
    import X from '@lucide/svelte/icons/x';

    let {
        days,
        filters = $bindable(),
        shown,
        total,
        filtering,
        onClear,
    }: { days: Day[]; filters: Filters; shown: number; total: number; filtering: boolean; onClear: () => void } = $props();

    let searchInput: HTMLInputElement | undefined = $state();
    let moreOpen = $state(false);

    const sessionGroups = $derived(
        days
            .map((day, index) => ({ day, number: index + 1 }))
            .filter(({ number }) => filters.day === null || filters.day === number),
    );
    const extraCount = $derived([filters.session, filters.speaker, filters.assets].filter((value) => value !== null).length);

    function selectDay(number: number | null): void {
        filters.day = number;
        if (number !== null && filters.session !== null && !days[number - 1].sessions.some((s) => s.id === filters.session)) {
            filters.session = null;
        }
    }

    function onWindowKeydown(event: KeyboardEvent): void {
        if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;

        const target = event.target as HTMLElement | null;
        if (target?.closest('input, textarea, select, [contenteditable], [role="dialog"]')) return;

        event.preventDefault();
        searchInput?.focus();
    }

    function onSearchKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape' && filters.q) {
            event.preventDefault();
            filters.q = '';
        }
    }
</script>

<svelte:window onkeydown={onWindowKeydown} />

{#snippet sessionSelect()}
    <Select bind:value={filters.session} aria-label="Session" class="min-w-0">
        <option value={null}>All sessions</option>
        {#each sessionGroups as { day, number } (day.id)}
            {#if filters.day === null}
                <optgroup label="Day {number} · {formatShortDay(day.date)}">
                    {#each day.sessions as session (session.id)}
                        <option value={session.id}>{sessionLabel(session)}</option>
                    {/each}
                </optgroup>
            {:else}
                {#each day.sessions as session (session.id)}
                    <option value={session.id}>{sessionLabel(session)}</option>
                {/each}
            {/if}
        {/each}
    </Select>
{/snippet}

{#snippet statusSelects()}
    <Select bind:value={filters.speaker} aria-label="Speaker status" class="min-w-0 sm:w-48">
        <option value={null}>Any speaker status</option>
        {#each Object.entries(speakerStatusOptions) as [value, option] (value)}
            <option {value}>Speaker: {option.label}</option>
        {/each}
    </Select>
    <Select bind:value={filters.assets} aria-label="Assets status" class="min-w-0 sm:w-44">
        <option value={null}>Any assets status</option>
        <option value="pending">Assets: {assetsStatusOptions.pending.label}</option>
        <option value="ready">Assets: {assetsStatusOptions.ready.label}</option>
    </Select>
{/snippet}

<section aria-label="Filters" class="rounded-xl border border-line bg-white p-3 shadow-[0_1px_2px_rgba(11,20,38,0.04)] sm:p-4 print:hidden">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex shrink-0 rounded-lg bg-ink-50 p-1 ring-1 ring-line ring-inset" role="group" aria-label="Day">
            <button
                type="button"
                onclick={() => selectDay(null)}
                aria-pressed={filters.day === null}
                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors lg:flex-none {filters.day === null
                    ? 'bg-white text-ink-900 shadow-sm ring-1 ring-line'
                    : 'text-ink-500 hover:text-ink-900'}"
            >
                All days
            </button>
            {#each days as day, index (day.id)}
                <button
                    type="button"
                    onclick={() => selectDay(index + 1)}
                    aria-pressed={filters.day === index + 1}
                    class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors lg:flex-none {filters.day === index + 1
                        ? 'bg-white text-ink-900 shadow-sm ring-1 ring-line'
                        : 'text-ink-500 hover:text-ink-900'}"
                >
                    Day {index + 1}<span class="hidden font-normal text-ink-400 sm:inline"> · {formatShortDay(day.date)}</span>
                </button>
            {/each}
        </div>

        <div class="hidden min-w-0 flex-1 sm:block lg:max-w-xs">
            {@render sessionSelect()}
        </div>

        <div class="relative min-w-0 flex-1">
            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-400" aria-hidden="true" />
            <input
                bind:this={searchInput}
                bind:value={filters.q}
                onkeydown={onSearchKeydown}
                type="search"
                maxlength="100"
                placeholder="Search keynotes, topics, moderators…"
                aria-label="Search"
                class="h-10 w-full rounded-lg border-0 bg-white pr-10 pl-9 text-sm text-ink-900 ring-1 ring-line-strong ring-inset placeholder:text-ink-400 hover:ring-ink-300 focus:ring-2 focus:ring-ink-700 focus:outline-none [&::-webkit-search-cancel-button]:hidden"
            />
            {#if filters.q}
                <button
                    type="button"
                    onclick={() => {
                        filters.q = '';
                        searchInput?.focus();
                    }}
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1 text-ink-400 hover:bg-ink-50 hover:text-ink-700"
                    aria-label="Clear search"
                >
                    <X class="size-4" />
                </button>
            {:else}
                <kbd
                    class="pointer-events-none absolute top-1/2 right-3 hidden -translate-y-1/2 rounded border border-line px-1.5 font-sans text-[11px] text-ink-400 sm:block"
                    aria-hidden="true">/</kbd
                >
            {/if}
        </div>
    </div>

    <div class="mt-3 hidden flex-wrap items-center gap-3 sm:flex">
        {@render statusSelects()}
    </div>

    <button
        type="button"
        onclick={() => (moreOpen = !moreOpen)}
        aria-expanded={moreOpen}
        class="mt-3 flex w-full items-center justify-between rounded-lg px-1 py-1 text-sm font-medium text-ink-700 sm:hidden"
    >
        <span class="inline-flex items-center gap-2">
            <SlidersHorizontal class="size-4 text-ink-400" aria-hidden="true" />
            More filters
            {#if extraCount}
                <span class="rounded-full bg-ink-900 px-1.5 text-xs text-white">{extraCount}</span>
            {/if}
        </span>
        <span class="text-xs text-ink-400">{moreOpen ? 'Hide' : 'Show'}</span>
    </button>
    {#if moreOpen}
        <div class="mt-2 grid gap-2 sm:hidden">
            {@render sessionSelect()}
            {@render statusSelects()}
        </div>
    {/if}

    {#if filtering}
        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-line pt-3 text-sm">
            {#if filters.flag}
                <span class="inline-flex items-center gap-1 rounded-full bg-ink-900 py-1 pr-1 pl-3 text-xs font-medium text-white">
                    {flagLabels[filters.flag]}
                    <button
                        type="button"
                        onclick={() => (filters.flag = null)}
                        class="rounded-full p-0.5 text-ink-300 hover:bg-white/10 hover:text-white"
                        aria-label="Remove {flagLabels[filters.flag]} filter"
                    >
                        <X class="size-3.5" />
                    </button>
                </span>
            {/if}
            <p class="text-ink-500" aria-live="polite">
                Showing <span class="font-semibold text-ink-900 tabular-nums">{shown}</span> of {pluralize(total, 'speaker')}
            </p>
            <button type="button" onclick={onClear} class="ml-auto text-sm font-medium text-ink-700 underline decoration-ink-200 underline-offset-4 hover:text-ink-900">
                Clear filters
            </button>
        </div>
    {/if}
</section>
