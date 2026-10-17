import type { AgendaItem, ConferenceSession, Day } from '@/types/models';

let nextId = 1;

export function item(overrides: Partial<AgendaItem> = {}): AgendaItem {
    return {
        id: nextId++,
        type: 'keynote',
        label: 'Keynote 1',
        time: null,
        speaker_name: 'Prof. Test',
        speaker_role: null,
        affiliation: null,
        emails: [],
        title: 'A topic',
        speaker_status: 'pending',
        assets_status: 'pending',
        updated_by: null,
        updated_at: '2026-10-17T10:00:00+00:00',
        notes: [],
        ...overrides,
    };
}

export function session(overrides: Partial<ConferenceSession> = {}): ConferenceSession {
    return {
        id: nextId++,
        kind: 'session',
        number: 1,
        journal: 'Journal',
        title: 'Title',
        time: { start: '09:00', end: '10:00', team_start: '10:00', team_end: '11:00' },
        spp_moderator: 'Sara Ayman',
        journal_moderator: 'Prof. Editor',
        updated_by: null,
        updated_at: '2026-10-17T10:00:00+00:00',
        items: [],
        notes: [],
        ...overrides,
    };
}

export function day(sessions: ConferenceSession[], overrides: Partial<Day> = {}): Day {
    return {
        id: nextId++,
        date: '2026-10-24',
        title: 'Day',
        time: null,
        utc_offsets: { conference: '+02:00', team: '+03:00' },
        sessions,
        ...overrides,
    };
}
