<script lang="ts">
    import { highlightParts } from '@/lib/search';

    let { text, query }: { text: string; query: string } = $props();

    const parts = $derived(highlightParts(text, query));
</script>

{#each parts as part, index (index)}{#if part.match}<mark class="rounded-sm bg-amber-100 text-inherit">{part.text}</mark>{:else}{part.text}{/if}{/each}
