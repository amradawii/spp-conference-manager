<script lang="ts">
    import Highlight from '@/components/ui/Highlight.svelte';
    import { getBoard } from '@/lib/board';
    import { formatRelative } from '@/lib/format';
    import { missingModerators } from '@/lib/stats';
    import type { ConferenceSession } from '@/types/models';
    import AgendaItemRow from './AgendaItemRow.svelte';
    import RecordActions from './RecordActions.svelte';
    import TimeRange from './TimeRange.svelte';

    let { session }: { session: ConferenceSession } = $props();

    const board = getBoard();
    const isSession = $derived(session.kind === 'session');
    const missing = $derived(missingModerators(session));
    const moderators = $derived([
        { label: 'SPP moderator', tone: 'text-spp', name: session.spp_moderator, missing: missing.includes('spp') },
        { label: 'Journal moderator', tone: 'text-journal', name: session.journal_moderator, missing: missing.includes('journal') },
    ]);
</script>

<article
    id="session-{session.id}"
    class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(11,20,38,0.04)] print:shadow-none"
>
    <header
        class="grid gap-3 px-5 py-5 sm:px-6 md:grid-cols-[12.5rem_minmax(0,1fr)_15rem] md:gap-6 print:break-inside-avoid print:grid-cols-[12.5rem_minmax(0,1fr)_12rem] print:gap-6 print:break-after-avoid {isSession
            ? 'bg-journal/[0.035]'
            : 'bg-ink-50/60'}"
    >
        <div class="md:pt-0.5">
            <TimeRange time={session.time} cities={board.cities} />
        </div>

        <div class="min-w-0">
            <p class="label-caps {isSession ? 'text-journal' : 'text-brand-700'}">{isSession ? `Session ${session.number}` : 'Plenary'}</p>
            {#if session.journal}
                <h3 class="mt-1 font-serif text-xl leading-snug font-semibold text-journal">
                    <Highlight text={session.journal} query={board.query} />
                </h3>
                {#if session.title}
                    <p class="mt-1 text-[15px] leading-snug text-ink-600"><Highlight text={session.title} query={board.query} /></p>
                {/if}
            {:else if session.title}
                <h3 class="mt-1 font-serif text-xl leading-snug font-semibold text-ink-900">
                    <Highlight text={session.title} query={board.query} />
                </h3>
            {/if}

            {#if isSession}
                <dl class="mt-4 flex flex-wrap gap-x-10 gap-y-3">
                    {#each moderators as moderator (moderator.label)}
                        <div>
                            <dt class="label-caps {moderator.tone}">{moderator.label}</dt>
                            <dd class="mt-0.5 text-sm">
                                {#if moderator.missing}
                                    <span class="inline-flex items-center gap-1.5 font-medium text-amber-700">
                                        <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                                        Not assigned
                                    </span>
                                    <button
                                        type="button"
                                        onclick={() => board.editSession(session)}
                                        class="ml-1.5 text-xs font-medium print:hidden text-ink-600 underline decoration-ink-200 underline-offset-4 hover:text-ink-900"
                                    >
                                        Assign
                                    </button>
                                {:else}
                                    <span class="font-medium text-ink-900"><Highlight text={moderator.name ?? ''} query={board.query} /></span>
                                {/if}
                            </dd>
                        </div>
                    {/each}
                </dl>
            {/if}
        </div>

        <div class="flex flex-col gap-1.5 md:pt-0.5">
            <RecordActions
                notesCount={session.notes.length}
                onNotes={() => board.openNotes({ type: 'session', id: session.id })}
                onEdit={() => board.editSession(session)}
            />
            {#if session.updated_by}
                <p class="text-xs text-ink-400 print:hidden" title={new Date(session.updated_at).toLocaleString()}>
                    {session.updated_by} · {formatRelative(session.updated_at)}
                </p>
            {/if}
        </div>
    </header>

    {#if session.items.length}
        <ol class="divide-y divide-line border-t border-line">
            {#each session.items as item (item.id)}
                <AgendaItemRow {item} />
            {/each}
        </ol>
    {/if}

    {#if !board.isFiltering}
        <div class="border-t border-line px-5 py-2 sm:px-6 print:hidden">
            <button
                type="button"
                onclick={() => board.addItem(session)}
                class="-ml-1.5 rounded-md px-1.5 py-1 text-xs font-medium text-ink-500 transition-colors hover:bg-ink-50 hover:text-ink-800"
            >
                + Add item to this {isSession ? 'session' : 'block'}
            </button>
        </div>
    {/if}
</article>
