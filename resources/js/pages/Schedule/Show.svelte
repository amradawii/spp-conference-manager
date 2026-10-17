<script lang="ts">
    import AppHeader from '@/components/AppHeader.svelte';
    import DaySection from '@/components/schedule/DaySection.svelte';
    import EditorNameModal from '@/components/schedule/EditorNameModal.svelte';
    import FilterBar from '@/components/schedule/FilterBar.svelte';
    import ItemDrawer from '@/components/schedule/ItemDrawer.svelte';
    import NotesModal from '@/components/schedule/NotesModal.svelte';
    import SessionDrawer from '@/components/schedule/SessionDrawer.svelte';
    import SummaryBar from '@/components/schedule/SummaryBar.svelte';
    import TimezoneNotice from '@/components/schedule/TimezoneNotice.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Toaster from '@/components/ui/Toaster.svelte';
    import { locateItem, locateSession, setBoard, type NotesTarget } from '@/lib/board';
    import { editorPrompt } from '@/lib/editor.svelte';
    import { applyFilters, countSpeakers, emptyFilters, isFiltering, parseFilters, scopeDays, sessionLabel, toQueryString, type Filters } from '@/lib/filters';
    import { formatDateSpan, formatDay } from '@/lib/format';
    import { createRoutes } from '@/lib/routes';
    import { summarize } from '@/lib/stats';
    import { toasts } from '@/lib/toasts.svelte';
    import type { Conference } from '@/types/models';
    import { router } from '@inertiajs/svelte';

    type Editing = { kind: 'session'; dayId: number; sessionId?: number } | { kind: 'item'; sessionId: number; itemId?: number };

    let { conference }: { conference: Conference } = $props();

    // svelte-ignore state_referenced_locally
    const routes = createRoutes(conference.url);
    // svelte-ignore state_referenced_locally
    let filters = $state(parseFilters(window.location.search, conference.days));
    let notesTarget = $state<NotesTarget | null>(null);
    let editing = $state<Editing | null>(null);
    let loadedAt = $state(new Date());
    let refreshing = $state(false);

    const visibleDays = $derived(applyFilters(conference.days, filters));
    const filtering = $derived(isFiltering(filters));
    const summary = $derived(summarize(scopeDays(conference.days, filters)));
    const dayNumbers = $derived(new Map(conference.days.map((day, index) => [day.id, index + 1])));
    const nextSessionNumber = $derived(Math.max(0, ...conference.days.flatMap((day) => day.sessions.map((s) => s.number ?? 0))) + 1);
    const subtitle = $derived(`${formatDateSpan(conference.days.map((day) => day.date))} · Online via Zoom`);
    const loadedLabel = $derived(loadedAt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));

    const notesRecord = $derived.by(() => {
        if (!notesTarget) return null;

        if (notesTarget.type === 'session') {
            const found = locateSession(conference.days, notesTarget.id);
            return found && { title: sessionLabel(found.session), context: `Day ${found.dayNumber} · ${formatDay(found.day.date)}`, notes: found.session.notes };
        }

        const found = locateItem(conference.days, notesTarget.id);
        return (
            found && {
                title: found.item.speaker_name ?? found.item.label ?? 'Programme item',
                context: `${sessionLabel(found.session)} · Day ${found.dayNumber}`,
                notes: found.item.notes,
            }
        );
    });

    setBoard({
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
        openNotes: (target) => (notesTarget = target),
        addSession: (day) => (editing = { kind: 'session', dayId: day.id }),
        editSession: (session) => {
            const found = locateSession(conference.days, session.id);
            if (found) editing = { kind: 'session', dayId: found.day.id, sessionId: session.id };
        },
        addItem: (session) => (editing = { kind: 'item', sessionId: session.id }),
        editItem: (item) => {
            const found = locateItem(conference.days, item.id);
            if (found) editing = { kind: 'item', sessionId: found.session.id, itemId: item.id };
        },
    });

    // Keep the address bar in sync, so a filtered view can be shared or bookmarked.
    $effect(() => {
        const url = conference.url + toQueryString(filters);
        const timer = setTimeout(() => {
            if (url !== window.location.pathname + window.location.search) {
                router.replace({ url, preserveState: true, preserveScroll: true });
            }
        }, 250);

        return () => clearTimeout(timer);
    });

    $effect(() => {
        const unsubscribe = [
            router.on('flash', (event) => {
                if (event.detail.flash.toast) toasts.push(event.detail.flash.toast);
            }),
            router.on('success', () => (loadedAt = new Date())),
            router.on('httpException', (event) => {
                const messages: Record<number, string> = {
                    404: 'That record no longer exists. Someone may have deleted it, so the schedule was refreshed.',
                    419: 'This page was open for too long. Refresh the page, then try again.',
                    429: 'Too many changes in a short time. Wait a moment and try again.',
                };
                const status = event.detail.response.status;
                if (!messages[status] && import.meta.env.DEV) return;

                toasts.push(messages[status] ?? 'Something went wrong on the server. Please try again.', 'error');
                if (status === 404) router.reload();
                return false;
            }),
            router.on('networkError', () => {
                toasts.push('Could not reach the server. Check your connection and try again.', 'error');
                return false;
            }),
        ];

        return () => unsubscribe.forEach((off) => off());
    });

    function applyShortcut(shortcut: Partial<Pick<Filters, 'speaker' | 'assets' | 'flag'>>): void {
        const active = Object.entries(shortcut).every(([key, value]) => filters[key as keyof typeof shortcut] === value);
        for (const key of Object.keys(shortcut) as (keyof typeof shortcut)[]) {
            (filters[key] as unknown) = active ? null : shortcut[key];
        }
    }

    function clearFilters(): void {
        filters = emptyFilters();
    }

    function refresh(): void {
        router.reload({
            onStart: () => (refreshing = true),
            onFinish: () => (refreshing = false),
            onSuccess: () => toasts.push('Schedule is up to date'),
        });
    }
