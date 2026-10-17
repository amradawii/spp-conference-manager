/** The Clipboard API only exists on HTTPS (and localhost); fall back to a hidden textarea elsewhere. */
export async function copyText(text: string): Promise<void> {
    if (window.isSecureContext && navigator.clipboard) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.append(textarea);
    textarea.select();

    try {
        if (!document.execCommand('copy')) throw new Error('Copy command was refused.');
    } finally {
        textarea.remove();
    }
}
