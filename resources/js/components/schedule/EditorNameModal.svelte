<script lang="ts">
    import Button from '@/components/ui/Button.svelte';
    import FormField from '@/components/ui/FormField.svelte';
    import Modal from '@/components/ui/Modal.svelte';
    import { getBoard } from '@/lib/board';
    import { editorPrompt } from '@/lib/editor.svelte';
    import { inputClass } from '@/lib/ui';
    import { useForm } from '@inertiajs/svelte';
    import { untrack } from 'svelte';

    const board = getBoard();
    const form = useForm({ name: '' });

    $effect(() => {
        if (!editorPrompt.open) return;

        untrack(() => {
            form.name = editorPrompt.name ?? '';
            form.clearErrors();
        });
    });

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post(board.routes.editor, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => editorPrompt.saved(),
        });
    }
</script>

<Modal
    bind:open={editorPrompt.open}
    size="sm"
    title={editorPrompt.name ? 'Change your name' : 'Who is making changes?'}
    description="Your name appears next to every change and note, so the team knows who to ask. It is remembered on this device."
    onClose={() => editorPrompt.dismiss()}
>
    <form id="editor-name-form" onsubmit={submit}>
        <FormField label="Your name" for="editor-name" error={form.errors.name}>
            <!-- svelte-ignore a11y_autofocus -->
            <input
                id="editor-name"
                bind:value={form.name}
                class={inputClass}
                autocomplete="name"
                maxlength="60"
                placeholder="e.g. Yomna Hassan"
                aria-invalid={form.errors.name ? 'true' : undefined}
                autofocus
                required
            />
        </FormField>
    </form>

    {#snippet footer()}
        <Button variant="ghost" onclick={() => editorPrompt.dismiss()}>Cancel</Button>
        <Button variant="primary" type="submit" form="editor-name-form" disabled={form.processing || form.name.trim().length < 2}>
            {form.processing ? 'Saving…' : 'Continue'}
        </Button>
    {/snippet}
</Modal>
