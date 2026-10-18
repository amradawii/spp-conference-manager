import type { AgendaItem, Conference, ConferenceSession, Day } from '@/types/models';
import { getContext, setContext } from 'svelte';
import type { Routes } from './routes';

export type NotesTarget = { type: 'session' | 'item'; id: number };

/** Everything deep components need from the board page, without prop drilling. */
export interface Board {
    /** The public programme: no statuses, notes or editing. */
    readonly readonly: boolean;
    readonly routes: Routes;
    readonly cities: Conference['cities'];
    readonly query: string;
    readonly isFiltering: boolean;
    openNotes(target: NotesTarget): void;
    addSession(day: Day): void;
    editSession(session: ConferenceSession): void;
    addItem(session: ConferenceSession): void;
    editItem(item: AgendaItem): void;
}

const key = Symbol('board');

export function setBoard(board: Board): void {
    setContext(key, board);
}

export function getBoard(): Board {
    return getContext<Board>(key);
}

export interface SessionLocation {
    day: Day;
    dayNumber: number;
    session: ConferenceSession;
}

export function locateSession(days: Day[], id: number): SessionLocation | undefined {
    for (const [index, day] of days.entries()) {
        const session = day.sessions.find((s) => s.id === id);
        if (session) return { day, dayNumber: index + 1, session };
    }
}

export function locateItem(days: Day[], id: number): (SessionLocation & { item: AgendaItem }) | undefined {
    for (const [index, day] of days.entries()) {
        for (const session of day.sessions) {
            const item = session.items.find((i) => i.id === id);
            if (item) return { day, dayNumber: index + 1, session, item };
        }
    }
}
