import { day, item, session } from '@/test/fixtures';
import { describe, expect, it } from 'vitest';
import { applyFilters, emptyFilters, parseFilters, scopeDays, sessionLabel, toQueryString, type Filters } from './filters';

const opening = session({ kind: 'ceremony', number: null, journal: null, title: 'Opening Ceremony', spp_moderator: null, journal_moderator: null });
const climate = session({
    number: 1,
    journal: 'Journal of Climate Change Action (JCCA)',
    spp_moderator: 'Sara Ayman',
    journal_moderator: null,
    items: [
        item({ speaker_name: 'Prof. Šárka Nováková', speaker_status: 'confirmed', assets_status: 'ready' }),
        item({ speaker_name: 'Dr. Omar Fathy', speaker_status: 'not_attending', assets_status: 'pending', title: null }),
    ],
});
const materials = session({
    number: 2,
    journal: 'Sustainable Materials (SM)',
    spp_moderator: 'Mona Ali',
    items: [item({ speaker_name: 'Dr. Lina Saleh', emails: ['lina@example.org'] })],
});
const days = [day([opening, climate]), day([materials], { date: '2026-10-25' })];

function filters(overrides: Partial<Filters>): Filters {
    return { ...emptyFilters(), ...overrides };
}

function speakerNames(result: ReturnType<typeof applyFilters>): (string | null)[] {
    return result.flatMap((d) => d.sessions.flatMap((s) => s.items.map((i) => i.speaker_name)));
}

describe('query string', () => {
    it('round-trips filters through the URL', () => {
        const original = filters({ day: 1, session: climate.id, q: 'omar', speaker: 'pending', assets: 'ready', flag: 'at_risk' });

        expect(parseFilters(toQueryString(original), days)).toEqual(original);
    });

    it('drops unknown values and sessions outside the chosen day', () => {
        const parsed = parseFilters(`?day=1&session=${materials.id}&speaker=maybe&flag=x&day=9`, days);

        expect(parsed).toEqual(filters({ day: 1 }));
        expect(parseFilters('?day=5', days).day).toBeNull();
    });

    it('is empty without filters', () => {
        expect(toQueryString(emptyFilters())).toBe('');
    });
});

describe('applyFilters', () => {
    it('keeps everything without filters', () => {
        expect(applyFilters(days, emptyFilters())).toEqual(days);
    });

    it('narrows to a day and a session', () => {
        expect(applyFilters(days, filters({ day: 2 })).map((d) => d.id)).toEqual([days[1].id]);
        expect(speakerNames(applyFilters(days, filters({ session: climate.id })))).toEqual(['Prof. Šárka Nováková', 'Dr. Omar Fathy']);
    });

    it('searches speakers without caring about accents', () => {
        expect(speakerNames(applyFilters(days, filters({ q: 'sarka novakova' })))).toEqual(['Prof. Šárka Nováková']);
        expect(speakerNames(applyFilters(days, filters({ q: 'lina@example' })))).toEqual(['Dr. Lina Saleh']);
    });

    it('keeps the whole session when the search matches the session itself', () => {
        expect(speakerNames(applyFilters(days, filters({ q: 'sara ayman' })))).toEqual(['Prof. Šárka Nováková', 'Dr. Omar Fathy']);
        expect(applyFilters(days, filters({ q: 'opening' }))[0].sessions).toEqual([opening]);
    });

    it('filters by status and flag', () => {
        expect(speakerNames(applyFilters(days, filters({ speaker: 'confirmed' })))).toEqual(['Prof. Šárka Nováková']);
        expect(speakerNames(applyFilters(days, filters({ assets: 'pending' })))).toEqual(['Dr. Omar Fathy', 'Dr. Lina Saleh']);
        expect(speakerNames(applyFilters(days, filters({ flag: 'at_risk' })))).toEqual(['Dr. Omar Fathy']);
        expect(speakerNames(applyFilters(days, filters({ flag: 'topic_tbd' })))).toEqual(['Dr. Omar Fathy']);
    });

    it('lists whole sessions that miss a moderator', () => {
        const result = applyFilters(days, filters({ flag: 'missing_moderator' }));

        expect(result.flatMap((d) => d.sessions)).toEqual([climate]);
    });

    it('combines a search with a status filter', () => {
        expect(speakerNames(applyFilters(days, filters({ q: 'sara ayman', speaker: 'not_attending' })))).toEqual(['Dr. Omar Fathy']);
        expect(applyFilters(days, filters({ q: 'opening', speaker: 'confirmed' }))).toEqual([]);
    });
});

describe('scopeDays', () => {
    it('ignores status filters, so the summary still counts the whole scope', () => {
        expect(scopeDays(days, { day: 1, session: null })).toEqual([days[0]]);
        expect(scopeDays(days, { day: null, session: materials.id })[0].sessions).toEqual([materials]);
    });
});

describe('sessionLabel', () => {
    it('uses the journal abbreviation', () => {
        expect(sessionLabel(climate)).toBe('Session 1 · JCCA');
        expect(sessionLabel(opening)).toBe('Opening Ceremony');
    });
});
