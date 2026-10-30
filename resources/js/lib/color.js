// Small colour helpers for themeable pages (WCAG contrast, safe accents).
// Hex input only (#rgb or #rrggbb); anything else returns null/falls back.

export function parseHex(hex) {
    const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec((hex || "").trim());
    if (!m) return null;
    const h = m[1].length === 3 ? m[1].replace(/./g, "$&$&") : m[1];
    return { r: parseInt(h.slice(0, 2), 16), g: parseInt(h.slice(2, 4), 16), b: parseInt(h.slice(4, 6), 16) };
}

export function toHex({ r, g, b }) {
    return `#${[r, g, b].map((v) => Math.round(v).toString(16).padStart(2, "0")).join("")}`;
}

function luminance(rgb) {
    const [r, g, b] = [rgb.r, rgb.g, rgb.b].map((v) => {
        const c = v / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(a, b) {
    const ca = parseHex(a);
    const cb = parseHex(b);
    if (!ca || !cb) return 1;
    const [hi, lo] = [luminance(ca), luminance(cb)].sort((x, y) => y - x);
    return (hi + 0.05) / (lo + 0.05);
}

export function mix(a, b, t) {
    const ca = parseHex(a);
    const cb = parseHex(b);
    if (!ca || !cb) return a;
    return toHex({ r: ca.r + (cb.r - ca.r) * t, g: ca.g + (cb.g - ca.g) * t, b: ca.b + (cb.b - ca.b) * t });
}

/** Black or white text, whichever reads better on `bg`. */
export function readableOn(bg) {
    return contrast("#ffffff", bg) >= contrast("#020617", bg) ? "#ffffff" : "#020617";
}

/** Lightens `fg` towards white until it reaches `min` contrast on the (dark) `bg`. */
export function ensureContrast(fg, bg, min = 4.5) {
    if (!parseHex(fg)) return fg;
    let out = fg;
    for (let t = 0.05; contrast(out, bg) < min && t <= 1; t += 0.05) {
        out = mix(fg, "#ffffff", t);
    }
    return out;
}

function hue(hex) {
    const c = parseHex(hex);
    if (!c) return { h: 0, s: 0 };
    const r = c.r / 255, g = c.g / 255, b = c.b / 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    let h = 0;
    if (d) {
        if (max === r) h = ((g - b) / d) % 6;
        else if (max === g) h = (b - r) / d + 2;
        else h = (r - g) / d + 4;
    }
    const l = (max + min) / 2;
    const s = d === 0 ? 0 : d / (1 - Math.abs(2 * l - 1));
    return { h: (h * 60 + 360) % 360, s };
}

/** True when a colour could be mistaken for the LIVE red. */
export function isNearLiveRed(hex) {
    const { h, s } = hue(hex);
    return s > 0.45 && (h <= 15 || h >= 345);
}
