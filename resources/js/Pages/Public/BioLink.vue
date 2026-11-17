<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import BioLinkView from "@/Components/BioLink/BioLinkView.vue";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";

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
    promoCta: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const isDismissed = ref(false);

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
    if (typeof window !== "undefined") {
        try {
            if (localStorage.getItem("biolink_promo_dismissed") === "1") {
                isDismissed.value = true;
            }
        } catch {
            // ignore
        }
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

// ?theme=<key> previews a preset on any profile without saving it.
const previewKey = typeof window !== "undefined" ? new URLSearchParams(window.location.search).get("theme") : null;

const shouldShowNudge = computed(() => {
    if (page.props.auth?.user?.is_officer === true) return false;
    if (isDismissed.value) return false;
    return true;
});

const dismissNudge = () => {
    isDismissed.value = true;
    try {
        localStorage.setItem("biolink_promo_dismissed", "1");
    } catch {
        // ignore
    }
};
</script>

<template>
    <Head>
        <title>{{ meta.title || `${officer.officer_name} — Tactical Bio Hub` }}</title>
        <meta name="description" :content="meta.description || ''" />
    </Head>

    <div class="relative min-h-screen">
        <BioLinkView
            :officer="officerState"
            :profile="profile"
            :links="linksState"
            :themes="themes"
            :theme-key="previewKey"
            :is-suspended="isSuspended"
            :is-unclaimed="isUnclaimed"
        />

        <!-- Ajakan halus di /@slug (BIO-01 C) -->
        <div
            v-if="shouldShowNudge"
            class="fixed bottom-4 inset-x-4 mx-auto max-w-lg z-50 animate-in fade-in slide-in-from-bottom-4 duration-300"
        >
            <Card class="p-3.5 sm:p-4 shadow-xl border-border bg-card/95 backdrop-blur-md flex items-center justify-between gap-3 text-xs">
                <div class="space-y-0.5 min-w-0 flex-1 text-left">
                    <p class="text-xs sm:text-sm font-semibold text-foreground">
                        Suka tampilan ini?
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Kamu juga bisa punya bio-link resmi di <span class="font-mono text-primary font-bold">/@kamu</span>.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button as-child size="sm" class="font-mono text-xs font-bold h-8">
                        <Link href="/bio-link">Pelajari &rarr;</Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-xs text-muted-foreground hover:text-foreground h-8 px-2"
                        @click="dismissNudge"
                    >
                        Sembunyikan
                    </Button>
                </div>
            </Card>
        </div>
    </div>
</template>
