<script lang="ts">
    let { notesCount, onNotes, onEdit }: { notesCount: number; onNotes: () => void; onEdit: () => void } = $props();
</script>

<div class="flex items-center gap-1 text-xs print:hidden">
    <button
        type="button"
        onclick={onNotes}
        class="-ml-1.5 rounded-md px-1.5 py-1 font-medium transition-colors hover:bg-ink-50 {notesCount ? 'text-ink-800' : 'text-ink-500 hover:text-ink-800'}"
    >
        {notesCount ? `Notes (${notesCount})` : 'Add note'}
    </button>
    <span class="text-ink-200" aria-hidden="true">·</span>
    <button type="button" onclick={onEdit} class="rounded-md px-1.5 py-1 font-medium text-ink-500 transition-colors hover:bg-ink-50 hover:text-ink-800">
        Edit
    </button>
</div>
