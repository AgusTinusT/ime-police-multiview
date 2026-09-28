import { ref } from "vue";

/**
 * Senior Web Analytics & GA4 Centralized Composable
 * 
 * Complies with GA4 & Dynamic Tab Title Audit Requirements:
 * 1. Standard Title Pattern: [Nama Halaman / Status / Aksi] – [Nama Website/Aplikasi]
 * 2. In-Page & Virtual Pageview Tracking: Sync pushState & fire gtag page_view.
 * 3. Title update MUST execute BEFORE GA4 event trigger (prevents stale title data).
 * 4. Safe gtag function check (typeof window.gtag === 'function') with dataLayer fallback.
 * 5. Active Session Heartbeat tracking for passive/long watching sessions.
 */

const APP_NAME = "IME RP Multiview";
const DEFAULT_TITLE = "Multiview (10-8 Live Officer Feeds) – IME RP Multiview";

/**
 * Safe helper to trigger gtag events
 */
function sendGtagEvent(eventName, params = {}) {
    if (typeof window === "undefined") return;

    if (typeof window.gtag === "function") {
        window.gtag("event", eventName, params);
    } else if (window.dataLayer && Array.isArray(window.dataLayer)) {
        window.dataLayer.push({
            event: eventName,
            ...params,
        });
    }
}

/**
 * Update document.title safely & return full title string
 */
export function setPageTitle(dynamicPart, siteName = APP_NAME) {
    if (typeof document === "undefined") return "";
    let title = "";
    if (!dynamicPart) {
        title = DEFAULT_TITLE;
    } else if (dynamicPart.includes("–") || dynamicPart.includes("- IME RP")) {
        title = dynamicPart;
    } else {
        title = `${dynamicPart} – ${siteName}`;
    }

    document.title = title;
    return title;
}

export function useAnalytics() {
    /**
     * Send virtual pageview after updating browser title
     */
    const trackPageView = (dynamicTitle = "", path = null) => {
        if (typeof window === "undefined") return;

        const title = setPageTitle(dynamicTitle);
        const currentPath = path || (window.location.pathname + window.location.search);
        const currentLocation = window.location.origin + currentPath;

        sendGtagEvent("page_view", {
            page_title: title,
            page_location: currentLocation,
            page_path: currentPath,
        });
    };

    /**
     * Track user switching or focusing on a specific stream bodycam
     */
    const trackSelectStream = (stream) => {
        if (!stream || typeof window === "undefined") return;

        const officerName =
            stream.officer?.officer_name || stream.officer_name || stream.name || "Officer";
        const streamTitle = stream.title || "Live Stream Bodycam";
        const dept = stream.officer?.department || stream.department || "UNIT";
        const videoId = stream.video_id || stream.id || "";

        // 1. Update document.title FIRST before sending GA4 event (Prevents stale title data)
        const titleText = `Menonton: ${officerName} (${dept})`;
        const fullTitle = setPageTitle(titleText);

        const currentPath = window.location.pathname + window.location.search;
        const currentLocation = window.location.href;

        // 2. Fire custom select_stream event
        sendGtagEvent("select_stream", {
            event_category: "stream_interaction",
            event_label: `${officerName} [${videoId}]`,
            video_id: videoId,
            video_title: streamTitle,
            officer_name: officerName,
            department: dept,
            channel_name: stream.officer?.handle || stream.officer?.channel_id || "",
            page_title: fullTitle,
            page_location: currentLocation,
        });

        // 3. Fire virtual page_view event for the in-page stream change
        sendGtagEvent("page_view", {
            page_title: fullTitle,
            page_location: currentLocation,
            page_path: currentPath,
        });
    };

    /**
     * Track user switching department category tabs (LSPD, BCSO, SASP, SAPR, TAC, Personal)
     */
    const trackDepartmentChange = (deptCode) => {
        if (!deptCode || typeof window === "undefined") return;

        const deptLabel =
            deptCode === "ALL"
                ? "Seluruh Unit (10-8)"
                : deptCode === "PERSONAL"
                ? "Watchlist Personal"
                : deptCode.replace("_", " ");

        const titleText = `Kategori: ${deptLabel}`;
        const fullTitle = setPageTitle(titleText);

        const currentPath = window.location.pathname + window.location.search;
        const currentLocation = window.location.href;

        sendGtagEvent("select_department", {
            event_category: "navigation",
            event_label: deptCode,
            department: deptCode,
            page_title: fullTitle,
            page_location: currentLocation,
        });

        sendGtagEvent("page_view", {
            page_title: fullTitle,
            page_location: currentLocation,
            page_path: currentPath,
        });
    };

    /**
     * Periodic active session heartbeat tracking (every 2 mins during active stream watching)
     */
    const trackHeartbeat = (metadata = {}) => {
        if (typeof window === "undefined") return;
        if (document.visibilityState !== "visible") return;

        sendGtagEvent("stream_heartbeat", {
            event_category: "engagement",
            event_label: "active_watching",
            non_interaction: false,
            active_streams_count: metadata.activeStreamsCount || 0,
            focused_stream_id: metadata.focusedStreamId || null,
            focused_officer: metadata.focusedOfficer || null,
            selected_department: metadata.selectedDepartment || "ALL",
            page_title: document.title,
            page_location: window.location.href,
        });
    };

    return {
        setPageTitle,
        trackPageView,
        trackSelectStream,
        trackDepartmentChange,
        trackHeartbeat,
    };
}
