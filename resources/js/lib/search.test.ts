import { describe, expect, it } from 'vitest';
import { highlightParts, normalize } from './search';

describe('normalize', () => {
    it('ignores case and accents', () => {
        expect(normalize('Šárka NOVÁKOVÁ')).toBe('sarka novakova');
    });
});

describe('highlightParts', () => {
    it('marks every match, keeping the original characters', () => {
        expect(highlightParts('Dr. Šárka and Sarka', 'sarka')).toEqual([
            { text: 'Dr. ', match: false },
            { text: 'Šárka', match: true },
            { text: ' and ', match: false },
            { text: 'Sarka', match: true },
        ]);
    });

    it('returns the text untouched without a query', () => {
        expect(highlightParts('Keynote 1', '  ')).toEqual([{ text: 'Keynote 1', match: false }]);
    });
});
