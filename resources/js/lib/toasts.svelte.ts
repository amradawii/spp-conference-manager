export interface Toast {
    id: number;
    message: string;
    tone: 'neutral' | 'error';
}

class Toasts {
    items = $state<Toast[]>([]);
    #nextId = 1;

    push(message: string, tone: Toast['tone'] = 'neutral'): void {
        const id = this.#nextId++;
        this.items = [...this.items.slice(-2), { id, message, tone }];
        setTimeout(() => this.dismiss(id), tone === 'error' ? 6000 : 3000);
    }

    dismiss(id: number): void {
        this.items = this.items.filter((toast) => toast.id !== id);
    }
}

export const toasts = new Toasts();
