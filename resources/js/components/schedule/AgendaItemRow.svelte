<script lang="ts">
    import StatusPill from '@/components/ui/StatusPill.svelte';
    import { pluralize } from '@/lib/format';
    import { hasSpeaker, isAtRisk, isTopicTbd, itPresents, tracksAssets } from '@/lib/stats';
    import { assetsStatusOptions, speakerStatusOptions } from '@/lib/status';
    import type { AgendaItem, Conference } from '@/types/models';
    import TimeRange from './TimeRange.svelte';

    let { item, cities }: { item: AgendaItem; cities: Conference['cities'] } = $props();

    const speaker = $derived(speakerStatusOptions[item.speaker_status]);
    const assets = $derived(assetsStatusOptions[item.assets_status]);
    const details = $derived([item.speaker_role, item.affiliation].filter(Boolean).join(' · '));
    const atRisk = $derived(isAtRisk(item));
</script>

<li class="relative grid gap-3 px-5 py-4 sm:px-6 md:grid-cols-[9.5rem_minmax(0,1fr)_15rem] md:gap-6 {hasSpeaker(item) ? '' : 'bg-canvas/60'}">
    {#if atRisk}
        <span class="absolute inset-y-0 left-0 w-[3px] bg-rose-500" aria-hidden="true"></span>
    {/if}

    <div class="md:pt-0.5">
        <TimeRange time={item.time} {cities} emptyLabel="Within session" />
    </div>

    <div class="min-w-0">
        {#if item.label}
            <p class="label-caps text-ink-500">{item.label}</p>
        {/if}

        {#if hasSpeaker(item)}
            <p class="mt-0.5 text-[15px] font-semibold text-ink-900">{item.speaker_name}</p>
            {#if details}
                <p class="mt-0.5 text-sm leading-snug text-ink-500">{details}</p>
            {/if}

            {#if isTopicTbd(item)}
                <p class="mt-2">
                    <span class="rounded-sm bg-amber-50 px-1.5 py-0.5 text-sm font-medium text-amber-800 ring-1 ring-amber-600/20 ring-inset">
                        Topic TBD
                    </span>
                </p>
            {:else}
                <p class="mt-2 font-serif text-[15.5px] leading-snug text-ink-800">{item.title}</p>
            {/if}

            {#if item.emails.length}
                <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    {#each item.emails as email (email)}
                        <a
                            href="mailto:{email}"
                            class="break-all text-ink-500 underline decoration-ink-200 underline-offset-4 transition-colors hover:text-ink-900 hover:decoration-ink-400"
                        >
                            {email}
                        </a>
                    {/each}
                </p>
            {/if}
        {:else}
            <p class="text-sm leading-relaxed whitespace-pre-line text-ink-600 {item.label ? 'mt-0.5' : ''}">{item.title}</p>
        {/if}
    </div>

    <div class="flex flex-col gap-2 md:pt-0.5">
        {#if hasSpeaker(item)}
            <dl class="flex flex-wrap gap-x-5 gap-y-2 md:flex-col">
                <div class="flex items-center gap-2.5">
                    <dt class="w-14 text-xs text-ink-500">Speaker</dt>
                    <dd><StatusPill tone={speaker.tone} label={speaker.label} /></dd>
                </div>
                {#if tracksAssets(item)}
                    <div class="flex items-center gap-2.5">
                        <dt class="w-14 text-xs text-ink-500">Assets</dt>
                        <dd><StatusPill tone={assets.tone} label={assets.label} /></dd>
                    </div>
                {/if}
            </dl>

            {#if atRisk}
                <p class="text-xs font-medium text-rose-700">No assets for IT to present</p>
            {:else if itPresents(item)}
                <p class="text-xs text-ink-500">IT host presents the assets</p>
            {/if}
        {/if}

        {#if item.notes.length}
            <p class="text-xs text-ink-500">{pluralize(item.notes.length, 'note')}</p>
        {/if}
    </div>
</li>