</script>

<svelte:head>
    <title>{conference.name} · SPP Conference Manager</title>
</svelte:head>

{#snippet headerActions()}
    <div class="flex items-center gap-3 text-xs text-ink-300 sm:gap-4 print:hidden">
        <span class="hidden md:inline">Loaded at {loadedLabel}</span>
        <button
            type="button"
            onclick={() => window.print()}
            class="hidden rounded-md px-2 py-1 font-medium text-ink-200 transition-colors hover:bg-white/10 hover:text-white md:inline-flex"
            title="Print or save as PDF (shows the current filters)"
        >
            Print
        </button>
        <button
            type="button"
            onclick={refresh}
            disabled={refreshing}
            class="rounded-md px-2 py-1 font-medium text-white ring-1 ring-white/20 transition-colors hover:bg-white/10 disabled:opacity-60"
        >
            {refreshing ? 'Refreshing…' : 'Refresh'}
        </button>
        <span class="h-4 w-px bg-white/15" aria-hidden="true"></span>
        {#if editorPrompt.name}
            <span class="hidden sm:inline">Editing as</span>
            <button
                type="button"
                onclick={() => editorPrompt.change()}
                class="max-w-32 truncate font-medium text-white underline decoration-white/30 underline-offset-4 hover:decoration-white"
                title="Change your name"
            >
                {editorPrompt.name}
            </button>
        {:else}
            <button type="button" onclick={() => editorPrompt.change()} class="font-medium text-white underline decoration-white/30 underline-offset-4 hover:decoration-white">
                Set your name
            </button>
        {/if}
    </div>
{/snippet}

<a
    href="#schedule"
    class="sr-only z-50 rounded-md bg-white px-3 py-2 text-sm font-medium text-ink-900 shadow-lg focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
>
    Skip to schedule
</a>

<AppHeader title={conference.name} {subtitle} actions={headerActions} />

<main class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 print:pb-0">
    <div class="relative -mt-10 space-y-4">
        <SummaryBar {summary} {filters} onShortcut={applyShortcut} />
        <TimezoneNotice days={conference.days} cities={conference.cities} />
        <FilterBar
            days={conference.days}
            bind:filters
            shown={countSpeakers(visibleDays)}
            total={countSpeakers(conference.days)}
            {filtering}
            onClear={clearFilters}
        />
    </div>

    {#if visibleDays.length}
        <div id="schedule" tabindex="-1" class="mt-10 space-y-14 focus:outline-none">            {#each visibleDays as day (day.id)}
                <DaySection {day} number={dayNumbers.get(day.id) ?? 0} />
            {/each}
        </div>
    {:else}
        <div id="schedule" class="mt-10 rounded-xl border border-dashed border-line-strong bg-white/60 px-6 py-16 text-center">
            <p class="font-serif text-xl font-semibold text-ink-900">Nothing matches these filters</p>
            <p class="mt-2 text-sm text-ink-500">Try a different spelling, or widen the day, session or status.</p>
            <Button class="mt-6" onclick={clearFilters}>Clear filters</Button>
        </div>
    {/if}
</main>

<footer class="border-t border-line py-8 text-center text-xs text-ink-500">
    Science Park Publisher · Internal team schedule
</footer>

{#if notesTarget && notesRecord}
    {#key notesTarget}
        <NotesModal target={notesTarget} {...notesRecord} onClose={() => (notesTarget = null)} />
    {/key}
{/if}

{#if editing?.kind === 'session'}
    {@const dayId = editing.dayId}
    {@const day = conference.days.find((d) => d.id === dayId)}
    {@const found = editing.sessionId ? locateSession(conference.days, editing.sessionId) : undefined}
    {#if day && (!editing.sessionId || found)}
        {#key editing}
            <SessionDrawer
                {day}
                dayNumber={dayNumbers.get(day.id) ?? 0}
                session={found?.session}
                nextNumber={nextSessionNumber}
                onClose={() => (editing = null)}
            />
        {/key}
    {/if}
{:else if editing?.kind === 'item'}
    {@const found = locateSession(conference.days, editing.sessionId)}
    {@const item = editing.itemId ? locateItem(conference.days, editing.itemId)?.item : undefined}
    {#if found && (!editing.itemId || item)}
        {#key editing}
            <ItemDrawer day={found.day} session={found.session} {item} onClose={() => (editing = null)} />
        {/key}
    {/if}
{/if}

<EditorNameModal />
<Toaster />
