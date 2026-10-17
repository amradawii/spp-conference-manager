import { page } from '@inertiajs/svelte';
import type { Errors } from '@inertiajs/core';

/**
 * Every change is signed with the editor's name. The first time someone
 * changes anything we ask for it, then carry on with what they were doing.
 */
class EditorPrompt {
    open = $state(false);
    #pending: (() => void) | null = null;

    get name(): string | null {
        return page.props.editor?.name ?? null;
    }

    require(action: () => void): void {
        if (this.name) {
            action();
            return;
        }

        this.#pending = action;
        this.open = true;
    }

    change(): void {
        this.#pending = null;
        this.open = true;
    }

    saved(): void {
        const action = this.#pending;
        this.#pending = null;
        this.open = false;
        action?.();
    }

    dismiss(): void {
        this.#pending = null;
        this.open = false;
    }

    /** The server refused because the name cookie is gone: ask again, then retry. */
    recover(errors: Errors, retry: () => void): boolean {
        if (!errors.editor_name) return false;

        this.#pending = retry;
        this.open = true;
        return true;
    }
}

export const editorPrompt = new EditorPrompt();
