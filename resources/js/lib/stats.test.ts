import { day, item, session } from '@/test/fixtures';
import { describe, expect, it } from 'vitest';
import { isAtRisk, isTopicTbd, itPresents, missingModerators, summarize } from './stats';

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
