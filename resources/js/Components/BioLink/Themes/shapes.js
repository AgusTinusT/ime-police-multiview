// Deterministic SVG shape generators for bio-link theme backdrops. Same input,
// same output, so backdrops render identically on every visit (and on SSR).

const round = (n) => Math.round(n * 10) / 10;

/** Small seeded PRNG (mulberry32). */
export function rng(seed) {
    let a = seed >>> 0;
    return () => {
        a = (a + 0x6d2b79f5) >>> 0;
        let t = a;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Regular star polygon centred on (cx, cy). */
export function starPath(cx, cy, points, outer, inner) {
    const step = Math.PI / points;
    let d = "";
    for (let i = 0; i < points * 2; i++) {
        const r = i % 2 === 0 ? outer : inner;
        const a = i * step - Math.PI / 2;
        d += `${i ? "L" : "M"}${round(cx + r * Math.cos(a))} ${round(cy + r * Math.sin(a))}`;
    }
    return `${d}Z`;
}

/** Tip positions of a star (for the ball tips of a sheriff badge). */
export function starTips(cx, cy, points, outer) {
    return Array.from({ length: points }, (_, i) => {
        const a = (i * 2 * Math.PI) / points - Math.PI / 2;
        return { x: round(cx + outer * Math.cos(a)), y: round(cy + outer * Math.sin(a)) };
    });
}

/** Rolling ridge line across `width`, closed down to `bottom`. Returns { d, points }. */
export function ridge({ width, base, amplitude, seed, bottom, step = 40 }) {
    const rand = rng(seed);
    const phase = rand() * Math.PI * 2;
    const points = [];
    for (let x = 0; x <= width; x += step) {
        const y = base - amplitude * (0.55 * Math.sin(x / 170 + phase) + 0.3 * Math.sin(x / 63 + phase * 2) + 0.15 * (rand() - 0.5));
        points.push({ x, y: round(y) });
    }
    const d = `M0 ${bottom}${points.map((p) => `L${p.x} ${p.y}`).join("")}L${width} ${bottom}Z`;
    return { d, points };
}

/** Pine trees standing on ridge points (every `every`-th point). */
export function pinesOn(points, { seed, every = 2, min = 26, max = 58 }) {
    const rand = rng(seed);
    let d = "";
    points.forEach((p, i) => {
        if (i % every !== 0 || rand() < 0.2) return;
        const h = min + rand() * (max - min);
        const w = h * 0.32;
        const x = p.x + (rand() - 0.5) * 20;
        const y = p.y + 4;
        // Three stacked tiers + trunk
        for (let t = 0; t < 3; t++) {
            const top = y - h + t * h * 0.28;
            const bottom = y - h * 0.18 - (2 - t) * h * 0.2;
            const half = w * (0.55 + t * 0.25);
            d += `M${round(x)} ${round(top)}L${round(x - half)} ${round(bottom)}L${round(x + half)} ${round(bottom)}Z`;
        }
        d += `M${round(x - 1.5)} ${round(y - h * 0.2)}h3v${round(h * 0.2)}h-3Z`;
    });
    return d;
}

/** Topographic contour rings around (cx, cy). Returns an array of path strings. */
export function contours({ cx, cy, rings, gap, seed }) {
    const rand = rng(seed);
    const p1 = rand() * 6, p2 = rand() * 6, p3 = rand() * 6;
    return Array.from({ length: rings }, (_, k) => {
        const base = gap * (k + 1);
        let d = "";
        for (let i = 0; i <= 72; i++) {
            const a = (i / 72) * Math.PI * 2;
            const r = base * (1 + 0.14 * Math.sin(3 * a + p1 + k * 0.35) + 0.07 * Math.sin(5 * a + p2 - k * 0.2) + 0.04 * Math.sin(9 * a + p3));
            d += `${i ? "L" : "M"}${round(cx + r * Math.cos(a))} ${round(cy + r * 0.72 * Math.sin(a))}`;
        }
        return `${d}Z`;
    });
}

/** City skyline blocks along the bottom of a `width` x `height` box. */
export function skyline({ width, height, seed }) {
    const rand = rng(seed);
    const blocks = [];
    let x = 0;
    while (x < width) {
        const w = 26 + rand() * 54;
        // Taller towers near the middle, like a downtown core
        const centre = 1 - Math.abs(x + w / 2 - width / 2) / (width / 2);
        const h = height * (0.18 + rand() * 0.35 + centre * 0.4);
        blocks.push({ x: round(x), w: round(w - 3), h: round(Math.min(h, height - 6)), mast: rand() < 0.18 });
        x += w;
    }
    return blocks;
}
