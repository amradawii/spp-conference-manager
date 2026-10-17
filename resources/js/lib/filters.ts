import type { AgendaItem, ConferenceSession, Day, SpeakerStatus } from '@/types/models';
import { normalize } from './search';
import { hasSpeaker, isAtRisk, isTopicTbd, missingModerators, tracksAssets } from './stats';

export type AssetsFilter = 'pending' | 'ready';
export type FlagFilter = 'at_risk' | 'topic_tbd' | 'missing_moderator';

export interface Filters {
    /** 1-based day number. */
    day: number | null;
    session: number | null;
    q: string;
    speaker: SpeakerStatus | null;
    assets: AssetsFilter | null;
    flag: FlagFilter | null;
}

export const speakerFilterValues: SpeakerStatus[] = ['pending', 'confirmed', 'not_attending'];
export const assetsFilterValues: AssetsFilter[] = ['pending', 'ready'];
export const flagFilterValues: FlagFilter[] = ['at_risk', 'topic_tbd', 'missing_moderator'];

export const flagLabels: Record<FlagFilter, string> = {
    at_risk: 'At risk',
    topic_tbd: 'Topic TBD',
    missing_moderator: 'Missing moderator',
};

export function emptyFilters(): Filters {
    return { day: null, session: null, q: '', speaker: null, assets: null, flag: null };
}

function pick<T extends string>(value: string | null, allowed: readonly T[]): T | null {
    return value !== null && (allowed as readonly string[]).includes(value) ? (value as T) : null;
}

export function parseFilters(search: string, days: Day[]): Filters {
    const params = new URLSearchParams(search);
    const filters = emptyFilters();

    const day = Number(params.get('day'));
    if (Number.isInteger(day) && day >= 1 && day <= days.length) {
        filters.day = day;
    }

    const sessionId = Number(params.get('session'));
    const allowedDays = filters.day ? [days[filters.day - 1]] : days;
    if (allowedDays.some((d) => d.sessions.some((s) => s.id === sessionId))) {
        filters.session = sessionId;
    }

    filters.q = (params.get('q') ?? '').slice(0, 100);
    filters.speaker = pick(params.get('speaker'), speakerFilterValues);
    filters.assets = pick(params.get('assets'), assetsFilterValues);
    filters.flag = pick(params.get('flag'), flagFilterValues);

    return filters;
}

export function toQueryString(filters: Filters): string {
    const params = new URLSearchParams();

    if (filters.day) params.set('day', String(filters.day));
    if (filters.session) params.set('session', String(filters.session));
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.speaker) params.set('speaker', filters.speaker);
    if (filters.assets) params.set('assets', filters.assets);
    if (filters.flag) params.set('flag', filters.flag);

    const query = params.toString();
    return query ? `?${query}` : '';
}

/** Filters that narrow down items (as opposed to choosing a day or session). */
export function hasItemCriteria(filters: Filters): boolean {
    return filters.q.trim() !== '' || hasStatusCriteria(filters);
}

function hasStatusCriteria(filters: Filters): boolean {
    return filters.speaker !== null || filters.assets !== null || filters.flag === 'at_risk' || filters.flag === 'topic_tbd';
}

export function isFiltering(filters: Filters): boolean {
    return filters.day !== null || filters.session !== null || filters.flag !== null || hasItemCriteria(filters);
}

/** Only the chosen day and session; used for the summary counters. */
export function scopeDays(days: Day[], filters: Pick<Filters, 'day' | 'session'>): Day[] {
    return days
        .filter((_, index) => filters.day === null || index + 1 === filters.day)
        .map((day) => (filters.session === null ? day : { ...day, sessions: day.sessions.filter((s) => s.id === filters.session) }))
        .filter((day) => day.sessions.length > 0);
}

function includesQuery(values: (string | null | undefined)[], query: string): boolean {
    return values.some((value) => value && normalize(value).includes(query));
}

function sessionMatchesQuery(session: ConferenceSession, query: string): boolean {
    return includesQuery([session.journal, session.title, session.spp_moderator, session.journal_moderator], query);
}

function itemMatchesQuery(item: AgendaItem, query: string): boolean {
    return includesQuery([item.speaker_name, item.title, item.label, item.speaker_role, item.affiliation, ...item.emails], query);
}

function itemPassesStatusFilters(item: AgendaItem, filters: Filters): boolean {
    if (filters.speaker && (!hasSpeaker(item) || item.speaker_status !== filters.speaker)) return false;
    if (filters.assets && (!tracksAssets(item) || item.assets_status !== filters.assets)) return false;
    if (filters.flag === 'at_risk' && !isAtRisk(item)) return false;
    if (filters.flag === 'topic_tbd' && !isTopicTbd(item)) return false;

    return true;
}

/**
 * The visible programme. A search that matches a session itself (journal,
 * title or a moderator) keeps the whole session, so searching "Sara" lists
 * every session she moderates.
 */
export function applyFilters(days: Day[], filters: Filters): Day[] {
    const query = normalize(filters.q.trim());
    const narrowsItems = hasItemCriteria(filters);

    return scopeDays(days, filters)
        .map((day) => ({
            ...day,
            sessions: day.sessions.flatMap((session) => {
                if (filters.flag === 'missing_moderator' && missingModerators(session).length === 0) return [];
                if (!narrowsItems) return [session];

                const sessionHit = query !== '' && sessionMatchesQuery(session, query);
                const items = session.items.filter(
                    (item) => itemPassesStatusFilters(item, filters) && (query === '' || sessionHit || itemMatchesQuery(item, query)),
                );

                // A matching plenary block without items (e.g. the closing ceremony) still shows.
                const keepEmptySession = sessionHit && !hasStatusCriteria(filters);
                return items.length > 0 || keepEmptySession ? [{ ...session, items }] : [];
            }),
        }))
        .filter((day) => day.sessions.length > 0);
}

export function countSpeakers(days: Day[]): number {
    return days.reduce((total, day) => total + day.sessions.reduce((sum, session) => sum + session.items.filter(hasSpeaker).length, 0), 0);
}

/** "Journal of Climate Change Action (JCCA)" → "JCCA"; two journals → "JAFS & ARI". */
export function sessionShortName(session: ConferenceSession): string {
    const abbreviations = [...(session.journal ?? '').matchAll(/\(([^)]+)\)/g)].map((match) => match[1]);
    if (abbreviations.length > 0) return abbreviations.join(' & ');

    return session.journal ?? session.title ?? 'Untitled';
}

export function sessionLabel(session: ConferenceSession): string {
    return session.kind === 'session' && session.number !== null ? `Session ${session.number} · ${sessionShortName(session)}` : (session.title ?? 'Plenary');
}
