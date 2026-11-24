export const releasesEn = [
    {
        version: "v2.7.0",
        date: "November 22, 2026",
        badge: "LATEST RELEASE",
        isLatest: true,
        summary:
            "YouTube-style watch page, Action Moments with automatic archiving, one Clip Studio name, a new floating header, a transparent Support page, and fixes for cutting live streams.",
        changes: [
            {
                type: "FEATURE",
                tag: "Watch Page",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "YouTube-style Watch Page (/watch)",
                description:
                    "Watch one officer with a large player, live chat, other live streams and recent replays. Dashboard cards and multiview tiles now open this page.",
            },
            {
                type: "FEATURE",
                tag: "Action Moments",
                tagColor: "bg-purple-950/70 text-purple-300 border-purple-500/40",
                title: "TacClips Is Now Action Moments (/momen)",
                description:
                    "Mark a moment straight from the watch page. Every moment is archived automatically at 720p for 30 days, even while the stream is still live.",
            },
            {
                type: "FEATURE",
                tag: "Clip Studio",
                tagColor: "bg-purple-950/70 text-purple-300 border-purple-500/40",
                title: "One Name: Clip Studio (/clip-studio)",
                description:
                    "The trim & download studio now has a single name and address. Old /clipper and /action-clips links keep working.",
            },
            {
                type: "FEATURE",
                tag: "Interface",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Floating Header & New Account Menu",
                description:
                    "The header turns into a floating pill when you scroll, with a More menu (Send feedback, Updates, FAQ) and a cleaner account menu.",
            },
            {
                type: "FEATURE",
                tag: "Community",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Support Page (/dukung)",
                description:
                    "Transparent running costs, the next goal (prizes for the monthly Top 1–3 patrol event), and ways to help: donations, GitHub or feedback.",
            },
            {
                type: "FEATURE",
                tag: "Main Page",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Denser, Tidier Dashboard",
                description:
                    "New officers are highlighted for 14 days, Top 10 patrols moved up, own and community squads merged without duplicates, and scroll arrows only show when needed.",
            },
            {
                type: "FEATURE",
                tag: "Officers",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Open Channel & Non-personnel Bio-link Accounts",
                description:
                    "Officer profiles get an Open Channel button. Accounts that only use a bio-link can be hidden from officer lists without turning the bio-link off.",
            },
            {
                type: "BUGFIX",
                tag: "Clip Studio",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Live Clips Start at the Chosen Minute",
                description:
                    "Cutting a stream that is still live no longer returns footage from after the chosen minute. Clips come from the stream buffer, even hours back.",
            },
            {
                type: "BUGFIX",
                tag: "Live Streams",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "#imepolice Streams Always Show as Live",
                description:
                    "Titles tagged #imepolice now show under Live even when they contain words like \"rampok\", and streams that are still live no longer appear as replays.",
            },
            {
                type: "BUGFIX",
                tag: "Watch Page",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Full Stream Descriptions",
                description:
                    "Short multi-line descriptions are no longer cut off, and links in descriptions (e.g. donation pages) are clickable.",
            },
            {
                type: "BUGFIX",
                tag: "System",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Navigation, Admin & Security Fixes",
                description:
                    "The dashboard header stays pinned while scrolling, the admin Recommended Websites menu opens again, and server dependencies were updated to close 11 security advisories.",
            },
        ],
    },
    {
        version: "v2.6.0",
        date: "October 31, 2026",
        summary:
            "Architecture update: the personal Watchlist and TAC Channel radio features are retired and fully replaced by custom Squads and more flexible community presets.",
        changes: [
            {
                type: "REMOVED",
                tag: "Squad System",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Watchlist Retired — Use Squads",
                description:
                    "The personal Watchlist has been retired. Use Squads to group and save favourite officers across devices.",
            },
            {
                type: "REMOVED",
                tag: "Squad System",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "TAC Channel Retired — Squads Take Over",
                description:
                    "Tactical radio channels TAC 1-10 are retired and replaced by community squads and watch-party presets that can be shared between users.",
            },
        ],
    },
    {
        version: "v2.5.0",
        date: "September 27, 2026",
        summary:
            "Major release updates: Main Command Dashboard, Streamer Road to 1K Subs Support Feature, Monthly Streamer Leaderboard, Officer Visibility & Priority Panel, and Multiview 10-8 Stage Streamlining.",
        changes: [
            {
                type: "FEATURE",
                tag: "Main Page",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Main Dashboard Page (Police Duty Command)",
                description:
                    "Updated main route structure where users are now greeted with an interactive Dashboard page before entering the Multiview stage.",
            },
            {
                type: "FEATURE",
                tag: "Community Feature",
                tagColor:
                    "bg-purple-950/70 text-purple-300 border-purple-500/40",
                title: "Streamer Road to 1K Subscriber Support",
                description:
                    "Dedicated Dashboard section supporting officers/streamers chasing 1,000 subscribers (1K Subs) with direct subscribe shortcuts.",
            },
            {
                type: "FEATURE",
                tag: "Patrol Ranking",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Monthly Streamer & Patrol Hours Leaderboard",
                description:
                    "Monthly active officer leaderboard based on accumulated 10-8 patrol hours, total viewers, and monthly broadcast consistency.",
            },
            {
                type: "FEATURE",
                tag: "Multiview Feature",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Officer Visibility & Management Panel (BottomSheet)",
                description:
                    "Officer live stream visibility controls with status filters (Shown, Hidden, Priority), consistent color indicators, and instant search.",
            },
            {
                type: "FEATURE",
                tag: "Multiview Feature",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Grid Order Hierarchy & Priority Pin Settings",
                description:
                    "Prioritize officer streams at top grid positions (#1, #2, #3...) using Pin feature (📌) and SVG-based Up/Down navigation buttons (▲/▼).",
            },
            {
                type: "UIUX",
                tag: "UI & Experience",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Layout Simplification & 10-7 Offline Roster Removal",
                description:
                    "Removed 10-7 Offline Roster feature and sub-header toolbar menu to optimize page performance and focus 100% on live 10-8 broadcasts.",
            },
            {
                type: "BUGFIX",
                tag: "Bug Fixes",
                tagColor:
                    "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Multiview Control Search & ReferenceError Fix",
                description:
                    "Fixed realtime officer search in Multiview Control and resolved `getOfficerKey` issues for officer priority action stability.",
            },
            {
                type: "UIUX",
                tag: "UI & Experience",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "UI Standardization According to panduan.html",
                description:
                    "Applied panduan.html design specifications: aligned status color system (Emerald for Shown, Rose for Hidden, Amber for Priority), single-language action labels ('Tampil' vs 'Sembunyi'), and replaced all emoticons with native SVG vectors.",
            },
        ],
    },
    {
        version: "v2.4.0",
        date: "September 17, 2026",
        badge: "STABLE",
        isLatest: false,
        summary:
            "Weekly release updates: Police Agency & Rank Master, Tactical Certifications, Member Account & RBAC Management, Real-time Member Chat, Mobile & Bottom Dock View, 1080p HD Video Quality, and Server Security.",
        changes: [
            {
                type: "FEATURE",
                tag: "Admin Feature",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Police Agency Master & Rank Hierarchy",
                description:
                    "Admins can now centrally manage police agency structures (SASP, LSPD, BCSO, SAPR), officer rank tiers, command levels, and tactical sub-divisions.",
            },
            {
                type: "FEATURE",
                tag: "Admin Feature",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Tactical Qualification & Certification Management",
                description:
                    "Special officer licenses and certification management (e.g. Air Support, K9, SWAT/SRT, Pursuit Unit) via admin panel.",
            },
            {
                type: "FEATURE",
                tag: "Admin Feature",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Member Account & Access Control (RBAC)",
                description:
                    "Registered member account management, role access permissions (Admin & Member), and administrative route protection.",
            },
            {
                type: "FEATURE",
                tag: "Member Feature",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Community Chatroom (Tactical Member Chat)",
                description:
                    "New weekly feature: Live chatroom dedicated for registered members and officers with official rank/role badges and chat moderation protection.",
            },
        ],
    },
];
