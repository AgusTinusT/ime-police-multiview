export const releasesEn = [
    {
        version: "v2.5.0-Pro",
        date: "September 27, 2026",
        badge: "LATEST RELEASE",
        isLatest: true,
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
        version: "v2.4.0-Pro",
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
