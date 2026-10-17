<script lang="ts">
    import Button from '@/components/ui/Button.svelte';
    import Drawer from '@/components/ui/Drawer.svelte';
    import FormField from '@/components/ui/FormField.svelte';
    import Select from '@/components/ui/Select.svelte';
    import { getBoard } from '@/lib/board';
    import { editorPrompt } from '@/lib/editor.svelte';
    import { sessionLabel } from '@/lib/filters';
    import { shiftTime, teamLeadHours } from '@/lib/format';
    import { inputClass } from '@/lib/ui';
    import type { AgendaItem, AgendaItemType, ConferenceSession, Day } from '@/types/models';
    import { router, useForm } from '@inertiajs/svelte';
    import StaleNotice from './StaleNotice.svelte';

    let {
        day,
        session,
        item,
        onClose,
    }: { day: Day; session: ConferenceSession; item?: AgendaItem; onClose: () => void } = $props();

    const board = getBoard();
    const editing = $derived(item !== undefined);

    const types: { value: AgendaItemType; label: string }[] = [
        { value: 'keynote', label: 'Keynote' },
        { value: 'oral', label: 'Oral presentation' },
        { value: 'address', label: 'Address or talk' },
        { value: 'segment', label: 'Programme segment (no speaker)' },
    ];

    function defaultLabel(type: AgendaItemType): string {
        if (type === 'keynote') return `Keynote ${session.items.filter((i) => i.type === 'keynote').length + 1}`;
        return '';
    }

    let open = $state(true);
    let confirmingDelete = $state(false);
    let deleting = $state(false);

    const form = useForm(() => ({
        type: (item?.type ?? 'keynote') as AgendaItemType,
        label: item?.label ?? defaultLabel('keynote'),
        starts_at: item?.time?.start ?? '',
        ends_at: item?.time?.end ?? '',
        speaker_name: item?.speaker_name ?? '',
        speaker_role: item?.speaker_role ?? '',
        affiliation: item?.affiliation ?? '',
        emails: (item?.emails ?? []).join('\n'),
        title: item?.title ?? '',
    }));

    const errors = $derived(form.errors as Record<string, string | undefined>);
    const emailError = $derived(Object.entries(errors).find(([key]) => key === 'emails' || key.startsWith('emails.'))?.[1]);
    const isSegment = $derived(form.type === 'segment');
    const lead = $derived(teamLeadHours(day));
    const teamPreview = $derived(
        form.starts_at && form.ends_at ? `${shiftTime(form.starts_at, lead)}–${shiftTime(form.ends_at, lead)}` : null,
    );

    function changeType(type: AgendaItemType): void {
        if (!editing && form.label === defaultLabel(form.type)) {
            form.label = defaultLabel(type);
        }
        form.type = type;
    }

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

        const submit = () => {
            form.transform((data) => ({
                ...data,
                emails: data.emails.split(/[\n,;]+/).map((email) => email.trim()).filter(Boolean),
                ...(editing ? { expected_updated_at: item!.updated_at } : {}),
            }));

            if (editing) {
                form.put(board.routes.item(item!.id), options);
            } else {
                form.post(board.routes.sessionItems(session.id), options);
            }
        };

        editorPrompt.require(submit);
    }

    function destroy(): void {
        const submit = () =>
            router.delete(board.routes.item(item!.id), {
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

<Drawer bind:open title={editing ? 'Edit item' : 'Add item'} description={sessionLabel(session)} {onClose}>
    <form id="item-form" onsubmit={save} class="space-y-6">
        {#if errors.stale}
            <StaleNotice message={errors.stale} />
        {/if}

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="Type" for="item-type" error={errors.type}>
                <Select id="item-type" value={form.type} onchange={(event) => changeType(event.currentTarget.value as AgendaItemType)}>
                    {#each types as type (type.value)}
                        <option value={type.value}>{type.label}</option>
                    {/each}
                </Select>
            </FormField>
            <FormField label="Label" for="item-label" error={errors.label} optional hint="Shown above the name, e.g. “Keynote 3”">
                <input id="item-label" bind:value={form.label} class={inputClass} maxlength="100" />
            </FormField>
        </div>

        <div>
            <div class="grid grid-cols-2 gap-4">
                <FormField label="Starts ({board.cities.conference})" for="item-start" error={errors.starts_at} optional>
                    <input id="item-start" type="time" bind:value={form.starts_at} class={inputClass} />
                </FormField>
                <FormField label="Ends ({board.cities.conference})" for="item-end" error={errors.ends_at} optional>
                    <input id="item-end" type="time" bind:value={form.ends_at} class={inputClass} />
                </FormField>
            </div>
            <p class="mt-2 text-xs text-ink-500">
                {#if teamPreview}
                    That is <span class="font-semibold text-ink-800 tabular-nums">{teamPreview}</span> {board.cities.team} time.
                {:else}
                    Leave empty if the programme gives no time for this item.
                {/if}
            </p>
        </div>

        {#if isSegment}
            <FormField label="Description" for="item-title" error={errors.title} hint="e.g. “Award ceremony and group photo”">
                <textarea id="item-title" bind:value={form.title} rows="4" maxlength="2000" class="{inputClass} resize-y"></textarea>
            </FormField>
        {:else}
            <FormField label="Speaker name" for="item-speaker" error={errors.speaker_name}>
                <input id="item-speaker" bind:value={form.speaker_name} class={inputClass} maxlength="255" placeholder="e.g. Prof. Dr. Jana Novak" />
            </FormField>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Role" for="item-role" error={errors.speaker_role} optional>
                    <input id="item-role" bind:value={form.speaker_role} class={inputClass} maxlength="255" placeholder="e.g. Editor-in-Chief" />
                </FormField>
                <FormField label="Affiliation" for="item-affiliation" error={errors.affiliation} optional>
                    <input id="item-affiliation" bind:value={form.affiliation} class={inputClass} maxlength="1000" />
                </FormField>
            </div>

            <FormField label="Topic" for="item-title" error={errors.title} optional hint="Leave empty while the topic is not known; it shows as Topic TBD.">
                <textarea id="item-title" bind:value={form.title} rows="2" maxlength="2000" class="{inputClass} resize-y"></textarea>
            </FormField>

            <FormField label="Emails" for="item-emails" error={emailError} optional hint="One per line, up to five.">
                <textarea id="item-emails" bind:value={form.emails} rows="2" class="{inputClass} resize-y" spellcheck="false"></textarea>
            </FormField>

            {#if !editing}
                <p class="text-xs text-ink-500">New speakers start as Pending, with assets Pending. Change both from the board.</p>
            {/if}
        {/if}
    </form>

    {#snippet footer()}
        {#if confirmingDelete && item}
            <p class="flex-1 text-sm text-rose-800">
                Delete {item.speaker_name ?? item.label ?? 'this item'}{item.notes.length ? ' and its notes' : ''}?
            </p>
            <Button variant="ghost" onclick={() => (confirmingDelete = false)}>Keep</Button>
            <Button variant="danger-solid" disabled={deleting} onclick={destroy}>{deleting ? 'Deleting…' : 'Delete'}</Button>
        {:else}
            {#if editing}
                <Button variant="danger" class="-ml-2" onclick={() => (confirmingDelete = true)}>Delete</Button>
            {/if}
            <span class="flex-1"></span>
            <Button variant="ghost" onclick={close}>Cancel</Button>
            <Button variant="primary" type="submit" form="item-form" disabled={form.processing}>
                {form.processing ? 'Saving…' : errors.stale ? 'Save anyway' : editing ? 'Save changes' : 'Add item'}
            </Button>
        {/if}
    {/snippet}
</Drawer>
