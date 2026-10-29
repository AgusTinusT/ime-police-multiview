<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { DeptBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import {
    ArrowRight,
    ClipboardList,
    Heart,
    Play,
    ShieldCheck,
    Sparkles,
} from "@lucide/vue";

const { t } = useI18n();

const props = defineProps({
    title: {
        type: String,
        default: "",
    },
    subtitle: {
        type: String,
        default: "",
    },
    officers: {
        type: Array,
        default: () => null,
    },
});

const displayTitle = computed(
    () => props.title || t("sub_support.badge_title"),
);
const displaySubtitle = computed(
    () => props.subtitle || t("sub_support.subtitle"),
);

const defaultSupportOfficers = [
    {
        id: 1,
        officer_name: "Maya 'Falcon' Lin",
        streamer_name: "Falcon_SAPR",
        callsign: "4-PARK-08",
        department: "SAPR",
        avatar_url:
            "https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@Falcon_SAPR",
    },
    {
        id: 2,
        officer_name: "Ethan 'Ghost' Hunt",
        streamer_name: "Ghost_LSPD",
        callsign: "2-ADAM-15",
        department: "LSPD",
        avatar_url:
            "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@Ghost_LSPD",
    },
    {
        id: 3,
        officer_name: "Samantha 'Raven' Reed",
        streamer_name: "Raven_BCSO",
        callsign: "3-MARY-04",
        department: "BCSO",
        avatar_url:
            "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Raven_BCSO",
    },
    {
        id: 4,
        officer_name: "Lucas 'Storm' Miller",
        streamer_name: "Storm_Trooper",
        callsign: "1-KING-02",
        department: "SASP",
        avatar_url:
            "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Storm_Trooper",
    },
    {
        id: 5,
        officer_name: "Marcus 'Viper' Vance",
        streamer_name: "Viper_LSPD",
        callsign: "2-ADAM-22",
        department: "LSPD",
        avatar_url:
            "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Viper_LSPD",
    },
    {
        id: 6,
        officer_name: "Chloe 'Apex' Bennett",
        streamer_name: "Apex_SAPR",
        callsign: "4-PARK-12",
        department: "SAPR",
        avatar_url:
            "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@Apex_SAPR",
    },
    {
        id: 7,
        officer_name: "Noah 'Titan' Hayes",
        streamer_name: "Titan_BCSO",
        callsign: "3-LINCOLN-09",
        department: "BCSO",
        avatar_url:
            "https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Titan_BCSO",
    },
    {
        id: 8,
        officer_name: "Daniel 'Cobalt' Drake",
        streamer_name: "Cobalt_SASP",
        callsign: "1-KING-05",
        department: "SASP",
        avatar_url:
            "https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Cobalt_SASP",
    },
    {
        id: 9,
        officer_name: "Bayu 'Speed' Nugraha",
        streamer_name: "BayuRP_Patrol",
        callsign: "2-BOY-08",
        department: "LSPD",
        avatar_url:
            "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@BayuRP_Patrol",
    },
];

const displayedOfficers = computed(() => {
    if (props.officers && props.officers.length > 0) {
        return props.officers;
    }
    return defaultSupportOfficers;
});

const openChannel = (url) => {
    if (!url) return;
    let targetUrl = url;
    if (
        targetUrl.includes("youtube.com/") &&
        !targetUrl.includes("/channel/") &&
        !targetUrl.includes("/watch?") &&
        !targetUrl.includes("/embed/") &&
        !targetUrl.includes("/live_chat?") &&
        !targetUrl.includes("/@")
    ) {
        targetUrl = targetUrl.replace("youtube.com/", "youtube.com/@");
    }
    window.open(targetUrl, "_blank");
};
</script>

<template>
    <section class="relative w-full font-sans">
        <div
            class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start relative z-10"
        >
            <!-- LEFT COLUMN (8/12): EXPANDED MONITORING PANEL WITH 3-COLUMN OFFICERS MATRIX -->
            <div class="lg:col-span-8 order-2 lg:order-1">
                <Card class="p-5 space-y-4">
                    <!-- HUD Card Header -->
                    <div
                        class="flex items-center justify-between pb-3 border-b border-border"
                    >
                        <div class="flex items-center gap-2">
                            <ShieldCheck
                                class="size-4 text-primary shrink-0 hidden sm:block"
                            />
                            <h3
                                class="text-xs font-mono font-semibold text-foreground uppercase tracking-wider"
                            >
                                {{ $t("sub_support.active_list_title") }}
                            </h3>
                        </div>
                        <Badge
                            variant="info"
                            class="hidden sm:inline-flex font-mono text-xs"
                        >
                            {{ $t("sub_support.live_3_days") }}
                        </Badge>
                    </div>

                    <!-- 3-Column Officers Matrix -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5"
                    >
                        <div
                            v-for="officer in displayedOfficers"
                            :key="officer.id"
                            class="p-2.5 rounded-lg bg-background hover:border-input border border-border transition-colors flex items-center justify-between gap-2.5 group"
                        >
                            <!-- Profile Photo & Officer Name -->
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <div
                                    class="shrink-0 cursor-pointer"
                                    @click="openChannel(officer.youtube_url)"
                                >
                                    <OfficerAvatar
                                        :src="officer.avatar_url"
                                        :name="officer.officer_name"
                                        :live="officer.is_live_now"
                                        size="md"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h4
                                            class="font-semibold text-xs text-foreground leading-tight truncate cursor-pointer hover:text-primary transition-colors"
                                            @click="
                                                openChannel(officer.youtube_url)
                                            "
                                        >
                                            {{ officer.officer_name }}
                                        </h4>
                                    </div>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <DeptBadge
                                            :dept="officer.department || 'SASP'"
                                        />
                                        <span
                                            class="text-xs text-primary font-mono truncate"
                                        >
                                            @{{ officer.streamer_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right CTA Button (YouTube Support) -->
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                class="h-7 px-2.5 text-xs font-mono gap-1 shrink-0"
                                @click="openChannel(officer.youtube_url)"
                            >
                                <Play class="size-3 fill-current shrink-0" />
                                <span>{{ $t("sub_support.support_btn") }}</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Bottom HUD Card Footer -->
                    <div
                        class="pt-3 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono"
                    >
                        <span
                            class="text-muted-foreground flex items-center gap-1.5"
                        >
                            <ClipboardList
                                class="size-3.5 text-muted-foreground inline"
                            />
                            <span>{{
                                $t("sub_support.showing_officers", {
                                    count: displayedOfficers.length,
                                })
                            }}</span>
                        </span>
                        <Button as-child variant="outline" size="sm">
                            <Link
                                href="/officers?sort=subs_asc"
                                class="font-mono text-xs gap-1.5 group"
                            >
                                <span>{{
                                    $t("sub_support.view_all_officers")
                                }}</span>
                                <ArrowRight
                                    class="size-3.5 group-hover:translate-x-1 transition-transform"
                                />
                            </Link>
                        </Button>
                    </div>
                </Card>
            </div>

            <!-- RIGHT COLUMN (4/12): COMPACT VERTICAL TACTICAL SIDEBAR -->
            <div class="lg:col-span-4 order-1 lg:order-2">
                <div class="space-y-4 sticky top-20">
                    <!-- Top Badge Pill -->
                    <Badge
                        variant="outline"
                        class="inline-flex items-center gap-2 px-2.5 py-1 text-xs font-mono text-primary bg-card"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-primary motion-safe:animate-pulse"
                        ></span>
                        <span>SUPPORT BOOST // ROAD TO 1K</span>
                    </Badge>

                    <!-- Compact Section Header -->
                    <div class="space-y-1.5">
                        <h2
                            class="text-xl font-bold text-foreground tracking-tight uppercase leading-tight"
                        >
                            IME POLICE OFFICER <br />
                            <span class="text-primary">{{
                                displayTitle
                            }}</span>
                        </h2>

                        <p
                            class="text-muted-foreground text-xs leading-relaxed font-sans font-normal"
                        >
                            {{ displaySubtitle }}
                        </p>
                    </div>

                    <!-- Information Feature Pills & Action Button -->
                    <div class="space-y-2.5 font-mono text-xs">
                        <div
                            class="flex items-center gap-2.5 p-2.5 rounded-lg bg-background border border-border"
                        >
                            <Sparkles class="size-4 text-primary shrink-0" />
                            <div>
                                <strong
                                    class="text-foreground block font-sans text-xs"
                                    >{{
                                        $t(
                                            "sub_support.feature_active_officers",
                                        )
                                    }}</strong
                                >
                                <span
                                    class="text-muted-foreground text-xs font-sans font-normal"
                                    >{{
                                        $t(
                                            "sub_support.feature_active_officers_desc",
                                        )
                                    }}</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2.5 p-2.5 rounded-lg bg-background border border-border"
                        >
                            <Heart class="size-4 text-success shrink-0" />
                            <div>
                                <strong
                                    class="text-foreground block font-sans text-xs"
                                    >{{
                                        $t(
                                            "sub_support.feature_community_support",
                                        )
                                    }}</strong
                                >
                                <span
                                    class="text-muted-foreground text-xs font-sans font-normal"
                                    >{{
                                        $t(
                                            "sub_support.feature_community_support_desc",
                                        )
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
