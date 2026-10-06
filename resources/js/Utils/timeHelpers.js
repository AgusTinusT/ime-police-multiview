/**
 * Time and Stream Sorting Utilities
 */

/**
 * Parses a relative time string (e.g., "2 jam lalu", "5 mins ago", "baru saja", "live")
 * into total seconds for sorting comparisons.
 *
 * @param {string} str - Relative time string or timestamp text
 * @returns {number} Time equivalent in seconds (lower means more recent)
 */
export const parseRelativeTimeToSeconds = (str) => {
    if (!str) return 999999999;
    const text = String(str).toLowerCase().trim();
    if (
        text.includes("baru saja") ||
        text.includes("just now") ||
        text.includes("live") ||
        text.includes("sedang tayang")
    ) {
        return 0;
    }

    const numMatch = text.match(/(\d+)/);
    const num = numMatch ? parseInt(numMatch[1], 10) : 1;
    const isIndonesian = text.includes("lalu");

    if (
        text.includes("tahun") ||
        text.includes("year") ||
        text.match(/\b\d+\s*(?:y|thn|th)\b/)
    ) {
        return num * 31536000;
    }
    if (
        text.includes("bulan") ||
        text.includes("month") ||
        text.match(/\b\d+\s*(?:mo|bln)\b/)
    ) {
        return num * 2592000;
    }
    if (
        text.includes("minggu") ||
        text.includes("week") ||
        text.match(/\b\d+\s*w\b/)
    ) {
        return num * 604800;
    }
    if (
        text.includes("hari") ||
        text.includes("day") ||
        (isIndonesian && text.match(/\b\d+\s*h\b/)) ||
        (!isIndonesian && text.match(/\b\d+\s*d\b/))
    ) {
        return num * 86400;
    }
    if (
        text.includes("jam") ||
        text.includes("hour") ||
        (isIndonesian && text.match(/\b\d+\s*j\b/)) ||
        (!isIndonesian && text.match(/\b\d+\s*h\b/))
    ) {
        return num * 3600;
    }
    if (
        text.includes("menit") ||
        text.includes("minute") ||
        text.includes("min") ||
        text.match(/\b\d+\s*m\b/)
    ) {
        return num * 60;
    }
    if (
        text.includes("detik") ||
        text.includes("second") ||
        text.includes("sec") ||
        text.match(/\b\d+\s*s\b/)
    ) {
        return num;
    }

    return 999999999;
};

/**
 * Sorts streams helper:
 * 1) LIVE status first
 * 2) Video streams/replays over static cards
 * 3) Recency timestamp (newest stream/replay first)
 *
 * @param {Array} streamList - List of stream objects
 * @returns {Array} Sorted stream objects
 */
export const sortStreamsByStatusAndRecency = (streamList) => {
    if (!Array.isArray(streamList)) return [];
    return [...streamList].sort((a, b) => {
        if (a.status === "LIVE" && b.status !== "LIVE") return -1;
        if (b.status === "LIVE" && a.status !== "LIVE") return 1;

        if (!a.is_officer_card && b.is_officer_card) return -1;
        if (a.is_officer_card && !b.is_officer_card) return 1;

        const timeA = parseRelativeTimeToSeconds(
            a.streamed_at || a.incident_code,
        );
        const timeB = parseRelativeTimeToSeconds(
            b.streamed_at || b.incident_code,
        );
        if (timeA !== timeB) {
            return timeA - timeB;
        }

        return 0;
    });
};
