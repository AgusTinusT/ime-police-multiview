<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { PageContainer, PageHeader } from "@/Components/tactical";
import { Card, CardContent } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import AccountSettings from "@/Components/Settings/AccountSettings.vue";
import OfficerSettings from "@/Components/Settings/OfficerSettings.vue";
import BioLinkSettings from "@/Components/Settings/BioLinkSettings.vue";
import BioExampleGallery from "@/Components/BioLink/BioExampleGallery.vue";
import { cn } from "@/lib/utils";
import {
    ArrowRight,
    Link2,
    Mail,
    ShieldCheck,
    User,
} from "@lucide/vue";

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: "",
    },
    currentTab: {
        type: String,
        default: "account",
    },
    officer: {
        type: Object,
        default: null,
    },
    agenciesMaster: {
        type: Array,
        default: () => [],
    },
    canChangeCallsign: {
        type: Boolean,
        default: true,
    },
    remainingCooldownDays: {
        type: Number,
        default: 0,
    },
    bioSlug: {
        type: String,
        default: null,
    },
    biolinksAdminEmail: {
        type: String,
        default: null,
    },
    // Bio-link editor props (hoisted here so switching to the Bio-link tab
    // client-side has everything already loaded).
    bioOfficer: { type: Object, default: null },
    bioProfile: { type: Object, default: null },
    bioOtherOfficers: { type: Array, default: () => [] },
    bioThemes: { type: Object, default: () => ({}) },
    bioButtonStyles: { type: Array, default: () => [] },
    bioAllowedDomains: { type: Object, default: () => ({}) },
    bioPlatforms: { type: Object, default: () => ({}) },
    bioStats: { type: Object, default: () => ({ total_views: 0, total_clicks: 0, links_count: 0 }) },
});

const page = usePage();

const isOfficer = computed(() => {
    const user = page.props.auth?.user;
    return !!(user?.is_officer || props.officer);
});

const navItems = computed(() => {
    const items = [
        {
            id: "account",
            label: "Akun",
            i18nKey: "settings.tab_account",
            icon: User,
        },
    ];

    if (isOfficer.value) {
        if (props.officer) {
            items.push({
                id: "officer",
                label: "Profil perwira",
                i18nKey: "settings.tab_officer",
                icon: ShieldCheck,
            });
        }
        items.push({
            id: "biolink",
            label: "Bio-link",
            i18nKey: "settings.tab_biolink",
            icon: Link2,
        });
    }

    return items;
});

const validTabIds = computed(() => navItems.value.map((i) => i.id));

const activeTab = ref("account");

const resolveInitialTab = () => {
    if (typeof window === "undefined") {
        return props.currentTab || "account";
    }
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get("tab") || props.currentTab;
    if (tabParam && validTabIds.value.includes(tabParam)) {
        return tabParam;
    }
    return "account";
};

const setTab = (tabId) => {
    if (!validTabIds.value.includes(tabId)) return;
    activeTab.value = tabId;
    if (typeof window !== "undefined") {
        const url = new URL(window.location.href);
        url.searchParams.set("tab", tabId);
        window.history.replaceState(null, "", url.toString());
    }
};

const handlePopState = () => {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get("tab");
    if (tabParam && validTabIds.value.includes(tabParam)) {
        activeTab.value = tabParam;
    } else {
        activeTab.value = "account";
    }
};

watch(
    () => props.currentTab,
    (newVal) => {
        if (newVal && validTabIds.value.includes(newVal)) {
            activeTab.value = newVal;
        }
    }
);

onMounted(() => {
    activeTab.value = resolveInitialTab();
    window.addEventListener("popstate", handlePopState);
});

onUnmounted(() => {
    window.removeEventListener("popstate", handlePopState);
});
</script>

