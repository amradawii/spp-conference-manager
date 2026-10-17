import type { AgendaItem, ConferenceSession, Day } from '@/types/models';

export interface Summary {
    speakers: number;
    confirmed: number;
    notAttending: number;
    assetsTracked: number;
    assetsReady: number;
    atRisk: number;
    topicsTbd: number;
    sessionsMissingModerator: number;
}

export type MissingModerator = 'spp' | 'journal';

export function hasSpeaker(item: AgendaItem): boolean {
    return item.type !== 'segment';
}

export function tracksAssets(item: AgendaItem): boolean {
    return item.assets_status !== 'not_needed';
}

/** The speaker will not attend and IT has nothing to present in their place. */
export function isAtRisk(item: AgendaItem): boolean {
    return hasSpeaker(item) && item.speaker_status === 'not_attending' && item.assets_status === 'pending';
}

/** The speaker will not attend, so the IT host presents their assets instead. */
export function itPresents(item: AgendaItem): boolean {
    return hasSpeaker(item) && item.speaker_status === 'not_attending' && item.assets_status === 'ready';
}

export function isTopicTbd(item: AgendaItem): boolean {
    return hasSpeaker(item) && !item.title?.trim();
}

export function missingModerators(session: ConferenceSession): MissingModerator[] {
    if (session.kind !== 'session') {
        return [];
    }

    const missing: MissingModerator[] = [];
    if (!session.spp_moderator?.trim()) missing.push('spp');
    if (!session.journal_moderator?.trim()) missing.push('journal');

    return missing;
}

export function summarize(days: Day[]): Summary {
    const sessions = days.flatMap((day) => day.sessions);
    const items = sessions.flatMap((session) => session.items);
    const speakers = items.filter(hasSpeaker);
    const tracked = items.filter(tracksAssets);

    return {
        speakers: speakers.length,
        confirmed: speakers.filter((item) => item.speaker_status === 'confirmed').length,
        notAttending: speakers.filter((item) => item.speaker_status === 'not_attending').length,
        assetsTracked: tracked.length,
        assetsReady: tracked.filter((item) => item.assets_status === 'ready').length,
        atRisk: items.filter(isAtRisk).length,
        topicsTbd: items.filter(isTopicTbd).length,
        sessionsMissingModerator: sessions.filter((session) => missingModerators(session).length > 0).length,
    };
}
