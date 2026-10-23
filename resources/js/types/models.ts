export type SpeakerStatus = 'pending' | 'confirmed' | 'not_attending';
export type AssetsStatus = 'pending' | 'ready' | 'not_needed';
export type AgendaItemType = 'address' | 'keynote' | 'oral' | 'segment';
export type SessionKind = 'session' | 'ceremony';

/** Times as HH:MM in the conference timezone (start/end) and the team timezone (team_start/team_end). */
export interface TimeRange {
    start: string;
    end: string;
    team_start: string;
    team_end: string;
}

export interface Note {
    id: number;
    author_name: string;
    body: string;
    created_at: string;
}

export interface AgendaItem {
    id: number;
    type: AgendaItemType;
    label: string | null;
    time: TimeRange | null;
    speaker_name: string | null;
    speaker_role: string | null;
    affiliation: string | null;
    emails: string[];
    title: string | null;
    speaker_status: SpeakerStatus;
    assets_status: AssetsStatus;
    updated_by: string | null;
    updated_at: string;
    notes: Note[];
}

export interface ConferenceSession {
    id: number;
    kind: SessionKind;
    number: number | null;
    journal: string | null;
    title: string | null;
    time: TimeRange;
    chair: string | null;
    co_chair: string | null;
    updated_by: string | null;
    updated_at: string;
    items: AgendaItem[];
    notes: Note[];
}

export interface Day {
    id: number;
    date: string;
    title: string;
    zoom_url: string | null;
    note: string | null;
    time: TimeRange | null;
    utc_offsets: { conference: string; team: string };
    sessions: ConferenceSession[];
}

/**
 * The read-only programme ("/") receives the same shapes without the internal fields:
 * statuses, notes, updated_by/updated_at and public_url. Components skip them when `board.readonly`.
 */
export interface Conference {
    id: number;
    name: string;
    /** Relative URL of the board: "/s/{token}" for the team (all write routes hang off it), "/" for the read-only programme. */
    url: string;
    /** The read-only programme as a downloadable PDF. */
    pdf_url: string;
    /** Absolute URL of the read-only programme. Team board only. */
    public_url?: string;
    cities: { conference: string; team: string };
    days: Day[];
}
