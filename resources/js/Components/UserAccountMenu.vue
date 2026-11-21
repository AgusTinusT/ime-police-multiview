<script setup>
import { ref, computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AccountLanguageSwitch from "@/Components/AccountLanguageSwitch.vue";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";
import OfficerVisibilityModal from "@/Components/OfficerVisibilityModal.vue";
import { OfficerAvatar } from "@/Components/tactical";
import { DONATE_URL } from "@/lib/siteLinks";
import { Badge } from "@/Components/ui/badge";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    ChevronDown,
    ChevronRight,
    Heart,
    Link2,
    LogIn,
    LogOut,
    Scissors,
    Settings,
    ShieldCheck,
    SlidersHorizontal,
    User,
} from "@lucide/vue";

const page = usePage();

const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === "admin" || !!user.value?.is_admin);

const showOfficerClaimModal = ref(false);
const showVisibilityModal = ref(false);

const handleLogout = () => {
    router.post("/logout");
};

// Shared look for the pill trigger, the highlighted cards and the plain rows.
const triggerClass =
    "flex h-10 shrink-0 items-center gap-2 rounded-full border border-foreground/10 bg-foreground/5 pl-1 pr-3 text-sm font-semibold text-foreground outline-none transition-colors hover:bg-foreground/10 focus-visible:ring-2 focus-visible:ring-ring data-[state=open]:border-primary/60 data-[state=open]:bg-primary/10";
const donateCardClass =
    "flex w-full cursor-pointer items-center gap-3 rounded-xl border border-live/40 bg-live/10 px-3 py-2.5 text-live focus:bg-live/20 focus:text-live";
const rowClass =
    "flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-foreground/90";
</script>

