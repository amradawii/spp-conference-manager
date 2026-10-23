export const eventLogo = { src: '/icspp-2026-logo.png', alt: 'ICSPP 2026: Materials for Sustainable Development and Advanced Applications' };

export const inputClass =
    'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-ink-900 ring-1 ring-line-strong ring-inset placeholder:text-ink-400 hover:ring-ink-300 focus:ring-2 focus:ring-ink-700 focus:outline-none aria-invalid:ring-rose-400';

/** Columns shared by session headers and item rows: time · details · status (the read-only view has no status column). */
export function rowColumns(readonly: boolean): string {
    return readonly
        ? 'md:grid-cols-[12.5rem_minmax(0,1fr)] print:grid-cols-[12.5rem_minmax(0,1fr)]'
        : 'md:grid-cols-[12.5rem_minmax(0,1fr)_15rem] print:grid-cols-[12.5rem_minmax(0,1fr)_12rem]';
}
