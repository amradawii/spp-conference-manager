import type { AssetsStatus, SpeakerStatus } from '@/types/models';

export type Tone = 'pending' | 'positive' | 'negative' | 'muted';

export const speakerStatusOptions: Record<SpeakerStatus, { label: string; tone: Tone }> = {
    pending: { label: 'Pending', tone: 'pending' },
    confirmed: { label: 'Confirmed', tone: 'positive' },
    not_attending: { label: 'Not attending', tone: 'negative' },
};

export const assetsStatusOptions: Record<AssetsStatus, { label: string; tone: Tone }> = {
    pending: { label: 'Pending', tone: 'pending' },
    ready: { label: 'Ready', tone: 'positive' },
    not_needed: { label: 'Not needed', tone: 'muted' },
};
