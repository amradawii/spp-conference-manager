<script lang="ts">
    import X from '@lucide/svelte/icons/x';
    import { Dialog } from 'bits-ui';
    import type { Snippet } from 'svelte';

    let {
        open = $bindable(false),
        title,
        description,
        children,
        footer,
        onClose,
    }: {
        open?: boolean;
        title: string;
        description?: string;
        children: Snippet;
        footer?: Snippet;
        onClose?: () => void;
    } = $props();
</script>

<Dialog.Root bind:open onOpenChange={(value) => !value && onClose?.()}>
    <Dialog.Portal>
        <Dialog.Overlay class="fixed inset-0 z-40 bg-ink-950/40 backdrop-blur-[2px]" />
        <Dialog.Content
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col bg-white shadow-2xl ring-1 ring-ink-950/5 focus:outline-none"
        >
            <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-5">
                <div class="min-w-0">
                    <Dialog.Title class="font-serif text-xl leading-snug font-semibold text-ink-900">{title}</Dialog.Title>
                    {#if description}
                        <Dialog.Description class="mt-1 text-sm text-ink-500">{description}</Dialog.Description>
                    {/if}
                </div>
                <Dialog.Close class="-mr-2 rounded-md p-1.5 text-ink-400 hover:bg-ink-50 hover:text-ink-700" aria-label="Close">
                    <X class="size-5" />
                </Dialog.Close>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                {@render children()}
            </div>
            {#if footer}
                <div class="flex items-center gap-2 border-t border-line bg-canvas/50 px-6 py-4">
                    {@render footer()}
                </div>
            {/if}
        </Dialog.Content>
    </Dialog.Portal>
</Dialog.Root>
