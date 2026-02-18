export function parseJsonAttribute(element, attributeName, fallback = null) {
    if (!element) return fallback;

    const raw = element.getAttribute(attributeName);
    if (!raw || typeof raw !== 'string') return fallback;

    const decodeHtml = (value) => {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        return textarea.value;
    };

    const attempts = [
        raw,
        decodeHtml(raw),
        raw
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&amp;/g, '&'),
    ];

    for (const value of attempts) {
        const candidate = (value || '').trim();
        if (!candidate) continue;

        try {
            return JSON.parse(candidate);
        } catch {
            // continue
        }

        // Handle quoted payload edge case: "{...}"
        if (
            (candidate.startsWith('"') && candidate.endsWith('"')) ||
            (candidate.startsWith("'") && candidate.endsWith("'"))
        ) {
            const unwrapped = candidate.slice(1, -1);
            try {
                return JSON.parse(unwrapped);
            } catch {
                // continue
            }
        }
    }

    return fallback;
}
