<script lang="ts">
    import X from '@lucide/svelte/icons/x';
    import { Dialog } from 'bits-ui';
    import type { Snippet } from 'svelte';

    let {
        open = $bindable(false),
        title,
        description,
        size = 'md',
        children,
        footer,
        onClose,
    }: {
        open?: boolean;
        title: string;
        description?: string;
        size?: 'sm' | 'md';
        children: Snippet;
        footer?: Snippet;
        onClose?: () => void;
    } = $props();
</script>

<Dialog.Root bind:open onOpenChange={(value) => !value && onClose?.()}>
    <Dialog.Portal>
        <Dialog.Overlay class="fixed inset-0 z-40 bg-ink-950/40 backdrop-blur-[2px]" />
        <Dialog.Content
            class="fixed top-[8vh] left-1/2 z-50 flex max-h-[84vh] w-[calc(100%-2rem)] -translate-x-1/2 flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-ink-950/5 focus:outline-none {size === 'sm' ? 'max-w-md' : 'max-w-xl'}"
        >
            <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
                <div class="min-w-0">
                    <Dialog.Title class="font-serif text-lg leading-snug font-semibold text-ink-900">{title}</Dialog.Title>
                    {#if description}
                        <Dialog.Description class="mt-0.5 text-sm text-ink-500">{description}</Dialog.Description>
                    {/if}
                </div>
                <Dialog.Close class="-mr-2 rounded-md p-1.5 text-ink-400 hover:bg-ink-50 hover:text-ink-700" aria-label="Close">
                    <X class="size-4" />
                </Dialog.Close>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                {@render children()}
            </div>
            {#if footer}
                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/50 px-6 py-3">
                    {@render footer()}
                </div>
            {/if}
        </Dialog.Content>
    </Dialog.Portal>
</Dialog.Root>
