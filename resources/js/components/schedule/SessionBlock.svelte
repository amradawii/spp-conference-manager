<script lang="ts">
    import { pluralize } from '@/lib/format';
    import { missingModerators } from '@/lib/stats';
    import type { Conference, ConferenceSession } from '@/types/models';
    import AgendaItemRow from './AgendaItemRow.svelte';
    import TimeRange from './TimeRange.svelte';

    let { session, cities }: { session: ConferenceSession; cities: Conference['cities'] } = $props();

    const isSession = $derived(session.kind === 'session');
    const missing = $derived(missingModerators(session));
    const moderators = $derived([
        { label: 'SPP moderator', name: session.spp_moderator, missing: missing.includes('spp') },
        { label: 'Journal moderator', name: session.journal_moderator, missing: missing.includes('journal') },
    ]);
</script>

<article
    id="session-{session.id}"
    class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(11,20,38,0.04)]"
>
    <header class="grid gap-3 px-5 py-5 sm:px-6 md:grid-cols-[9.5rem_minmax(0,1fr)_15rem] md:gap-6 {isSession ? '' : 'bg-ink-50/60'}">
        <div class="md:pt-0.5">
            <TimeRange time={session.time} {cities} />
        </div>

        <div class="min-w-0">
            <p class="label-caps text-ink-500">{isSession ? `Session ${session.number}` : 'Plenary'}</p>
            {#if session.journal}
                <h3 class="mt-1 font-serif text-xl leading-snug font-semibold text-ink-900">{session.journal}</h3>
                {#if session.title}
                    <p class="mt-1 text-[15px] leading-snug text-ink-600">{session.title}</p>
                {/if}
            {:else if session.title}
                <h3 class="mt-1 font-serif text-xl leading-snug font-semibold text-ink-900">{session.title}</h3>
            {/if}

            {#if isSession}
                <dl class="mt-4 flex flex-wrap gap-x-10 gap-y-3">
                    {#each moderators as moderator (moderator.label)}
                        <div>
                            <dt class="label-caps text-ink-400">{moderator.label}</dt>
                            <dd class="mt-0.5 text-sm">
                                {#if moderator.missing}
                                    <span class="inline-flex items-center gap-1.5 font-medium text-amber-700">
                                        <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                                        Not assigned
                                    </span>
                                {:else}
                                    <span class="font-medium text-ink-900">{moderator.name}</span>
                                {/if}
                            </dd>
                        </div>
                    {/each}
                </dl>
            {/if}
        </div>

        <div class="text-xs text-ink-500 md:pt-0.5">
            {#if session.notes.length}
                {pluralize(session.notes.length, 'note')}
            {/if}
        </div>
    </header>

    {#if session.items.length}
        <ol class="divide-y divide-line border-t border-line">
            {#each session.items as item (item.id)}
                <AgendaItemRow {item} {cities} />
            {/each}
        </ol>
    {/if}
</article>
