<script lang="ts">
    import Button from '@/components/ui/Button.svelte';
    import Modal from '@/components/ui/Modal.svelte';
    import { getBoard, type NotesTarget } from '@/lib/board';
    import { editorPrompt } from '@/lib/editor.svelte';
    import { formatRelative } from '@/lib/format';
    import { inputClass } from '@/lib/ui';
    import type { Note } from '@/types/models';
    import { router, useForm } from '@inertiajs/svelte';

    let {
        target,
        title,
        context,
        notes,
        onClose,
    }: { target: NotesTarget; title: string; context: string; notes: Note[]; onClose: () => void } = $props();

    const board = getBoard();
    let open = $state(true);
    let confirmingId = $state<number | null>(null);
    let deletingId = $state<number | null>(null);

    const form = useForm({ body: '' });

    function add(event: SubmitEvent): void {
        event.preventDefault();

        const submit = () =>
            form
                .transform((data) => ({ ...data, notable_type: target.type, notable_id: target.id }))
                .post(board.routes.notes, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => form.reset(),
                    onError: (errors) => editorPrompt.recover(errors, submit),
                });

        editorPrompt.require(submit);
    }

    function remove(note: Note): void {
        const submit = () =>
            router.delete(board.routes.note(note.id), {
                preserveScroll: true,
                preserveState: true,
                onStart: () => (deletingId = note.id),
                onFinish: () => {
                    deletingId = null;
                    confirmingId = null;
                },
                onError: (errors) => editorPrompt.recover(errors, submit),
            });

        editorPrompt.require(submit);
    }

    function onBodyKeydown(event: KeyboardEvent): void {
        if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
            (event.currentTarget as HTMLTextAreaElement).form?.requestSubmit();
        }
    }
</script>

<Modal bind:open {title} description={context} {onClose}>
    <form onsubmit={add}>
        <label for="note-body" class="sr-only">New note</label>
        <textarea
            id="note-body"
            bind:value={form.body}
            onkeydown={onBodyKeydown}
            rows="3"
            maxlength="2000"
            placeholder="Add a note for the team, e.g. “Sent the reminder email on 18 Oct.”"
            class="{inputClass} resize-y"
            aria-invalid={form.errors.body ? 'true' : undefined}
        ></textarea>
        {#if form.errors.body}
            <p class="mt-1.5 text-xs font-medium text-rose-700">{form.errors.body}</p>
        {/if}
        <div class="mt-2 flex items-center justify-between gap-3">
            <p class="text-xs text-ink-400">
                {editorPrompt.name ? `Signed as ${editorPrompt.name}` : 'We will ask for your name first'}
            </p>
            <Button type="submit" variant="primary" size="sm" disabled={form.processing || !form.body.trim()}>
                {form.processing ? 'Adding…' : 'Add note'}
            </Button>
        </div>
    </form>

    <div class="mt-6 border-t border-line pt-5">
        {#if notes.length === 0}
            <p class="py-4 text-center text-sm text-ink-400">No notes yet.</p>
        {:else}
            <ol class="space-y-5">
                {#each notes as note (note.id)}
                    <li>
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm">
                                <span class="font-semibold text-ink-900">{note.author_name}</span>
                                <span class="ml-1.5 text-xs text-ink-400" title={new Date(note.created_at).toLocaleString()}>
                                    {formatRelative(note.created_at)}
                                </span>
                            </p>
                            {#if confirmingId !== note.id}
                                <button
                                    type="button"
                                    onclick={() => (confirmingId = note.id)}
                                    class="text-xs font-medium text-ink-400 transition-colors hover:text-rose-700"
                                >
                                    Delete
                                </button>
                            {/if}
                        </div>
                        <p class="mt-1 text-sm leading-relaxed break-words whitespace-pre-line text-ink-700">{note.body}</p>
                        {#if confirmingId === note.id}
                            <div class="mt-2 flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800">
                                <span class="flex-1">Delete this note for everyone?</span>
                                <Button size="sm" variant="ghost" onclick={() => (confirmingId = null)}>Keep</Button>
                                <Button size="sm" variant="danger-solid" disabled={deletingId === note.id} onclick={() => remove(note)}>
                                    {deletingId === note.id ? 'Deleting…' : 'Delete'}
                                </Button>
                            </div>
                        {/if}
                    </li>
                {/each}
            </ol>
        {/if}
    </div>
</Modal>
