import type { AssetsStatus, SpeakerStatus } from '@/types/models';

export type Tone = 'pending' | 'positive' | 'negative' | 'muted';

interface StatusOption {
    label: string;
    tone: Tone;
    hint: string;
}

export const speakerStatusOptions: Record<SpeakerStatus, StatusOption> = {
    pending: { label: 'Pending', tone: 'pending', hint: 'Not confirmed yet' },
    confirmed: { label: 'Confirmed', tone: 'positive', hint: 'Will present live' },
    not_attending: { label: 'Not attending', tone: 'negative', hint: 'The IT host presents their assets' },
};

export const assetsStatusOptions: Record<AssetsStatus, StatusOption> = {
    pending: { label: 'Pending', tone: 'pending', hint: 'Not received yet' },
    ready: { label: 'Ready', tone: 'positive', hint: 'Received and shared with the IT hosts' },
    not_needed: { label: 'Not needed', tone: 'muted', hint: 'Nothing to present' },
};
