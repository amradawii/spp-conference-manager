<script lang="ts">
    import StatusPill from '@/components/ui/StatusPill.svelte';
    import { getBoard } from '@/lib/board';
    import { editorPrompt } from '@/lib/editor.svelte';
    import { assetsStatusOptions, speakerStatusOptions } from '@/lib/status';
    import type { AgendaItem } from '@/types/models';
    import { router } from '@inertiajs/svelte';
    import { DropdownMenu } from 'bits-ui';

    let { item, field }: { item: AgendaItem; field: 'speaker' | 'assets' } = $props();

    const board = getBoard();
    let saving = $state(false);

    const options = $derived(
        field === 'speaker'
            ? Object.entries(speakerStatusOptions)
            : Object.entries(assetsStatusOptions).filter(([value]) => value !== 'not_needed'),
    );
    const current = $derived(field === 'speaker' ? item.speaker_status : item.assets_status);
    const currentOption = $derived(field === 'speaker' ? speakerStatusOptions[item.speaker_status] : assetsStatusOptions[item.assets_status]);
    const fieldLabel = $derived(field === 'speaker' ? 'Speaker' : 'Assets');

    function save(value: string): void {
        if (value === current) return;

        const submit = () =>
            router.patch(
                board.routes.itemStatus(item.id),
                { [`${field}_status`]: value },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onStart: () => (saving = true),
                    onFinish: () => (saving = false),
                    onError: (errors) => editorPrompt.recover(errors, submit),
                },
            );

        editorPrompt.require(submit);
    }
</script>

<DropdownMenu.Root>
    <DropdownMenu.Trigger
        class="rounded-full transition-opacity hover:opacity-80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-700 {saving ? 'opacity-50' : ''}"
        aria-label="{fieldLabel} status: {currentOption.label}. Change"
        disabled={saving}
    >
        <StatusPill tone={currentOption.tone} label={saving ? 'Saving…' : currentOption.label} caret />
    </DropdownMenu.Trigger>
    <DropdownMenu.Portal>
        <DropdownMenu.Content
            align="start"
            sideOffset={6}
            class="z-50 w-72 rounded-lg border border-line bg-white p-1 shadow-xl ring-1 ring-ink-950/5 focus:outline-none"
        >
            <p class="label-caps px-3 pt-2 pb-1 text-ink-400">{fieldLabel} status</p>
            {#each options as [value, option] (value)}
                <DropdownMenu.Item
                    onSelect={() => save(value)}
                    class="flex cursor-pointer items-start gap-3 rounded-md px-3 py-2 outline-none data-highlighted:bg-ink-50"
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full {option.tone === 'positive'
                            ? 'bg-emerald-500'
                            : option.tone === 'negative'
                              ? 'bg-rose-500'
                              : 'bg-amber-500'}"
                        aria-hidden="true"
                    ></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-ink-900">{option.label}</span>
                        <span class="block text-xs text-ink-500">{option.hint}</span>
                    </span>
                    {#if value === current}
                        <span class="mt-0.5 text-xs font-medium text-ink-400">Current</span>
                    {/if}
                </DropdownMenu.Item>
            {/each}
        </DropdownMenu.Content>
    </DropdownMenu.Portal>
</DropdownMenu.Root>
