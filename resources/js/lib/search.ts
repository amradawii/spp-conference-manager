/** Lowercase and strip accents, so "Würzburg" matches "wurzburg". */
export function normalize(text: string): string {
    return text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

export interface TextPart {
    text: string;
    match: boolean;
}

/**
 * Split text into matching and non-matching parts, comparing accent- and
 * case-insensitively while keeping the original characters for display.
 */
export function highlightParts(text: string, query: string): TextPart[] {
    const needle = normalize(query.trim());
    if (needle === '' || text === '') {
        return [{ text, match: false }];
    }

    let haystack = '';
    const originalIndex: number[] = [];
    for (let i = 0; i < text.length; i++) {
        for (const char of normalize(text[i])) {
            haystack += char;
            originalIndex.push(i);
        }
    }

    const parts: TextPart[] = [];
    let cursor = 0;
    let from = 0;
    let found = haystack.indexOf(needle, from);

    while (found !== -1) {
        const start = originalIndex[found];
        const end = originalIndex[found + needle.length - 1] + 1;

        if (start > cursor) parts.push({ text: text.slice(cursor, start), match: false });
        parts.push({ text: text.slice(start, end), match: true });

        cursor = end;
        from = found + needle.length;
        found = haystack.indexOf(needle, from);
    }

    if (cursor < text.length) parts.push({ text: text.slice(cursor), match: false });

    return parts;
}
