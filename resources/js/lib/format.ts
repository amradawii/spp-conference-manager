import type { Day } from '@/types/models';

const dayFormatter = new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });
const dayNumberFormatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', timeZone: 'UTC' });
const monthYearFormatter = new Intl.DateTimeFormat('en-GB', { month: 'long', year: 'numeric', timeZone: 'UTC' });

function parseDate(date: string): Date {
    return new Date(`${date}T00:00:00Z`);
}

/** "2026-10-24" → "Saturday 24 October" */
export function formatDay(date: string): string {
    return dayFormatter.format(parseDate(date));
}

/** ["2026-10-24", "2026-10-25"] → "24–25 October 2026" */
export function formatDateSpan(dates: string[]): string {
    if (dates.length === 0) return '';

    const first = parseDate(dates[0]);
    const last = parseDate(dates[dates.length - 1]);

    if (dates.length === 1) {
        return `${dayNumberFormatter.format(first)} ${monthYearFormatter.format(first)}`;
    }

    if (first.getUTCMonth() === last.getUTCMonth() && first.getUTCFullYear() === last.getUTCFullYear()) {
        return `${dayNumberFormatter.format(first)}–${dayNumberFormatter.format(last)} ${monthYearFormatter.format(last)}`;
    }

    return `${dayNumberFormatter.format(first)} ${monthYearFormatter.format(first)} – ${dayNumberFormatter.format(last)} ${monthYearFormatter.format(last)}`;
}

function offsetMinutes(offset: string): number {
    const match = /^([+-])(\d{2}):(\d{2})$/.exec(offset);
    if (!match) return 0;

    const minutes = Number(match[2]) * 60 + Number(match[3]);
    return match[1] === '-' ? -minutes : minutes;
}

/** "+02:00" → "UTC+2", "+05:30" → "UTC+5:30" */
export function formatUtcOffset(offset: string): string {
    const total = offsetMinutes(offset);
    const sign = total < 0 ? '−' : '+';
    const hours = Math.floor(Math.abs(total) / 60);
    const minutes = Math.abs(total) % 60;

    return `UTC${sign}${hours}${minutes ? `:${String(minutes).padStart(2, '0')}` : ''}`;
}

/** How many hours the team timezone is ahead of the conference timezone on that day. */
export function teamLeadHours(day: Day): number {
    return (offsetMinutes(day.utc_offsets.team) - offsetMinutes(day.utc_offsets.conference)) / 60;
}

export function pluralize(count: number, singular: string, plural = `${singular}s`): string {
    return `${count} ${count === 1 ? singular : plural}`;
}
