/** URLs of the write endpoints, all relative to the board URL ("/s/{token}"). */
export function createRoutes(base: string) {
    return {
        board: base,
        editor: `${base}/editor`,
        itemStatus: (itemId: number) => `${base}/items/${itemId}/status`,
        item: (itemId: number) => `${base}/items/${itemId}`,
        sessionItems: (sessionId: number) => `${base}/sessions/${sessionId}/items`,
        sessions: `${base}/sessions`,
        session: (sessionId: number) => `${base}/sessions/${sessionId}`,
        notes: `${base}/notes`,
        note: (noteId: number) => `${base}/notes/${noteId}`,
    };
}

export type Routes = ReturnType<typeof createRoutes>;