<template>
    <Head :title="`${$t('settings.title')} - SASP Police Multiview`" />

    <TacticalLayout>
        <PageContainer size="lg" class="py-8 space-y-6">
            <PageHeader
                :title="$t('settings.title')"
                :description="$t('settings.description')"
            />

            <!-- Mobile Horizontal Nav Tabs (< md) -->
            <div
                v-if="navItems.length > 1"
                class="flex md:hidden overflow-x-auto no-scrollbar gap-1 p-1 bg-muted/50 rounded-lg"
            >
                <button
                    v-for="item in navItems"
                    :key="item.id"
                    type="button"
                    @click="setTab(item.id)"
                    :class="
                        cn(
                            'flex-1 px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition flex items-center justify-center gap-2 cursor-pointer',
                            activeTab === item.id
                                ? 'bg-card text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        )
                    "
                >
                    <component :is="item.icon" class="size-4 shrink-0" />
                    <span>{{ $t(item.i18nKey) }}</span>
                </button>
            </div>

            <!-- Desktop Side-by-side Layout (>= md) -->
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <!-- Desktop Left Vertical Nav (>= md): a plain list, not a card. -->
                <nav
                    v-if="navItems.length > 1"
                    class="hidden md:flex flex-col w-56 shrink-0 gap-0.5 self-start sticky top-20"
                >
                    <button
                        v-for="item in navItems"
                        :key="item.id"
                        type="button"
                        @click="setTab(item.id)"
                        :class="
                            cn(
                                'w-full text-left px-3 py-2 text-sm font-medium rounded-md transition flex items-center gap-3 cursor-pointer',
                                activeTab === item.id
                                    ? 'bg-accent text-foreground'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent/40'
                            )
                        "
                    >
                        <component :is="item.icon" class="size-4 shrink-0" />
                        <span>{{ $t(item.i18nKey) }}</span>
                    </button>
                </nav>

                <!-- Tab Content Area -->
                <div class="flex-1 w-full min-w-0">
                    <!-- Tab 1: Account Settings -->
                    <AccountSettings
                        v-if="activeTab === 'account'"
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />

                    <!-- Tab 2: Officer Duty Profile (Officer Only) -->
                    <OfficerSettings
                        v-else-if="activeTab === 'officer' && isOfficer && props.officer"
                        :officer="officer"
                        :agencies-master="agenciesMaster"
                        :can-change-callsign="canChangeCallsign"
                        :remaining-cooldown-days="remainingCooldownDays"
                    />

                    <!-- Tab 3: Full bio-link editor (officer only) -->
                    <template v-else-if="activeTab === 'biolink' && isOfficer">
                        <BioLinkSettings
                            v-if="bioOfficer && bioProfile"
                            :officer="bioOfficer"
                            :profile="bioProfile"
                            :other-officers="bioOtherOfficers"
                            :themes="bioThemes"
                            :button-styles="bioButtonStyles"
                            :allowed-domains="bioAllowedDomains"
                            :platforms="bioPlatforms"
                            :stats="bioStats"
                        />
                        <!-- Admin or user who isn't bound to an officer -->
                        <div v-else class="space-y-6">
                            <Card>
                                <CardContent class="p-8 sm:p-10 text-center space-y-4">
                                    <div class="max-w-xl mx-auto space-y-2">
                                        <h3 class="font-heading font-extrabold text-base sm:text-lg text-foreground">
                                            Profil Perwira Belum Terhubung
                                        </h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                            Akun Anda belum terhubung dengan profil perwira. Bio-link publik baru tersedia setelah akun diikat ke perwira oleh Admin SASP.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                                        <Button as-child size="sm" class="font-mono text-xs font-bold gap-2">
                                            <a href="/bio-link">
                                                <span>Pelajari & mulai verifikasi</span>
                                                <ArrowRight class="size-3.5" />
                                            </a>
                                        </Button>
                                        <Button
                                            v-if="props.biolinksAdminEmail"
                                            as-child
                                            variant="outline"
                                            size="sm"
                                            class="font-mono text-xs gap-2"
                                        >
                                            <a :href="`mailto:${props.biolinksAdminEmail}?subject=Bantuan%20Klaim%20Bio-Link%20Officer`">
                                                <Mail class="size-3.5" />
                                                <span>Hubungi Admin SASP</span>
                                            </a>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <div class="space-y-3">
                                <h4 class="font-mono text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                    Contoh Pilihan Tema Bio-Link:
                                </h4>
                                <BioExampleGallery :themes="bioThemes" :limit="3" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </PageContainer>
    </TacticalLayout>
</template>

