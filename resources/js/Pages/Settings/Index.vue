<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { PageContainer, PageHeader } from "@/Components/tactical";
import { Card, CardContent } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import AccountSettings from "@/Components/Settings/AccountSettings.vue";
import OfficerSettings from "@/Components/Settings/OfficerSettings.vue";
import { cn } from "@/lib/utils";
import {
    ExternalLink,
    Globe,
    Link2,
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

                    <!-- Tab 3: Tactical Bio-Link Hub Card (Officer Only) -->
                    <Card v-else-if="activeTab === 'biolink' && isOfficer">
                        <CardContent class="p-6 space-y-5">
                            <header class="space-y-1">
                                <h2 class="text-base font-semibold text-foreground">
                                    {{ $t("settings.tab_biolink") }}
                                </h2>
                                <p class="text-sm text-muted-foreground max-w-xl">
                                    {{ $t("settings.biolink_desc") }}
                                </p>
                            </header>

                            <!-- Public URL row: shown even when no slug yet, so the officer learns it exists -->
                            <div
                                v-if="bioSlug"
                                class="flex items-center justify-between gap-3 p-3.5 rounded-lg bg-muted/40 border border-border flex-wrap"
                            >
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <Globe class="size-4 text-muted-foreground shrink-0" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <p class="text-xs text-muted-foreground">
                                            {{ $t("settings.public_link") }}
                                        </p>
                                        <a
                                            :href="`/@${bioSlug}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-sm font-mono font-medium text-foreground hover:text-primary hover:underline truncate block"
                                        >
                                            /@{{ bioSlug }}
                                        </a>
                                    </div>
                                </div>

                                <Button as-child variant="outline" size="sm" class="shrink-0 cursor-pointer gap-1.5">
                                    <a
                                        :href="`/@${bioSlug}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <ExternalLink class="size-3.5" />
                                        <span>{{ $t("settings.open") }}</span>
                                    </a>
                                </Button>
                            </div>

                            <div
                                v-else
                                class="p-4 rounded-lg bg-muted/30 border border-dashed border-border text-sm text-muted-foreground"
                            >
                                {{ $t("settings.biolink_not_configured") }}
                            </div>

                            <div class="pt-1">
                                <Button as-child class="cursor-pointer gap-1.5">
                                    <Link href="/dashboard/bio-links">
                                        <Link2 class="size-4" />
                                        <span>{{ $t("settings.manage_biolink") }}</span>
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </PageContainer>
    </TacticalLayout>
</template>

