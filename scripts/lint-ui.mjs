#!/usr/bin/env node
import { execSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";

const ROOT = process.cwd();
const JS_ROOT = path.resolve(ROOT, "resources/js");

const args = process.argv.slice(2);
const flagAll = args.includes("--all");
const flagReport = args.includes("--report");
const targetArgs = args.filter((a) => !a.startsWith("--"));

function toPosix(p) {
    return p.split(path.sep).join("/");
}

function relPosix(absPath) {
    return toPosix(path.relative(ROOT, absPath));
}

function isIgnoredFile(absPath) {
    const rel = relPosix(absPath);
    if (!rel.endsWith(".vue")) return true;
    if (rel.startsWith("resources/js/Components/ui/")) return true;
    return false;
}

function walkVueFiles(dir) {
    const results = [];
    if (!fs.existsSync(dir)) return results;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            results.push(...walkVueFiles(full));
        } else if (entry.isFile() && entry.name.endsWith(".vue")) {
            if (!isIgnoredFile(full)) {
                results.push(full);
            }
        }
    }
    return results;
}

function getChangedVueFiles() {
    const files = new Set();
    const commands = [
        "git diff --name-only main",
        "git diff --name-only HEAD",
        "git diff --name-only --cached",
        "git ls-files --others --exclude-standard",
    ];
    for (const cmd of commands) {
        try {
            const out = execSync(cmd, { encoding: "utf8", stdio: ["ignore", "pipe", "ignore"] });
            for (const line of out.split(/\r?\n/)) {
                const trimmed = line.trim();
                if (!trimmed) continue;
                const abs = path.resolve(ROOT, trimmed);
                if (fs.existsSync(abs) && !isIgnoredFile(abs)) {
                    files.add(abs);
                }
            }
        } catch {
            // ignore git command error
        }
    }
    return Array.from(files).sort();
}

function resolveTargets() {
    if (flagAll) {
        return walkVueFiles(JS_ROOT).sort();
    }
    if (targetArgs.length > 0) {
        const collected = new Set();
        for (const t of targetArgs) {
            const abs = path.resolve(ROOT, t);
            if (!fs.existsSync(abs)) continue;
            const stat = fs.statSync(abs);
            if (stat.isDirectory()) {
                for (const f of walkVueFiles(abs)) {
                    collected.add(f);
                }
            } else if (stat.isFile() && !isIgnoredFile(abs)) {
                collected.add(abs);
            }
        }
        return Array.from(collected).sort();
    }
    return getChangedVueFiles();
}

function maskPreservingNewlines(text, regex) {
    return text.replace(regex, (match) => match.replace(/[^\r\n]/g, " "));
}

function getLineNumber(source, index) {
    let line = 1;
    for (let i = 0; i < index; i++) {
        if (source.charCodeAt(i) === 10) line++;
    }
    return line;
}

const RAW_PALETTE_RE =
    /\b(?:[a-z0-9-]+:)*(?:bg|text|border|ring|outline|from|via|to|fill|stroke|divide|placeholder|shadow|decoration|accent|caret)-(?:slate|gray|zinc|neutral|stone|red|rose|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink)-\d{2,3}(?:\/\d+)?\b/g;

const WHITE_BLACK_RE =
    /\b(?:[a-z0-9-]+:)*(?:bg|text|border)-(?:white|black)(?:\/\d+)?\b/g;

