<script lang="ts">
    import CopyButton from '@/components/ui/CopyButton.svelte';
    import Highlight from '@/components/ui/Highlight.svelte';
    import { getBoard } from '@/lib/board';
    import { formatRelative } from '@/lib/format';
    import { hasSpeaker, isAtRisk, isTopicTbd, itPresents, tracksAssets } from '@/lib/stats';
    import { rowColumns } from '@/lib/ui';
    import type { AgendaItem } from '@/types/models';
    import RecordActions from './RecordActions.svelte';
    import StatusMenu from './StatusMenu.svelte';
    import TimeRange from './TimeRange.svelte';

    /** `repeatsTime`: same time as the row above (e.g. a presenter inside an oral block), so it isn't printed again. */
    let { item, repeatsTime = false }: { item: AgendaItem; repeatsTime?: boolean } = $props();

    const board = getBoard();
    const details = $derived([item.speaker_role, item.affiliation].filter(Boolean).join(' · '));
    const atRisk = $derived(!board.readonly && isAtRisk(item));
</script>

<li class="relative grid gap-3 px-5 py-4 even:bg-stripe sm:px-6 md:gap-6 print:break-inside-avoid print:gap-6 {rowColumns(board.readonly)}">
    {#if atRisk}
        <span class="absolute inset-y-0 left-0 w-[3px] bg-rose-500" aria-hidden="true"></span>
    {/if}

    <div class="md:pt-0.5 {repeatsTime ? 'hidden md:block print:block' : ''}">
        {#if !repeatsTime}
            <TimeRange time={item.time} cities={board.cities} emptyLabel="Within session" />
        {/if}
    </div>

    <div class="min-w-0">
        {#if item.label}
            <p class="kicker {hasSpeaker(item) ? 'text-talk' : 'text-ink-500'}">{item.label}</p>
        {/if}

        {#if hasSpeaker(item)}
            <p class="mt-0.5 text-[17px] font-semibold text-ink-900">
                <Highlight text={item.speaker_name ?? ''} query={board.query} />
            </p>
            {#if details}
                <p class="mt-0.5 text-sm leading-snug text-ink-500"><Highlight text={details} query={board.query} /></p>
            {/if}

            {#if isTopicTbd(item)}
                <p class="mt-2">
                    <span class="rounded-sm bg-yellow-100 px-1.5 py-0.5 text-sm font-semibold text-yellow-900 ring-1 ring-yellow-500/30 ring-inset">
                        Topic TBD
                    </span>
                </p>
            {:else}
                <p class="mt-2 font-serif text-[16.5px] leading-snug whitespace-pre-line text-ink-800">
                    <Highlight text={item.title ?? ''} query={board.query} />
                </p>
            {/if}

            {#if item.emails.length}
                <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    {#each item.emails as email (email)}
                        <span class="inline-flex min-w-0 items-center gap-0.5">
                            <a
                                href="mailto:{email}"
                                class="break-all text-ink-500 underline decoration-ink-200 underline-offset-4 transition-colors hover:text-ink-900 hover:decoration-ink-400"
                            >
                                <Highlight text={email} query={board.query} />
                            </a>
                            {#if !board.readonly}
                                <CopyButton text={email} label="Copy {email}" />
                            {/if}
                        </span>
                    {/each}
                </p>
            {/if}
        {:else}
            <p class="text-sm leading-relaxed whitespace-pre-line text-ink-600 {item.label ? 'mt-0.5' : ''}">
                <Highlight text={item.title ?? ''} query={board.query} />
            </p>
        {/if}
    </div>

    {#if !board.readonly}
        <div class="flex flex-col gap-2.5 md:pt-0.5">
            {#if hasSpeaker(item)}
                <dl class="flex flex-wrap gap-x-5 gap-y-2 md:flex-col print:flex-col">
                    <div class="flex items-center gap-2.5">
                        <dt class="w-14 text-xs text-ink-500">Speaker</dt>
                        <dd><StatusMenu {item} field="speaker" /></dd>
                    </div>
                    {#if tracksAssets(item)}
                        <div class="flex items-center gap-2.5">
                            <dt class="w-14 text-xs text-ink-500">Assets</dt>
                            <dd><StatusMenu {item} field="assets" /></dd>
                        </div>
                    {/if}
                </dl>

                {#if atRisk}
                    <p class="text-xs font-medium text-rose-700">No assets for IT to present</p>
                {:else if itPresents(item)}
                    <p class="text-xs text-ink-500">IT host presents the assets</p>
                {/if}
            {/if}

            <RecordActions
                notesCount={item.notes.length}
                onNotes={() => board.openNotes({ type: 'item', id: item.id })}
                onEdit={() => board.editItem(item)}
            />

            {#if item.updated_by}
                <p class="text-xs text-ink-400 print:hidden" title={new Date(item.updated_at).toLocaleString()}>
                    {item.updated_by} · {formatRelative(item.updated_at)}
                </p>
            {/if}
        </div>
    {/if}
</li>
