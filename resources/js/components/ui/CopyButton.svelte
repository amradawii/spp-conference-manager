<script lang="ts">
    import { copyText } from '@/lib/clipboard';
    import { toasts } from '@/lib/toasts.svelte';
    import Check from '@lucide/svelte/icons/check';
    import Copy from '@lucide/svelte/icons/copy';

    let { text, label }: { text: string; label: string } = $props();

    let copied = $state(false);
    let timer: ReturnType<typeof setTimeout> | undefined;

    async function copy(): Promise<void> {
        try {
            await copyText(text);
        } catch {
            toasts.push('Could not copy. Select the address and copy it by hand.', 'error');
            return;
        }

        copied = true;
        toasts.push(`Copied ${text}`);
        clearTimeout(timer);
        timer = setTimeout(() => (copied = false), 1500);
    }
</script>

<button
    type="button"
    onclick={copy}
    class="shrink-0 rounded p-1 transition-colors hover:bg-ink-50 print:hidden {copied ? 'text-emerald-600' : 'text-ink-400 hover:text-ink-700'}"
    aria-label={copied ? 'Copied' : label}
    title={copied ? 'Copied' : label}
>
    {#if copied}
        <Check class="size-3.5" aria-hidden="true" />
    {:else}
        <Copy class="size-3.5" aria-hidden="true" />
    {/if}
</button>
