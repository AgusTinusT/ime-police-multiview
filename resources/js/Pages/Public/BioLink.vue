<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import BioLinkView from "@/Components/BioLink/BioLinkView.vue";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        required: true,
    },
    links: {
        type: [Array, Object],
        default: () => [],
    },
    isSuspended: {
        type: Boolean,
        default: false,
    },
    isUnclaimed: {
        type: Boolean,
        default: false,
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
    // config('biolinks.theme_presets'): the theme contract, keyed by preset
    themes: {
        type: Object,
        default: () => ({}),
    },
});

const initialLinks = Array.isArray(props.links)
    ? props.links
    : Object.values(props.links || {});

// Reactive state for polling updates (T-09)
const officerState = ref({ ...props.officer });
const linksState = ref([...initialLinks]);

// Polling live telemetry every 60 seconds without full reload
let pollTimer = null;
const fetchLiveStatus = async () => {
    if (props.isSuspended || props.isUnclaimed || !props.profile?.slug) return;
    try {
        const res = await axios.get(`/api/v1/bio-links/${props.profile.slug}/live-status`);
        if (res.data) {
            officerState.value.is_live = res.data.is_live;
            officerState.value.live_stream = res.data.live_stream;

            if (res.data.recommended_streams) {
                linksState.value.forEach((l) => {
                    if (l.link_type === "officer_recommendation" && res.data.recommended_streams[l.id] && l.recommended_officer) {
                        const rec = res.data.recommended_streams[l.id];
                        l.recommended_officer.is_live = rec.is_live;
                        l.recommended_officer.active_stream = rec.active_stream;
                    }
                });
            }
        }
    } catch {
        // Silently skip if network error occurs during background poll
    }
};

onMounted(() => {
    pollTimer = setInterval(fetchLiveStatus, 60000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

// ?theme=<key> previews a preset on any profile without saving it.
const previewKey = typeof window !== "undefined" ? new URLSearchParams(window.location.search).get("theme") : null;
</script>

<template>
    <Head>
        <title>{{ meta.title || `${officer.officer_name} — Tactical Bio Hub` }}</title>
        <meta name="description" :content="meta.description || ''" />
    </Head>

    <BioLinkView
        :officer="officerState"
        :profile="profile"
        :links="linksState"
        :themes="themes"
        :theme-key="previewKey"
        :is-suspended="isSuspended"
        :is-unclaimed="isUnclaimed"
    />
</template>
