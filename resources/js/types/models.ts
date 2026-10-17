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
    spp_moderator: string | null;
    journal_moderator: string | null;
    updated_by: string | null;
    updated_at: string;
    items: AgendaItem[];
    notes: Note[];
}

export interface Day {
    id: number;
    date: string;
    title: string;
    time: TimeRange | null;
    utc_offsets: { conference: string; team: string };
    sessions: ConferenceSession[];
}

export interface Conference {
    id: number;
    name: string;
    /** Relative URL of the board, e.g. "/s/{token}". All write routes hang off it. */
    url: string;
    cities: { conference: string; team: string };
    days: Day[];
}
