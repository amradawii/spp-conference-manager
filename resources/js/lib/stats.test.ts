import type { AgendaItem, ConferenceSession, Day } from '@/types/models';
import { describe, expect, it } from 'vitest';
import { isAtRisk, isTopicTbd, itPresents, missingModerators, summarize } from './stats';

function item(overrides: Partial<AgendaItem> = {}): AgendaItem {
    return {
        id: Math.random(),
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

function session(overrides: Partial<ConferenceSession> = {}): ConferenceSession {
    return {
        id: Math.random(),
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

function day(sessions: ConferenceSession[]): Day {
    return {
        id: 1,
        date: '2026-10-24',
        title: 'Day',
        time: null,
        utc_offsets: { conference: '+02:00', team: '+03:00' },
        sessions,
    };
}

describe('item flags', () => {
    it('flags an absent speaker without assets as at risk', () => {
        expect(isAtRisk(item({ speaker_status: 'not_attending', assets_status: 'pending' }))).toBe(true);
        expect(isAtRisk(item({ speaker_status: 'not_attending', assets_status: 'ready' }))).toBe(false);
        expect(isAtRisk(item({ speaker_status: 'pending', assets_status: 'pending' }))).toBe(false);
    });

    it('marks IT as presenter when an absent speaker has assets ready', () => {
        expect(itPresents(item({ speaker_status: 'not_attending', assets_status: 'ready' }))).toBe(true);
        expect(itPresents(item({ speaker_status: 'confirmed', assets_status: 'ready' }))).toBe(false);
    });

    it('treats a missing or blank topic as TBD, but never for segments', () => {
        expect(isTopicTbd(item({ title: null }))).toBe(true);
        expect(isTopicTbd(item({ title: '  ' }))).toBe(true);
        expect(isTopicTbd(item({ title: 'Real topic' }))).toBe(false);
        expect(isTopicTbd(item({ type: 'segment', title: null }))).toBe(false);
    });
});

describe('missingModerators', () => {
    it('lists the empty moderator roles of a numbered session', () => {
        expect(missingModerators(session({ spp_moderator: null, journal_moderator: '' }))).toEqual(['spp', 'journal']);
        expect(missingModerators(session())).toEqual([]);
    });

    it('ignores ceremonies', () => {
        expect(missingModerators(session({ kind: 'ceremony', spp_moderator: null, journal_moderator: null }))).toEqual([]);
    });
});

describe('summarize', () => {
    it('counts speakers, assets and problems across days', () => {
        const summary = summarize([
            day([
                session({
                    journal_moderator: null,
                    items: [
                        item({ speaker_status: 'confirmed', assets_status: 'ready' }),
                        item({ speaker_status: 'not_attending', assets_status: 'pending' }),
                        item({ title: null }),
                        item({ type: 'segment', speaker_name: null, assets_status: 'not_needed' }),
                    ],
                }),
            ]),
            day([session({ kind: 'ceremony', spp_moderator: null, journal_moderator: null })]),
        ]);

        expect(summary).toEqual({
            speakers: 3,
            confirmed: 1,
            notAttending: 1,
            assetsTracked: 3,
            assetsReady: 1,
            atRisk: 1,
            topicsTbd: 1,
            sessionsMissingModerator: 1,
        });
    });
});