<template>
    <div class="relative flex shrink-0 items-center text-left">
        <!-- Logged-in State -->
        <template v-if="user">
            <DropdownMenu>
                <DropdownMenuTrigger :class="triggerClass" :title="`${user.name} (${user.role || 'member'})`">
                    <OfficerAvatar :src="user.avatar_url" :name="user.name" size="sm" class="size-8" />
                    <span class="hidden max-w-[140px] truncate sm:inline">{{ user.name }}</span>
                    <ChevronDown class="size-4 shrink-0 text-muted-foreground" />
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    align="end"
                    :side-offset="10"
                    class="w-80 space-y-1.5 rounded-2xl border-foreground/10 bg-popover/95 p-2 font-sans backdrop-blur-xl"
                >
                    <!-- Profile card -->
                    <DropdownMenuItem as-child class="p-0">
                        <Link href="/settings" class="flex w-full cursor-pointer items-center gap-3 rounded-xl border border-border bg-card p-3">
                            <OfficerAvatar :src="user.avatar_url" :name="user.name" size="md" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-foreground">{{ user.name }}</p>
                                <p class="truncate font-mono text-xs text-muted-foreground">{{ user.email }}</p>
                                <Badge :variant="isAdmin ? 'warning' : 'success'" class="mt-1.5 gap-1 font-extrabold uppercase">
                                    <ShieldCheck v-if="isAdmin" class="size-3 shrink-0" />
                                    <User v-else class="size-3 shrink-0" />
                                    {{ isAdmin ? "Admin Dispatcher" : "Member User" }}
                                </Badge>
                            </div>
                            <ChevronRight class="size-4 shrink-0 text-muted-foreground" />
                        </Link>
                    </DropdownMenuItem>

                    <!-- Highlighted cards -->
                    <DropdownMenuItem v-if="isAdmin" as-child class="p-0">
                        <Link
                            href="/admin/officers"
                            class="flex w-full cursor-pointer items-center gap-3 rounded-xl border border-warning/40 bg-warning/10 px-3 py-2.5 text-warning focus:bg-warning/20"
                        >
                            <ShieldCheck class="size-4 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold">Admin Command Hub</p>
                                <p class="truncate text-xs font-normal text-warning/80">Kelola perwira & pengumuman</p>
                            </div>
                            <ChevronRight class="size-4 shrink-0" />
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuItem v-if="user.is_officer || isAdmin" as-child class="p-0">
                        <Link
                            href="/dashboard/bio-links"
                            class="flex w-full cursor-pointer items-center gap-3 rounded-xl border border-primary/40 bg-primary/10 px-3 py-2.5 text-primary focus:bg-primary/20"
                        >
                            <Link2 class="size-4 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold">Tactical Bio-Link</p>
                                <p class="truncate text-xs font-normal text-primary/80">Kelola tautan, donasi & squad</p>
                            </div>
                            <ChevronRight class="size-4 shrink-0" />
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="!user.is_officer && !isAdmin"
                        class="flex w-full cursor-pointer items-center gap-3 rounded-xl border border-primary/40 bg-primary/10 px-3 py-2.5 text-primary focus:bg-primary/20 focus:text-primary"
                        @select="showOfficerClaimModal = true"
                    >
                        <ShieldCheck class="size-4 shrink-0 text-primary" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold">Klaim Profil Perwira</p>
                            <p class="truncate text-xs font-normal text-primary/80">Ajukan verifikasi akun dinas</p>
                        </div>
                        <ChevronRight class="size-4 shrink-0 text-primary" />
                    </DropdownMenuItem>

                    <DropdownMenuItem as-child class="p-0">
                        <a :href="DONATE_URL" target="_blank" rel="noopener noreferrer" :class="donateCardClass">
                            <Heart class="size-4 shrink-0 fill-current" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold">{{ $t("donate.title") }}</p>
                                <p class="truncate text-xs font-normal text-live/80">{{ $t("donate.hint") }}</p>
                            </div>
                            <ChevronRight class="size-4 shrink-0" />
                        </a>
                    </DropdownMenuItem>

                    <!-- Plain rows -->
                    <div class="pt-1">
                        <DropdownMenuItem as-child class="p-0">
                            <Link href="/clipper" :class="rowClass">
                                <Scissors class="size-4 shrink-0 text-muted-foreground" />
                                <span class="flex-1">Tactical Video Trimmer</span>
                                <Badge :variant="user.can_trim_video ? 'success' : 'secondary'" class="font-mono font-bold">
                                    {{ user.can_trim_video ? "PRO" : "Free" }}
                                </Badge>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child class="p-0">
                            <Link href="/settings" :class="rowClass">
                                <Settings class="size-4 shrink-0 text-muted-foreground" />
                                <span class="flex-1">{{ $t("settings.title") }}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem :class="rowClass" @select="showVisibilityModal = true">
                            <SlidersHorizontal class="size-4 shrink-0 text-muted-foreground" />
                            <span class="flex-1">{{ $t("catalog.manage_visibility") }}</span>
                        </DropdownMenuItem>
                    </div>

                    <DropdownMenuSeparator class="mx-1 my-1" />
                    <AccountLanguageSwitch />
                    <DropdownMenuSeparator class="mx-1 my-1" />

                    <DropdownMenuItem variant="destructive" :class="rowClass" @select="handleLogout">
                        <LogOut class="size-4 shrink-0" />
                        <span class="flex-1">Keluar (Sign Out)</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <!-- Guest State -->
        <template v-else>
            <DropdownMenu>
                <DropdownMenuTrigger :class="triggerClass" title="Menu Pengunjung & Masuk / Guest Menu & Sign In">
                    <span class="flex size-8 items-center justify-center rounded-full bg-foreground/10">
                        <User class="size-4 text-muted-foreground" />
                    </span>
                    <span class="hidden sm:inline">Guest</span>
                    <ChevronDown class="size-4 shrink-0 text-muted-foreground" />
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    align="end"
                    :side-offset="10"
                    class="w-80 space-y-1.5 rounded-2xl border-foreground/10 bg-popover/95 p-2 font-sans backdrop-blur-xl"
                >
                    <DropdownMenuItem as-child class="p-0">
                        <Link
                            href="/login"
                            class="flex w-full cursor-pointer items-center gap-3 rounded-xl border border-primary/40 bg-primary/10 px-3 py-3 text-primary focus:bg-primary/20"
                        >
                            <LogIn class="size-4 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold">Masuk / Sign In</p>
                                <p class="truncate text-xs font-normal text-primary/80">Simpan squad, klaim profil & bio-link</p>
                            </div>
                            <ChevronRight class="size-4 shrink-0" />
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuItem as-child class="p-0">
                        <a :href="DONATE_URL" target="_blank" rel="noopener noreferrer" :class="donateCardClass">
                            <Heart class="size-4 shrink-0 fill-current" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold">{{ $t("donate.title") }}</p>
                                <p class="truncate text-xs font-normal text-live/80">{{ $t("donate.hint") }}</p>
                            </div>
                            <ChevronRight class="size-4 shrink-0" />
                        </a>
                    </DropdownMenuItem>

                    <div class="pt-1">
                        <DropdownMenuItem as-child class="p-0">
                            <Link href="/clipper" :class="rowClass">
                                <Scissors class="size-4 shrink-0 text-muted-foreground" />
                                <span class="flex-1">Tactical Trimmer</span>
                                <Badge variant="secondary" class="font-mono font-bold">Demo</Badge>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem :class="rowClass" @select="showVisibilityModal = true">
                            <SlidersHorizontal class="size-4 shrink-0 text-muted-foreground" />
                            <span class="flex-1">{{ $t("catalog.manage_visibility") }}</span>
                        </DropdownMenuItem>
                    </div>

                    <DropdownMenuSeparator class="mx-1 my-1" />
                    <AccountLanguageSwitch />
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <!-- Officer Claim Modal (T-07) -->
        <OfficerClaimModal
            :show="showOfficerClaimModal"
            @close="showOfficerClaimModal = false"
        />

        <!-- Officer Visibility Management Modal -->
        <OfficerVisibilityModal
            :open="showVisibilityModal"
            @update:open="showVisibilityModal = $event"
            @close="showVisibilityModal = false"
        />
    </div>
</template>
