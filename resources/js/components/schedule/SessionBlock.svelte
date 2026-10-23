<script lang="ts">
    import Highlight from '@/components/ui/Highlight.svelte';
    import { getBoard } from '@/lib/board';
    import { formatRelative, isSameTimeRange } from '@/lib/format';
    import { isMissingChair } from '@/lib/stats';
    import { rowColumns } from '@/lib/ui';
    import type { ConferenceSession } from '@/types/models';
    import AgendaItemRow from './AgendaItemRow.svelte';
    import RecordActions from './RecordActions.svelte';
    import TimeRange from './TimeRange.svelte';

    let { session }: { session: ConferenceSession } = $props();

    const board = getBoard();
    const isSession = $derived(session.kind === 'session');
    const heading = $derived(session.title ?? session.journal ?? '');
    const roles = $derived(
        [
            { label: 'Session Chair', name: session.chair, missing: isMissingChair(session), journal: false },
            { label: 'Session Co-Chair', name: session.co_chair, missing: false, journal: false },
            { label: 'Journal Title', name: session.title ? session.journal : null, missing: false, journal: true },
        ].filter((role) => role.missing || role.name?.trim()),
    );
</script>

<article
    id="session-{session.id}"
    class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(11,20,38,0.04)] print:overflow-visible print:rounded-none print:border-x-0 print:border-t-0 print:bg-transparent print:shadow-none"
>
    <header
        class="grid gap-3 px-5 py-5 sm:px-6 md:gap-6 print:break-inside-avoid print:gap-6 print:break-after-avoid print:border-t-2 {rowColumns(board.readonly)} {isSession
            ? 'bg-journal/[0.035] print:border-journal'
            : 'bg-ink-50/60 print:border-brand-700'}"
    >
        <div class="md:pt-0.5">
            <TimeRange time={session.time} cities={board.cities} />
        </div>

        <div class="min-w-0">
            <p class="kicker {isSession ? 'text-journal' : 'text-brand-700'}">{isSession ? `Session ${session.number}` : 'Plenary'}</p>
            <h3 class="mt-1 font-serif text-xl leading-snug font-semibold text-ink-900">
                <Highlight text={heading} query={board.query} />
            </h3>

            {#if roles.length}
                <dl class="mt-4 flex flex-wrap gap-x-10 gap-y-3">
                    {#each roles as role (role.label)}
                        <div class="min-w-0">
                            <dt class="kicker text-ink-500">{role.label}</dt>
                            <dd class="mt-0.5 text-[15px]">
                                {#if role.missing && board.readonly}
                                    <span class="text-ink-500">To be announced</span>
                                {:else if role.missing}
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
                                {:else if role.journal}
                                    <span class="font-serif font-semibold text-journal"><Highlight text={role.name ?? ''} query={board.query} /></span>
                                {:else}
                                    <span class="font-medium text-ink-900"><Highlight text={role.name ?? ''} query={board.query} /></span>
                                {/if}
                            </dd>
                        </div>
                    {/each}
                </dl>
            {/if}
        </div>

        {#if !board.readonly}
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
        {/if}
    </header>

    {#if session.items.length}
        <ol class="divide-y divide-line border-t border-line">
            {#each session.items as item, index (item.id)}
                <AgendaItemRow {item} repeatsTime={isSameTimeRange(session.items[index - 1]?.time, item.time)} />
            {/each}
        </ol>
    {/if}

    {#if !board.isFiltering && !board.readonly}
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
