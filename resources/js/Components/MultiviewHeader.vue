<script setup>
import { Link } from "@inertiajs/vue3";

import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";

import UserAccountMenu from "@/Components/UserAccountMenu.vue";
import TacticalMobileBottomNav from "@/Components/TacticalMobileBottomNav.vue";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { cn } from "@/lib/utils";
import { Radio, Star, Video } from "@lucide/vue";

const props = defineProps({
    selectedDepartment: {
        type: String,
        default: "ALL",
    },
    allActiveStreams: {
        type: Array,
        default: () => [],
    },
    activePersonalStreams: {
        type: Array,
        default: () => [],
    },
    tacChannels: {
        type: Array,
        default: () => [],
    },
    isDataSaverEnabled: {
        type: Boolean,
        default: false,
    },
    isSyncingFeeds: {
        type: Boolean,
        default: false,
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "update:selectedDepartment",
    "enable-data-saver",
    "disable-data-saver",
    "trigger-sync",
    "toggle-fullscreen",
]);

const getDeptCount = (deptId) => {
    if (deptId === "ALL") return props.allActiveStreams?.length || 0;
    if (deptId === "PERSONAL") return props.activePersonalStreams?.length || 0;
    if (deptId === "TAC") {
        return (props.tacChannels || []).reduce(
            (acc, c) => acc + (c.unit_count || c.video_ids?.length || 0),
            0,
        );
    }
    return (props.allActiveStreams || []).filter((s) => {
        const d = (s.officer?.department || "").toUpperCase();
        if (deptId === "SAPR") return d === "SAPR" || d === "PARK RANGER";
        return d === deptId;
    }).length;
};
</script>

<template>
    <!-- Full-Width Edge-to-Edge Master Header Bar -->
    <header
        class="w-full bg-background border-b border-border sticky top-0 z-40 py-2.5 sm:py-3"
    >
        <div
            class="max-w-(--breakpoint-2xl) mx-auto w-full px-3 sm:px-4 lg:px-8 flex items-center justify-between gap-3 text-foreground"
        >
            <!-- LEFT BRANDING: Police Duty (Navigates to Dashboard) -->
            <Link
                href="/"
                class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                title="IME Roleplay — Police Duty Dashboard"
            >
                <div
                    class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-md bg-card border border-border group-hover:border-input p-1 overflow-hidden transition"
                >
                    <img
                        :src="logoSaspColor"
                        class="w-full h-full object-contain rounded-sm"
                        alt="Police Badge"
                    />
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-xs font-extrabold tracking-wider text-primary group-hover:text-primary/80 uppercase leading-tight transition"
                    >
                        IME ROLEPLAY
                    </span>
                    <span
                        class="text-xs font-bold tracking-wide text-foreground/80 group-hover:text-foreground uppercase leading-tight transition"
                    >
                        POLICE DUTY
                    </span>
                </div>
            </Link>

            <!-- CENTER CATEGORIES (Desktop) -->
            <nav
                class="hidden md:flex items-center space-x-1.5 overflow-x-auto scrollbar-none px-1 py-0.5"
            >
                <Button
                    type="button"
                    :variant="
                        selectedDepartment === 'ALL' ? 'default' : 'ghost'
                    "
                    size="sm"
                    class="text-xs font-mono uppercase tracking-wider font-bold gap-1.5 shrink-0"
                    @click="emit('update:selectedDepartment', 'ALL')"
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full bg-success motion-safe:animate-pulse"
                    ></span>
                    <span>{{ $t("multiview.all_units") }}</span>
                    <Badge
                        :variant="
                            selectedDepartment === 'ALL'
                                ? 'secondary'
                                : 'outline'
                        "
                        class="px-1.5 py-0 text-xs font-mono font-bold"
                    >
                        {{ getDeptCount("ALL") }}
                    </Badge>
                </Button>

                <Button
                    v-for="item in [
                        { id: 'LSPD', label: 'LSPD', icon: iconLspd },
                        { id: 'BCSO', label: 'BCSO', icon: iconBcso },
                        { id: 'SASP', label: 'SASP', icon: iconSasp },
                        { id: 'SAPR', label: 'SAPR', icon: iconSapr },
                    ]"
                    :key="item.id"
                    type="button"
                    :variant="
                        selectedDepartment === item.id ? 'default' : 'ghost'
                    "
                    size="sm"
                    class="text-xs font-mono uppercase tracking-wider font-bold gap-1.5 shrink-0"
                    @click="emit('update:selectedDepartment', item.id)"
                >
                    <img
                        :src="item.icon"
                        class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90"
                        alt=""
                    />
                    <span>{{ item.label }}</span>
                    <Badge
                        :variant="
                            selectedDepartment === item.id
                                ? 'secondary'
                                : 'outline'
                        "
                        class="px-1.5 py-0 text-xs font-mono font-bold"
                    >
                        {{ getDeptCount(item.id) }}
                    </Badge>
                </Button>

                <Button
                    type="button"
                    :variant="
                        selectedDepartment.startsWith('TAC_')
                            ? 'default'
                            : 'ghost'
                    "
                    size="sm"
                    class="text-xs font-mono uppercase tracking-wider font-bold gap-1.5 shrink-0"
                    @click="
                        emit(
                            'update:selectedDepartment',
                            selectedDepartment.startsWith('TAC_')
                                ? 'ALL'
                                : 'TAC_1',
                        )
                    "
                >
                    <Radio class="size-3.5" />
                    <span>{{
                        selectedDepartment.startsWith("TAC_")
                            ? selectedDepartment.replace("_", " ")
                            : "TAC"
                    }}</span>
                    <Badge
                        :variant="
                            selectedDepartment.startsWith('TAC_')
                                ? 'secondary'
                                : 'outline'
                        "
                        class="px-1.5 py-0 text-xs font-mono font-bold"
                    >
                        {{ getDeptCount("TAC") }}
                    </Badge>
                </Button>

                <Button
                    type="button"
                    :variant="
                        selectedDepartment === 'PERSONAL' ? 'default' : 'ghost'
                    "
                    size="sm"
                    class="text-xs font-mono uppercase tracking-wider font-bold gap-1.5 shrink-0"
                    @click="emit('update:selectedDepartment', 'PERSONAL')"
                >
                    <Star class="size-3.5" />
                    <span>{{ $t("multiview.watchlist") }}</span>
                    <Badge
                        :variant="
                            selectedDepartment === 'PERSONAL'
                                ? 'secondary'
                                : 'outline'
                        "
                        class="px-1.5 py-0 text-xs font-mono font-bold"
                    >
                        {{ getDeptCount("PERSONAL") }}
                    </Badge>
                </Button>
            </nav>

            <!-- RIGHT AREA: Action Controls & User Avatar -->
            <div class="flex items-center space-x-2 shrink-0">
                <Button as-child variant="outline" size="sm">
                    <Link
                        href="/action-clips"
                        class="text-xs font-bold font-mono gap-1.5"
                        title="Buka TacClips — Galeri Momen Aksi Kepolisian"
                    >
                        <Video class="size-3.5 text-primary" />
                        <span class="hidden sm:inline">TacClips</span>
                    </Link>
                </Button>
                <UserAccountMenu />
            </div>
        </div>

        <!-- MOBILE SECONDARY NAV RIBBON (< md) -->
        <div
            class="flex md:hidden max-w-(--breakpoint-2xl) mx-auto w-full px-3 py-2 items-center space-x-2 overflow-x-auto scrollbar-none border-t border-border mt-2 bg-background/95"
        >
            <Button
                v-for="mItem in [
                    { id: 'ALL', label: 'ALL', countKey: 'ALL' },
                    { id: 'LSPD', label: 'LSPD', countKey: 'LSPD' },
                    { id: 'BCSO', label: 'BCSO', countKey: 'BCSO' },
                    { id: 'SASP', label: 'SASP', countKey: 'SASP' },
                    { id: 'SAPR', label: 'SAPR', countKey: 'SAPR' },
                ]"
                :key="mItem.id"
                type="button"
                :variant="
                    selectedDepartment === mItem.id ? 'default' : 'outline'
                "
                size="sm"
                :class="
                    cn(
                        'h-7 px-3 text-xs font-mono uppercase font-bold shrink-0 gap-1.5',
                    )
                "
                @click="emit('update:selectedDepartment', mItem.id)"
            >
                <span>{{ mItem.label }}</span>
                <span
                    v-if="selectedDepartment === mItem.id"
                    class="text-xs px-1 rounded-sm bg-background/30 font-mono font-bold"
                >
                    {{ getDeptCount(mItem.countKey) }}
                </span>
            </Button>

            <Button
                type="button"
                :variant="
                    selectedDepartment.startsWith('TAC_')
                        ? 'default'
                        : 'outline'
                "
                size="sm"
                class="h-7 px-3 text-xs font-mono uppercase font-bold shrink-0 gap-1.5"
                @click="
                    emit(
                        'update:selectedDepartment',
                        selectedDepartment.startsWith('TAC_') ? 'ALL' : 'TAC_1',
                    )
                "
            >
                <span>TAC</span>
                <span
                    v-if="selectedDepartment.startsWith('TAC_')"
                    class="text-xs px-1 rounded-sm bg-background/30 font-mono font-bold"
                >
                    {{ getDeptCount("TAC") }}
                </span>
            </Button>

            <Button
                type="button"
                :variant="
                    selectedDepartment === 'PERSONAL' ? 'default' : 'outline'
                "
                size="sm"
                class="h-7 px-3 text-xs font-mono uppercase font-bold shrink-0 gap-1.5"
                @click="emit('update:selectedDepartment', 'PERSONAL')"
            >
                <span>WATCHLIST</span>
                <span
                    v-if="selectedDepartment === 'PERSONAL'"
                    class="text-xs px-1 rounded-sm bg-background/30 font-mono font-bold"
                >
                    {{ getDeptCount("PERSONAL") }}
                </span>
            </Button>
        </div>
    </header>

    <!-- MOBILE BOTTOM NAVIGATION BAR & BOTTOM SHEET DRAWER -->
    <TacticalMobileBottomNav />
</template>
