<script lang="ts">
    import { toasts } from '@/lib/toasts.svelte';
</script>

<div class="pointer-events-none fixed inset-x-0 bottom-5 z-[60] flex flex-col items-center gap-2 px-4 print:hidden" aria-live="polite" role="status">
    {#each toasts.items as toast (toast.id)}
        <div
            class="pointer-events-auto flex max-w-md items-center gap-3 rounded-lg px-4 py-2.5 text-sm shadow-lg {toast.tone === 'error'
                ? 'bg-rose-700 text-white'
                : 'bg-ink-950 text-white'}"
        >
            {#if toast.tone === 'neutral'}
                <span class="size-1.5 shrink-0 rounded-full bg-brand-400" aria-hidden="true"></span>
            {/if}
            <span>{toast.message}</span>
        </div>
    {/each}
</div>
