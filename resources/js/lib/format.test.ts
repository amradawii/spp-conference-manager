import type { Day } from '@/types/models';
import { describe, expect, it } from 'vitest';
import { formatDateSpan, formatDay, formatUtcOffset, pluralize, teamLeadHours } from './format';

function dayWithOffsets(conference: string, team: string): Day {
    return { id: 1, date: '2026-10-24', title: '', time: null, utc_offsets: { conference, team }, sessions: [] };
}

describe('format', () => {
    it('formats a conference day without shifting the date', () => {
        expect(formatDay('2026-10-24')).toBe('Saturday 24 October');
    });

    it('formats date spans', () => {
        expect(formatDateSpan(['2026-10-24', '2026-10-25'])).toBe('24–25 October 2026');
        expect(formatDateSpan(['2026-10-24'])).toBe('24 October 2026');
        expect(formatDateSpan(['2026-10-31', '2026-11-01'])).toBe('31 October 2026 – 1 November 2026');
    });

    it('formats UTC offsets', () => {
        expect(formatUtcOffset('+02:00')).toBe('UTC+2');
        expect(formatUtcOffset('+05:30')).toBe('UTC+5:30');
        expect(formatUtcOffset('-03:00')).toBe('UTC−3');
    });

    it('computes how far ahead the team is on each side of the clock change', () => {
        expect(teamLeadHours(dayWithOffsets('+02:00', '+03:00'))).toBe(1);
        expect(teamLeadHours(dayWithOffsets('+01:00', '+03:00'))).toBe(2);
    });

    it('pluralizes', () => {
        expect(pluralize(1, 'note')).toBe('1 note');
        expect(pluralize(3, 'note')).toBe('3 notes');
    });
});