const ARBITRARY_COLOR_RE =
    /\b(?:[a-z0-9-]+:)*[a-z-]+-\[(?:#|rgb|hsl|oklch)[^\]]*\]/gi;

const ARBITRARY_TEXT_SIZE_RE =
    /\b(?:[a-z0-9-]+:)*text-\[\d+(?:\.\d+)?(?:px|rem)\]/g;

const FONT_BLACK_RE = /\b(?:[a-z0-9-]+:)*font-black\b/g;

const TACTICAL_FRAME_CLASS_RE = /\b(?:tactical-frame|tactical-frame-inner)\b/g;
const CLIP_PATH_RE = /\bclip-path\b|\[clip-path:[^\]]+\]/g;

const BROWSER_DIALOG_RE = /\b(?:window\.)?(?:confirm|prompt|alert)\s*\(/g;

function extractClassRegions(content) {
    const regions = [];

    // 1. HTML attributes: class="...", :class="...", v-bind:class="..."
    const attrRe = /(?:^|\s)(?:v-bind:|:)?class\s*=\s*(?:"([^"]*)"|'([^']*)')/gm;
    let m;
    while ((m = attrRe.exec(content)) !== null) {
        const val = m[1] !== undefined ? m[1] : m[2];
        const valOffset = m.index + m[0].lastIndexOf(val);
        regions.push({ text: val, offset: valOffset });
    }

    // 2. cn(...) calls anywhere in file
    const cnRe = /\bcn\s*\(/g;
    while ((m = cnRe.exec(content)) !== null) {
        const start = m.index + m[0].length;
        let depth = 1;
        let i = start;
        while (i < content.length && depth > 0) {
            const ch = content[i];
            if (ch === "(") depth++;
            else if (ch === ")") depth--;
            i++;
        }
        const inner = content.slice(start, i - 1);
        regions.push({ text: inner, offset: start });
    }

    // 3. String literals inside <script> that contain utility classes
    const scriptRe = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
    while ((m = scriptRe.exec(content)) !== null) {
        const scriptBody = m[1];
        const scriptOffset = m.index + m[0].indexOf(scriptBody);
        const strRe = /(?:"([^"\r\n]*)"|'([^'\r\n]*)'|`([^`]*)`)/g;
        let sm;
        while ((sm = strRe.exec(scriptBody)) !== null) {
            const strVal = sm[1] ?? sm[2] ?? sm[3] ?? "";
            const strOffset = scriptOffset + sm.index + 1;
            regions.push({ text: strVal, offset: strOffset });
        }
    }

    return regions;
}

function lintFile(absPath) {
    const rel = relPosix(absPath);
    const raw = fs.readFileSync(absPath, "utf8");

    let working = raw;
    // Ignore <pre>...</pre> inside Pages/Dev/
    if (rel.startsWith("resources/js/Pages/Dev/")) {
        working = maskPreservingNewlines(working, /<pre\b[\s\S]*?<\/pre>/gi);
    }
    // Mask HTML comments
    working = maskPreservingNewlines(working, /<!--[\s\S]*?-->/g);

    const findings = [];
    const seen = new Set();

    function addFinding(line, rule, snippet, severity = "error") {
        const key = `${line}:${rule}:${snippet}`;
        if (seen.has(key)) return;
        seen.add(key);
        findings.push({ file: rel, line, rule, snippet, severity });
    }

    const regions = extractClassRegions(working);

    for (const region of regions) {
        const { text, offset } = region;

        for (const match of text.matchAll(RAW_PALETTE_RE)) {
            const line = getLineNumber(working, offset + match.index);
            addFinding(line, "raw-palette", match[0]);
        }

        for (const match of text.matchAll(WHITE_BLACK_RE)) {
            const token = match[0];
            const line = getLineNumber(working, offset + match.index);
            const hasPrimaryOrLive = /\bbg-(?:live|primary)\b/.test(text);
            if (token === "text-white" && hasPrimaryOrLive) {
                addFinding(line, "raw-palette-warn", token, "warn");
            } else {
                addFinding(line, "raw-palette", token);
            }
        }

        for (const match of text.matchAll(ARBITRARY_COLOR_RE)) {
            const line = getLineNumber(working, offset + match.index);
            addFinding(line, "arbitrary-color", match[0]);
        }

        for (const match of text.matchAll(ARBITRARY_TEXT_SIZE_RE)) {
            const line = getLineNumber(working, offset + match.index);
            addFinding(line, "arbitrary-text-size", match[0]);
        }

        for (const match of text.matchAll(FONT_BLACK_RE)) {
            const line = getLineNumber(working, offset + match.index);
            addFinding(line, "font-black", match[0]);
        }

        if (rel !== "resources/js/Components/tactical/TacticalFrame.vue") {
            for (const match of text.matchAll(TACTICAL_FRAME_CLASS_RE)) {
                const line = getLineNumber(working, offset + match.index);
                addFinding(line, "tactical-frame-direct", match[0]);
            }
            for (const match of text.matchAll(CLIP_PATH_RE)) {
                const line = getLineNumber(working, offset + match.index);
                addFinding(line, "tactical-frame-direct", match[0]);
            }
        }
    }

    // Rule 6: clip-path in inline style="..." or <style> blocks outside TacticalFrame.vue
    if (rel !== "resources/js/Components/tactical/TacticalFrame.vue") {
        const styleAttrRe = /(?:^|\s)(?:v-bind:|:)?style\s*=\s*(?:"([^"]*)"|'([^']*)')/gm;
        let stm;
        while ((stm = styleAttrRe.exec(working)) !== null) {
            const val = stm[1] !== undefined ? stm[1] : stm[2];
            const valOffset = stm.index + stm[0].lastIndexOf(val);
            for (const match of val.matchAll(CLIP_PATH_RE)) {
                const line = getLineNumber(working, valOffset + match.index);
                addFinding(line, "tactical-frame-direct", match[0]);
            }
        }
        const styleBlockRe = /<style\b[^>]*>([\s\S]*?)<\/style>/gi;
        while ((stm = styleBlockRe.exec(working)) !== null) {
            const body = stm[1];
            const bodyOffset = stm.index + stm[0].indexOf(body);
            for (const match of body.matchAll(CLIP_PATH_RE)) {
                const line = getLineNumber(working, bodyOffset + match.index);
                addFinding(line, "tactical-frame-direct", match[0]);
            }
        }
    }

    // Rule 5: confirm( / prompt( / alert( in <script>
    const scriptRe = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
    let sm;
    while ((sm = scriptRe.exec(working)) !== null) {
        let scriptBody = sm[1];
        const scriptOffset = sm.index + sm[0].indexOf(scriptBody);
        // Mask JS comments in script
        scriptBody = maskPreservingNewlines(scriptBody, /\/\*[\s\S]*?\*\//g);
        scriptBody = maskPreservingNewlines(scriptBody, /\/\/[^\r\n]*/g);

        for (const match of scriptBody.matchAll(BROWSER_DIALOG_RE)) {
            const line = getLineNumber(working, scriptOffset + match.index);
            addFinding(line, "browser-dialog", match[0].trim());
        }
    }

    findings.sort((a, b) => a.line - b.line);
    return findings;
}

const files = resolveTargets();
let totalErrors = 0;
let totalWarnings = 0;
const fileCounts = [];
const folderCounts = new Map();

for (const file of files) {
    const findings = lintFile(file);
    const errors = findings.filter((f) => f.severity === "error");
    const warnings = findings.filter((f) => f.severity === "warn");

    totalErrors += errors.length;
    totalWarnings += warnings.length;

    if (findings.length > 0) {
        const rel = relPosix(file);
        fileCounts.push({ file: rel, errors: errors.length, warnings: warnings.length });

        const parts = rel.split("/");
        const folder = parts.length >= 3 ? parts.slice(0, 3).join("/") : path.posix.dirname(rel);
        folderCounts.set(folder, (folderCounts.get(folder) || 0) + errors.length);

        if (!flagReport) {
            for (const f of findings) {
                console.log(`${f.file}:${f.line}  [${f.rule}]  ${f.snippet}`);
            }
        }
    }
}

if (flagReport) {
    console.log("=== UI Lint Report (per file) ===");
    for (const item of fileCounts) {
        console.log(`${String(item.errors).padStart(5, " ")}  ${item.file}`);
    }
    console.log("\n=== UI Lint Summary (per folder) ===");
    for (const [folder, count] of folderCounts.entries()) {
        console.log(`${String(count).padStart(5, " ")}  ${folder}`);
    }
    console.log(`\nTotal files scanned: ${files.length}`);
    console.log(`Total findings: ${totalErrors} errors (${totalWarnings} warnings)`);
    process.exit(0);
}

if (totalErrors > 0) {
    console.error(`\n✖ ${totalErrors} UI lint error(s) found (${totalWarnings} warning(s)).`);
    process.exit(1);
} else {
    console.log(`✔ 0 UI lint errors (${files.length} file(s) checked).`);
    process.exit(0);
}
