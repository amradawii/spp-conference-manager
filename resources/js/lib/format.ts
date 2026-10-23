import type { Day, TimeRange } from '@/types/models';

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

const shortDayFormatter = new Intl.DateTimeFormat('en-GB', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' });

/** "2026-10-24" → "Sat 24 Oct" */
export function formatShortDay(date: string): string {
    return shortDayFormatter.format(parseDate(date));
}

const relativeFormatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto', style: 'short' });
const clockFormatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', hour12: true });

/** "just now", "5 min ago", "3 hr ago", "yesterday", or "17 Oct, 10:42" for anything older. */
export function formatRelative(iso: string, now: Date = new Date()): string {
    const seconds = Math.round((new Date(iso).getTime() - now.getTime()) / 1000);
    const abs = Math.abs(seconds);

    if (abs < 45) return 'just now';
    if (abs < 3600) return relativeFormatter.format(Math.round(seconds / 60), 'minute');
    if (abs < 86400) return relativeFormatter.format(Math.round(seconds / 3600), 'hour');
    if (abs < 172800) return relativeFormatter.format(Math.round(seconds / 86400), 'day');

    return clockFormatter.format(new Date(iso));
}

/** "16:30" → "04:30 PM". Times travel as 24-hour "HH:MM"; only the display uses AM/PM. */
export function formatClock(time: string): string {
    const match = /^(\d{1,2}):(\d{2})/.exec(time);
    if (!match) return time;

    const hours = Number(match[1]);
    const hours12 = hours % 12 === 0 ? 12 : hours % 12;
    return `${String(hours12).padStart(2, '0')}:${match[2]} ${hours < 12 ? 'AM' : 'PM'}`;
}

/** "10:00", "16:30" → "10:00 AM – 04:30 PM" */
export function formatClockRange(start: string, end: string): string {
    return `${formatClock(start)} – ${formatClock(end)}`;
}

/** "09:30" shifted by 2 hours → "11:30" (wraps around midnight). */
export function isSameTimeRange(a: TimeRange | null | undefined, b: TimeRange | null | undefined): boolean {
    return !!a && !!b && a.start === b.start && a.end === b.end;
}

export function shiftTime(time: string, hours: number): string {
    const match = /^(\d{1,2}):(\d{2})/.exec(time);
    if (!match) return '';

    const minutes = (((Number(match[1]) * 60 + Number(match[2]) + Math.round(hours * 60)) % 1440) + 1440) % 1440;
    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
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
