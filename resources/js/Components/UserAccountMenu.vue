<script setup>
import { ref, computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";
import OfficerVisibilityModal from "@/Components/OfficerVisibilityModal.vue";
import { OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    ChevronDown,
    Globe,
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
const { locale } = useI18n();

const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    return user?.role === "admin" || !!user?.is_admin;
});

const showOfficerClaimModal = ref(false);
const showVisibilityModal = ref(false);

const handleLogout = () => {
    router.post("/logout");
};

const setLanguage = (lang) => {
    locale.value = lang;
};
</script>

<template>
    <div class="relative flex items-center text-left shrink-0">
        <!-- Logged-in State -->
        <template v-if="$page.props.auth?.user">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1.5 px-2.5 shrink-0"
                        :title="`Logged in as ${$page.props.auth.user.name} (${$page.props.auth.user.role || 'member'})`"
                    >
                        <OfficerAvatar
                            :src="$page.props.auth.user.avatar_url"
                            :name="$page.props.auth.user.name"
                            size="sm"
                            class="size-5"
                        />
                        <span
                            class="text-xs font-bold text-foreground truncate max-w-[110px] sm:max-w-[140px] leading-none"
                        >
                            {{ $page.props.auth.user.name }}
                        </span>
                        <ChevronDown class="size-3.5 text-muted-foreground shrink-0" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-72 p-0 font-sans">
                    <!-- Header Profile Section -->
                    <DropdownMenuLabel class="p-4 flex items-start gap-3 font-normal">
                        <OfficerAvatar
                            :src="$page.props.auth.user.avatar_url"
                            :name="$page.props.auth.user.name"
                            size="md"
                        />

                        <div class="flex-1 min-w-0">
                            <h4
                                class="text-sm font-bold text-foreground truncate leading-snug"
                            >
                                {{ $page.props.auth.user.name }}
                            </h4>
                            <p
                                class="text-xs text-muted-foreground truncate mb-1.5 font-mono"
                            >
                                {{ $page.props.auth.user.email }}
                            </p>

                            <!-- Role Badge Pill -->
                            <Badge
                                :variant="isAdmin ? 'warning' : 'success'"
                                class="gap-1 font-extrabold uppercase"
                            >
                                <ShieldCheck v-if="isAdmin" class="size-3 shrink-0" />
                                <User v-else class="size-3 shrink-0" />
                                <span>
                                    {{
                                        isAdmin
                                            ? "Admin Dispatcher"
                                            : "Member User"
                                    }}
                                </span>
                            </Badge>
                        </div>
                    </DropdownMenuLabel>

                    <DropdownMenuSeparator class="m-0" />

                    <!-- Quick Navigation Action Links -->
                    <DropdownMenuGroup class="p-1.5">
                        <!-- Admin Command Hub (Admin Only) -->
                        <DropdownMenuItem v-if="isAdmin" as-child>
                            <Link
                                href="/admin/officers"
                                class="flex items-center px-3 py-2 text-warning gap-2.5 font-semibold cursor-pointer"
                            >
                                <ShieldCheck class="size-4 text-warning shrink-0" />
                                <div class="flex flex-col">
                                    <span class="text-xs">Admin Command Hub</span>
                                    <span class="text-xs text-muted-foreground font-normal">
                                        Kelola Perwira & Pengumuman
                                    </span>
                                </div>
                            </Link>
                        </DropdownMenuItem>

                        <!-- Tactical Bio-Link Hub (Officers & Admins) -->
                        <DropdownMenuItem
                            v-if="$page.props.auth.user.is_officer || isAdmin"
                            as-child
                        >
                            <Link
                                href="/dashboard/bio-links"
                                class="flex items-center px-3 py-2 text-info gap-2.5 font-semibold cursor-pointer"
                            >
                                <Link2 class="size-4 text-info shrink-0" />
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs">Tactical Bio-Link</span>
                                        <Badge variant="info" class="font-mono font-extrabold">
                                            HUB
                                        </Badge>
                                    </div>
                                    <span class="text-xs text-muted-foreground font-normal">
                                        Kelola Tautan, Donasi & Squad
                                    </span>
                                </div>
                            </Link>
                        </DropdownMenuItem>

                        <!-- Claim Officer Profile (For Members) -->
                        <DropdownMenuItem
                            v-if="!$page.props.auth.user.is_officer && !isAdmin"
                            class="flex items-center px-3 py-2 text-info gap-2.5 font-semibold cursor-pointer"
                            @select="showOfficerClaimModal = true"
                        >
                            <ShieldCheck class="size-4 text-info shrink-0" />
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs">Klaim Profil Perwira</span>
                                    <Badge variant="info" class="font-mono font-extrabold">
                                        VERIFIKASI
                                    </Badge>
                                </div>
                                <span class="text-xs text-muted-foreground font-normal">
                                    Ajukan Verifikasi Akun Dinas Anda
                                </span>
                            </div>
                        </DropdownMenuItem>

                        <!-- Tactical Video Trimmer Studio (All Users) -->
                        <DropdownMenuItem as-child>
                            <Link
                                href="/clipper"
                                class="flex items-center px-3 py-2 text-foreground gap-2.5 font-semibold cursor-pointer"
                            >
                                <Scissors class="size-4 text-muted-foreground shrink-0" />
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs">Tactical Video Trimmer</span>
                                        <Badge
                                            v-if="!$page.props.auth.user.can_trim_video"
                                            variant="secondary"
                                            class="font-mono font-bold"
                                        >
                                            Free
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="success"
                                            class="font-mono font-bold"
                                        >
                                            PRO
                                        </Badge>
                                    </div>
                                    <span class="text-xs text-muted-foreground font-normal">
                                        Studio Preview & Potong Video YouTube
                                    </span>
                                </div>
                            </Link>
                        </DropdownMenuItem>

                        <!-- Unified Settings (SET-01) -->
                        <DropdownMenuItem as-child>
                            <Link
                                href="/settings"
                                class="flex items-center px-3 py-2 text-foreground gap-2.5 cursor-pointer"
                            >
                                <Settings class="size-4 text-muted-foreground shrink-0" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium">{{ $t("settings.title") }}</span>
                                    <span class="text-xs text-muted-foreground font-normal">
                                        Akun, profil perwira & preferensi
                                    </span>
                                </div>
                            </Link>
                        </DropdownMenuItem>

                        <!-- Manage Officer Visibility -->
                        <DropdownMenuItem
                            class="flex items-center px-3 py-2 text-foreground gap-2.5 cursor-pointer"
                            @select="showVisibilityModal = true"
                        >
                            <SlidersHorizontal class="size-4 text-muted-foreground shrink-0" />
                            <div class="flex flex-col">
                                <span class="text-xs font-medium">{{ $t("catalog.manage_visibility") }}</span>
                                <span class="text-xs text-muted-foreground font-normal">
                                    Atur perwira yang disembunyikan
                                </span>
                            </div>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator class="m-0" />

                    <!-- Language Switcher Section -->
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2 px-1">
                            <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider font-mono flex items-center gap-1.5">
                                <Globe class="size-3.5" />
                                <span>Bahasa / Language</span>
                            </span>
                            <Badge variant="outline" class="font-mono">
                                {{ locale.toUpperCase() }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                :variant="locale === 'id' ? 'default' : 'outline'"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                                @click="setLanguage('id')"
                            >
                                <span class="font-mono font-extrabold">ID</span>
                                <span>Indonesia</span>
                            </Button>
                            <Button
                                type="button"
                                :variant="locale === 'en' ? 'default' : 'outline'"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                                @click="setLanguage('en')"
                            >
                                <span class="font-mono font-extrabold">EN</span>
                                <span>English</span>
                            </Button>
                        </div>
                    </div>

                    <DropdownMenuSeparator class="m-0" />

                    <!-- Footer Section: Sign Out Button -->
                    <div class="p-1.5">
                        <DropdownMenuItem
                            variant="destructive"
                            class="w-full justify-center gap-2 py-2 text-xs font-bold cursor-pointer"
                            @select="handleLogout"
                        >
                            <LogOut class="size-4" />
                            <span>Keluar (Sign Out)</span>
                        </DropdownMenuItem>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <!-- Guest State: Guest Button with Dropdown Trigger -->
        <template v-else>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1.5 px-3 shrink-0"
                        title="Menu Pengunjung & Masuk / Guest Menu & Sign In"
                    >
                        <User class="size-3.5 text-muted-foreground" />
                        <span class="text-xs font-bold text-foreground leading-none">
                            Guest
                        </span>
                        <ChevronDown class="size-3.5 text-muted-foreground shrink-0" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-64 p-0 font-sans">
                    <!-- Clipper Teaser Link & Sign In Action -->
                    <div class="p-3 space-y-2">
                        <DropdownMenuItem as-child>
                            <Link
                                href="/clipper"
                                class="flex items-center px-3 py-2 rounded-lg bg-background border border-border text-foreground gap-2 font-semibold text-xs cursor-pointer"
                            >
                                <Scissors class="size-4 text-primary shrink-0" />
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <span>Tactical Trimmer</span>
                                        <Badge variant="secondary" class="font-mono font-bold">
                                            Demo
                                        </Badge>
                                    </div>
                                    <span class="text-xs text-muted-foreground font-normal">
                                        Coba Potong Video YouTube Gratis
                                    </span>
                                </div>
                            </Link>
                        </DropdownMenuItem>

                        <DropdownMenuItem
                            class="flex items-center px-3 py-2 rounded-lg bg-background border border-border text-foreground gap-2 font-semibold text-xs cursor-pointer"
                            @select="showVisibilityModal = true"
                        >
                            <SlidersHorizontal class="size-4 text-muted-foreground shrink-0" />
                            <div class="flex flex-col">
                                <span class="text-xs font-medium">{{ $t("catalog.manage_visibility") }}</span>
                                <span class="text-xs text-muted-foreground font-normal">
                                    Atur perwira yang disembunyikan
                                </span>
                            </div>
                        </DropdownMenuItem>

                        <Button as-child size="sm" class="w-full font-bold text-xs">
                            <Link href="/login">
                                <LogIn class="size-4" />
                                <span>Masuk / Sign In</span>
                            </Link>
                        </Button>
                    </div>

                    <DropdownMenuSeparator class="m-0" />

                    <!-- Language Switcher Section -->
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2 px-1">
                            <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider font-mono flex items-center gap-1.5">
                                <Globe class="size-3.5" />
                                <span>Bahasa / Language</span>
                            </span>
                            <Badge variant="outline" class="font-mono">
                                {{ locale.toUpperCase() }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                :variant="locale === 'id' ? 'default' : 'outline'"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                                @click="setLanguage('id')"
                            >
                                <span class="font-mono font-extrabold">ID</span>
                                <span>Indonesia</span>
                            </Button>
                            <Button
                                type="button"
                                :variant="locale === 'en' ? 'default' : 'outline'"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                                @click="setLanguage('en')"
                            >
                                <span class="font-mono font-extrabold">EN</span>
                                <span>English</span>
                            </Button>
                        </div>
                    </div>
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
