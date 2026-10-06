import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                tactical: {
                    base: "#020617", // bg-slate-950 (Latar belakang utama)
                    panel: "#0f172a", // bg-slate-900 (Container / Card)
                    surface: "#1e293b", // bg-slate-800 (Card hover / input bg)
                    border: "#1e293b", // border-slate-800
                    "border-light": "#334155", // border-slate-700
                    text: "#f8fafc", // text-slate-100
                    muted: "#94a3b8", // text-slate-400
                    accent: "#2563eb", // blue-600
                },
                // Warna Spesifik Departemen Kepolisian (LSPD, BCSO, SASP, dll)
                dept: {
                    lspd: "#2563eb", // Blue
                    bcso: "#d97706", // Amber/Yellow
                    sasp: "#475569", // Dark Slate
                    sapr: "#059669", // Emerald Green
                },
            },
            fontFamily: {
                sans: [
                    '"Plus Jakarta Sans"',
                    "Inter",
                    ...defaultTheme.fontFamily.sans,
                ],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
                tactical: ['"Rajdhani"', "sans-serif"],
                heading: ['"Rajdhani"', "sans-serif"],
            },
        },
    },

    plugins: [forms],
};
