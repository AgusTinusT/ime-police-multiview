// m:ss or h:mm:ss for clip timestamps.
export const formatClipTime = (totalSeconds) => {
    const s = Math.max(0, Math.floor(Number(totalSeconds) || 0));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = String(s % 60).padStart(2, "0");
    return h ? `${h}:${String(m).padStart(2, "0")}:${sec}` : `${m}:${sec}`;
};

// Accepts "90", "1:30" or "1:02:03"; returns seconds or null when invalid.
export const parseClipTime = (input) => {
    const str = String(input ?? "").trim();
    if (!str) return null;
    if (/^\d+$/.test(str)) return parseInt(str, 10);
    if (!/^\d+(:\d{1,2}){1,2}$/.test(str)) return null;
    return str.split(":").reduce((acc, part) => acc * 60 + parseInt(part, 10), 0);
};
