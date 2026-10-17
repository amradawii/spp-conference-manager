<script lang="ts">
    import Button from '@/components/ui/Button.svelte';
    import Drawer from '@/components/ui/Drawer.svelte';
    import FormField from '@/components/ui/FormField.svelte';
    import { getBoard } from '@/lib/board';
    import { editorPrompt } from '@/lib/editor.svelte';
    import { formatClockRange, formatDay, shiftTime, teamLeadHours } from '@/lib/format';
    import { inputClass } from '@/lib/ui';
    import type { ConferenceSession, Day, SessionKind } from '@/types/models';
    import { router, useForm } from '@inertiajs/svelte';
    import StaleNotice from './StaleNotice.svelte';

    let {
        day,
        dayNumber,
        session,
        nextNumber,
        onClose,
    }: { day: Day; dayNumber: number; session?: ConferenceSession; nextNumber: number; onClose: () => void } = $props();

    const board = getBoard();
    const editing = $derived(session !== undefined);

    let open = $state(true);
    let confirmingDelete = $state(false);
    let deleting = $state(false);

    const form = useForm(() => ({
        kind: (session?.kind ?? 'session') as SessionKind,
        number: session?.number ?? nextNumber,
        journal: session?.journal ?? '',
        title: session?.title ?? '',
        starts_at: session?.time.start ?? day.sessions.at(-1)?.time.end ?? '',
        ends_at: session?.time.end ?? '',
        spp_moderator: session?.spp_moderator ?? '',
        journal_moderator: session?.journal_moderator ?? '',
    }));

    const errors = $derived(form.errors as Record<string, string | undefined>);
    const lead = $derived(teamLeadHours(day));
    const teamPreview = $derived(
        form.starts_at && form.ends_at ? formatClockRange(shiftTime(form.starts_at, lead), shiftTime(form.ends_at, lead)) : null,
    );

    function close(): void {
        open = false;
        onClose();
    }

    function save(event: SubmitEvent): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: close,
            onError: (errs: Record<string, string>) => editorPrompt.recover(errs, submit),
        };

        // The live record's timestamp: after a "changed by someone else" error the
        // page reloads, so saving again deliberately replaces their version.
        const submit = () =>
            editing
                ? form.transform((data) => ({ ...data, expected_updated_at: session!.updated_at })).put(board.routes.session(session!.id), options)
                : form.transform((data) => ({ ...data, day_id: day.id })).post(board.routes.sessions, options);

        editorPrompt.require(submit);
    }

    function destroy(): void {
        const submit = () =>
            router.delete(board.routes.session(session!.id), {
                preserveScroll: true,
                preserveState: true,
                onStart: () => (deleting = true),
                onFinish: () => (deleting = false),
                onSuccess: close,
                onError: (errs) => editorPrompt.recover(errs, submit),
            });

        editorPrompt.require(submit);
    }
</script>

<Drawer
    bind:open
    title={editing ? 'Edit session' : 'Add session'}
    description="Day {dayNumber} · {formatDay(day.date)}"
    {onClose}
>
    <form id="session-form" onsubmit={save} class="space-y-6">
        {#if errors.stale}
            <StaleNotice message={errors.stale} />
        {/if}

        <fieldset>
            <legend class="text-sm font-medium text-ink-800">Type</legend>
            <div class="mt-1.5 grid grid-cols-2 gap-2">
                {#each [{ value: 'session', label: 'Journal session', hint: 'Numbered, with moderators' }, { value: 'ceremony', label: 'Plenary', hint: 'Opening, closing, ceremony' }] as option (option.value)}
                    <label
                        class="cursor-pointer rounded-lg px-3 py-2.5 ring-1 ring-inset transition-colors {form.kind === option.value
                            ? 'bg-ink-50 ring-ink-700'
                            : 'ring-line-strong hover:ring-ink-300'}"
                    >
                        <input type="radio" bind:group={form.kind} value={option.value} class="sr-only" />
                        <span class="block text-sm font-medium text-ink-900">{option.label}</span>
                        <span class="block text-xs text-ink-500">{option.hint}</span>
                    </label>
                {/each}
            </div>
        </fieldset>

        <div class="grid gap-4 {form.kind === 'session' ? 'sm:grid-cols-[6rem_minmax(0,1fr)]' : ''}">
            {#if form.kind === 'session'}
                <FormField label="Number" for="session-number" error={errors.number}>
                    <input id="session-number" type="number" min="1" max="99" bind:value={form.number} class={inputClass} />
                </FormField>
            {/if}
            <FormField label="Journal" for="session-journal" error={errors.journal} optional={form.kind === 'ceremony'}>
                <input
                    id="session-journal"
                    bind:value={form.journal}
                    class={inputClass}
                    placeholder="e.g. Journal of Climate Change Action (JCCA)"
                    maxlength="255"
                />
            </FormField>
        </div>

        <FormField
            label="Title"
            for="session-title"
            error={errors.title}
            optional={!!form.journal.trim()}
            hint={form.kind === 'ceremony' ? 'e.g. Opening Ceremony' : 'Shown under the journal name'}
        >
            <input id="session-title" bind:value={form.title} class={inputClass} maxlength="255" />
        </FormField>

        <div>
            <div class="grid grid-cols-2 gap-4">
                <FormField label="Starts ({board.cities.conference})" for="session-start" error={errors.starts_at}>
                    <input id="session-start" type="time" bind:value={form.starts_at} class={inputClass} required />
                </FormField>
                <FormField label="Ends ({board.cities.conference})" for="session-end" error={errors.ends_at}>
                    <input id="session-end" type="time" bind:value={form.ends_at} class={inputClass} required />
                </FormField>
            </div>
            <p class="mt-2 text-xs text-ink-500">
                {#if teamPreview}
                    That is <span class="font-semibold text-ink-800 tabular-nums">{teamPreview}</span> {board.cities.team} time on this day.
                {:else}
                    Enter times as in the programme ({board.cities.conference}). {board.cities.team} time is worked out for you.
                {/if}
            </p>
        </div>

        {#if form.kind === 'session'}
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="SPP moderator" for="session-spp" error={errors.spp_moderator} optional>
                    <input id="session-spp" bind:value={form.spp_moderator} class={inputClass} maxlength="255" />
                </FormField>
                <FormField label="Journal moderator" for="session-journal-moderator" error={errors.journal_moderator} optional>
                    <input id="session-journal-moderator" bind:value={form.journal_moderator} class={inputClass} maxlength="255" />
                </FormField>
            </div>
        {/if}
    </form>

    {#snippet footer()}
        {#if confirmingDelete && session}
            <p class="flex-1 text-sm text-rose-800">
                Delete this {session.kind === 'session' ? 'session' : 'block'}{session.items.length
                    ? ` and its ${session.items.length} ${session.items.length === 1 ? 'item' : 'items'}`
                    : ''}?
            </p>
            <Button variant="ghost" onclick={() => (confirmingDelete = false)}>Keep</Button>
            <Button variant="danger-solid" disabled={deleting} onclick={destroy}>{deleting ? 'Deleting…' : 'Delete'}</Button>
        {:else}
            {#if editing}
                <Button variant="danger" class="-ml-2" onclick={() => (confirmingDelete = true)}>Delete</Button>
            {/if}
            <span class="flex-1"></span>
            <Button variant="ghost" onclick={close}>Cancel</Button>
            <Button variant="primary" type="submit" form="session-form" disabled={form.processing}>
                {form.processing ? 'Saving…' : errors.stale ? 'Save anyway' : editing ? 'Save changes' : 'Add session'}
            </Button>
        {/if}
    {/snippet}
</Drawer>
