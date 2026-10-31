<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { cn } from "@/lib/utils";
import {
    Check,
    ChevronDown,
    Gauge,
    House,
    LayoutGrid,
    Maximize,
    Monitor,
    RefreshCw,
    Search,
    Settings,
    Volume2,
    VolumeX,
    X,
} from "@lucide/vue";

const props = defineProps({
    selectedDepartment: { type: String, required: true },
    selectedLayout: { type: String, default: "auto" },
    searchFilter: { type: String, default: "" },
    isDataSaverEnabled: { type: Boolean, default: false },
    isSyncingFeeds: { type: Boolean, default: false },
    isTheaterMode: { type: Boolean, default: false },
    activeAudioVideoId: { type: String, default: null },
    visibleStreamsCount: { type: Number, default: 0 },
    getDeptCount: { type: Function, required: true },
    selectedDeptLabel: { type: String, default: "ALL" },
    selectedLayoutLabel: { type: String, default: "Auto Grid" },
    primaryFocusedStream: { type: Object, default: null },
});

const emit = defineEmits([
    "update:selectedDepartment",
    "update:selectedLayout",
    "update:searchFilter",
    "trigger-manual-sync",
    "toggle-fullscreen",
    "toggle-theater-mode",
    "toggle-audio",
    "disable-data-saver",
    "enable-data-saver",
    "open-officer-visibility",
]);

const isDockCategoryOpen = ref(false);
const isDockLayoutOpen = ref(false);
const isDockSearchOpen = ref(false);

const toggleDockCategory = () => {
    isDockCategoryOpen.value = !isDockCategoryOpen.value;
    if (isDockCategoryOpen.value) {
        isDockLayoutOpen.value = false;
        isDockSearchOpen.value = false;
    }
};

const toggleDockLayout = () => {
    isDockLayoutOpen.value = !isDockLayoutOpen.value;
    if (isDockLayoutOpen.value) {
        isDockCategoryOpen.value = false;
        isDockSearchOpen.value = false;
    }
};

const toggleDockSearch = () => {
    isDockSearchOpen.value = !isDockSearchOpen.value;
    if (isDockSearchOpen.value) {
        isDockCategoryOpen.value = false;
        isDockLayoutOpen.value = false;
    }
};

const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return iconSasp;
    if (d.includes("SAPR") || d.includes("RANGER")) return iconSapr;
    return iconAllUnits;
};
</script>

<template>
    <TooltipProvider :delay-duration="200">
        <div
            v-if="selectedLayout !== 'focus'"
            class="fixed bottom-0 inset-x-0 z-50 flex flex-col items-center pointer-events-none"
        >
            <!-- Popover 1: Department & Category Selector Menu -->
            <Card
                v-if="isDockCategoryOpen"
                class="hidden sm:block pointer-events-auto mb-2.5 w-80 sm:w-96 max-h-[70vh] overflow-y-auto p-3 text-xs space-y-3 animate-in fade-in zoom-in-95 font-sans"
                @click.stop
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-border text-foreground/80 font-bold font-mono"
                >
                    <span class="flex items-center gap-1.5 text-primary">
                        <LayoutGrid class="size-4" />
                        <span>PILIH KATEGORI / DEPARTEMEN</span>
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-6"
                        aria-label="Tutup pemilih kategori"
                        @click="isDockCategoryOpen = false"
                    >
                        <X class="size-3.5" />
                    </Button>
                </div>

                <!-- Main Departments Grid -->
                <div class="grid grid-cols-2 gap-1.5">
                    <button
                        v-for="dItem in [
                            {
                                id: 'ALL',
                                label: 'All units',
                                icon: iconAllUnits,
                            },
                            {
                                id: 'LSPD',
                                label: 'LSPD Metro',
                                icon: iconLspd,
                            },
                            {
                                id: 'BCSO',
                                label: 'BCSO Sheriff',
                                icon: iconBcso,
                            },
                            {
                                id: 'SASP',
                                label: 'SASP State',
                                icon: iconSasp,
                            },
                            {
                                id: 'SAPR',
                                label: 'SAPR Ranger',
                                icon: iconSapr,
                            },
                        ]"
                        :key="dItem.id"
                        type="button"
                        :class="
                            cn(
                                'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                                selectedDepartment === dItem.id
                                    ? 'bg-primary text-primary-foreground border-primary font-bold'
                                    : 'bg-background hover:bg-accent text-foreground/80 border-border',
                            )
                        "
                        @click="
                            emit('update:selectedDepartment', dItem.id);
                            isDockCategoryOpen = false;
                        "
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="dItem.icon"
                                class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                                alt=""
                            />
                            <span>{{ dItem.label }}</span>
                        </div>
                        <Badge
                            variant="secondary"
                            class="px-1.5 py-0 text-xs font-mono font-bold"
                        >
                            {{ getDeptCount(dItem.id) }}
                        </Badge>
                    </button>
                </div>
            </Card>

            <!-- Popover 2: Grid Layout Selector Menu -->
            <Card
                v-if="isDockLayoutOpen"
                class="hidden sm:block pointer-events-auto mb-2.5 w-64 p-3 text-xs space-y-2 animate-in fade-in zoom-in-95 font-sans"
                @click.stop
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-border text-foreground/80 font-bold font-mono"
                >
                    <span>TATA LETAK GRID (LAYOUT)</span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-6"
                        aria-label="Tutup pemilih layout"
                        @click="isDockLayoutOpen = false"
                    >
                        <X class="size-3.5" />
                    </Button>
                </div>
                <div class="space-y-1">
                    <button
                        v-for="l in [
                            {
                                id: 'auto',
                                name: 'Auto Grid',
                                desc: 'Adaptif sesuai jumlah stream',
                            },
                            {
                                id: 'grid-1x2',
                                name: '1 x 2 Grid',
                                desc: '2 Kamera bersampingan',
                            },
                            {
                                id: 'grid-2x2',
                                name: '2 x 2 Grid',
                                desc: '4 Kamera Quad Matrix',
                            },
                            {
                                id: 'grid-3x3',
                                name: '3 x 3 Grid',
                                desc: '9 Kamera Matrix',
                            },
                            {
                                id: 'grid-4x4',
                                name: '4 x 4 Grid',
                                desc: '16 Kamera Matrix',
                            },
                        ]"
                        :key="l.id"
                        type="button"
                        :class="
                            cn(
                                'w-full p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                                selectedLayout === l.id
                                    ? 'bg-primary text-primary-foreground border-primary font-bold'
                                    : 'bg-background hover:bg-accent text-foreground/80 border-border',
                            )
                        "
                        @click="
                            emit('update:selectedLayout', l.id);
                            isDockLayoutOpen = false;
                        "
                    >
                        <div>
                            <div class="font-bold text-xs">
                                {{ l.name }}
                            </div>
                            <div class="text-xs text-muted-foreground font-light">
                                {{ l.desc }}
                            </div>
                        </div>
                        <Check
                            v-if="selectedLayout === l.id"
                            class="size-3.5"
                        />
                    </button>
                </div>
            </Card>

            <!-- Popover 3: Quick Search Filter Input Bar -->
            <Card
                v-if="isDockSearchOpen"
                class="pointer-events-auto mb-2.5 w-72 sm:w-80 p-2.5 text-xs animate-in fade-in zoom-in-95 font-sans"
                @click.stop
            >
                <div class="relative flex items-center">
                    <Input
                        type="text"
                        :model-value="searchFilter"
                        placeholder="Cari perwira, callsign, divisi, judul..."
                        class="w-full bg-background text-foreground text-xs pr-8 h-9"
                        @update:model-value="
                            emit('update:searchFilter', $event)
                        "
                    />
                    <Button
                        v-if="searchFilter"
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute right-1.5 size-6 text-muted-foreground hover:text-foreground"
                        aria-label="Hapus pencarian"
                        @click="emit('update:searchFilter', '')"
                    >
                        <X class="size-3.5" />
                    </Button>
                </div>
            </Card>

            <!-- BOTTOM STICKY CONTROL BAR (DESKTOP ONLY) -->
            <div
                class="hidden sm:flex pointer-events-auto w-full bg-background border-t border-border px-4 py-2.5 items-center justify-center gap-3 text-xs text-foreground overflow-x-auto scrollbar-none"
                @click.stop
            >
                <!-- Home / Beranda Button -->
                <Button as-child variant="outline" size="sm">
                    <Link
                        href="/"
                        class="text-xs font-mono font-bold gap-1.5 shrink-0"
                        title="Kembali ke Dashboard Utama"
                    >
                        <House class="size-3.5 text-muted-foreground shrink-0" />
                        <span>Home</span>
                    </Link>
                </Button>

                <!-- Category Selector Button -->
                <Button
                    type="button"
                    :variant="isDockCategoryOpen ? 'secondary' : 'outline'"
                    size="sm"
                    class="hidden sm:flex text-xs font-bold gap-1.5 shrink-0"
                    title="Pilih Kategori / Departemen Kesatuan"
                    @click="toggleDockCategory"
                >
                    <img
                        :src="getDeptIcon(selectedDepartment)"
                        class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                        alt=""
                    />
                    <span class="font-mono text-xs">{{
                        selectedDeptLabel
                    }}</span>
                    <Badge
                        variant="outline"
                        class="px-1.5 py-0 text-primary text-xs font-mono font-bold"
                    >
                        {{ visibleStreamsCount }}
                    </Badge>
                    <ChevronDown class="size-3 text-muted-foreground" />
                </Button>

                <div
                    class="hidden sm:block h-4 w-px bg-border shrink-0"
                ></div>

                <!-- Layout Selector Button -->
                <Button
                    type="button"
                    :variant="isDockLayoutOpen ? 'secondary' : 'outline'"
                    size="sm"
                    class="hidden sm:flex text-xs font-bold gap-1.5 shrink-0"
                    title="Pilih Tata Letak Kamera (Grid Layout)"
                    @click="toggleDockLayout"
                >
                    <LayoutGrid class="size-3.5 shrink-0" />
                    <span class="font-mono text-xs">{{
                        selectedLayoutLabel
                    }}</span>
                    <ChevronDown class="size-3 text-muted-foreground" />
                </Button>

                <div
                    class="hidden sm:block h-4 w-px bg-border shrink-0"
                ></div>

                <!-- Quick Search Filter Button -->
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            type="button"
                            :variant="
                                isDockSearchOpen || searchFilter
                                    ? 'secondary'
                                    : 'outline'
                            "
                            size="icon"
                            class="size-8 relative shrink-0"
                            aria-label="Cari Petugas / Stream"
                            @click="toggleDockSearch"
                        >
                            <Search class="size-3.5" />
                            <span
                                v-if="searchFilter"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-primary rounded-full"
                            ></span>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Cari Petugas / Stream</TooltipContent>
                </Tooltip>

                <!-- Officer Visibility Bottom Sheet Button -->
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="text-xs font-mono font-bold gap-1.5 shrink-0"
                    title="Kelola Tampilan / Enable Disable Perwira"
                    @click="emit('open-officer-visibility')"
                >
                    <Settings class="size-3.5 text-muted-foreground shrink-0" />
                    <span>Kelola</span>
                </Button>

                <!-- Sync / Refresh Feeds Button -->
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="size-8 shrink-0"
                            :disabled="isSyncingFeeds"
                            aria-label="Sinkronkan Feeds Live Terbaru"
                            @click="emit('trigger-manual-sync')"
                        >
                            <RefreshCw
                                class="size-3.5"
                                :class="{ 'animate-spin': isSyncingFeeds }"
                            />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        Sinkronkan Feeds Live Terbaru
                    </TooltipContent>
                </Tooltip>

                <!-- Data Saver Toggle Button -->
                <Button
                    type="button"
                    :variant="isDataSaverEnabled ? 'secondary' : 'outline'"
                    size="sm"
                    :class="
                        cn(
                            'text-xs font-mono font-bold gap-1.5 shrink-0',
                            isDataSaverEnabled ? 'text-warning' : '',
                        )
                    "
                    :title="
                        isDataSaverEnabled
                            ? 'Hemat Data Aktif (Tutup untuk otomatis play)'
                            : 'Aktifkan Hemat Data'
                    "
                    @click="
                        isDataSaverEnabled
                            ? emit('disable-data-saver')
                            : emit('enable-data-saver')
                    "
                >
                    <Gauge class="size-3.5" />
                    <span>{{
                        isDataSaverEnabled ? "Saver ON" : "Saver OFF"
                    }}</span>
                </Button>

                <div
                    class="hidden sm:block h-4 w-px bg-border shrink-0"
                ></div>

                <!-- Theater / Bioskop Mode Toggle -->
                <Button
                    type="button"
                    :variant="isTheaterMode ? 'secondary' : 'outline'"
                    size="sm"
                    :class="
                        cn(
                            'hidden sm:flex text-xs font-mono font-bold gap-1.5 shrink-0',
                            isTheaterMode ? 'text-primary' : '',
                        )
                    "
                    :title="
                        isTheaterMode
                            ? 'Mode Bioskop Aktif (100% Lebar Layar)'
                            : 'Mode Bioskop (Perluas Layar 100% Full Width)'
                    "
                    @click="emit('toggle-theater-mode')"
                >
                    <Monitor class="size-3.5 shrink-0" />
                    <span>Bioskop</span>
                </Button>

                <!-- Audio Switch Button -->
                <Button
                    type="button"
                    :variant="
                        (selectedLayout === 'focus' &&
                            primaryFocusedStream &&
                            activeAudioVideoId ===
                                primaryFocusedStream.video_id) ||
                        (selectedLayout !== 'focus' && activeAudioVideoId)
                            ? 'secondary'
                            : 'outline'
                    "
                    size="sm"
                    :class="
                        cn(
                            'text-xs font-mono font-bold gap-1.5 shrink-0',
                            (selectedLayout === 'focus' &&
                                primaryFocusedStream &&
                                activeAudioVideoId ===
                                    primaryFocusedStream.video_id) ||
                                (selectedLayout !== 'focus' &&
                                    activeAudioVideoId)
                                ? 'text-success'
                                : '',
                        )
                    "
                    :title="
                        (selectedLayout === 'focus' &&
                            primaryFocusedStream &&
                            activeAudioVideoId ===
                                primaryFocusedStream.video_id) ||
                        (selectedLayout !== 'focus' && activeAudioVideoId)
                            ? 'Suara Aktif (Klik untuk Mute)'
                            : 'Aktivasi Suara Feed'
                    "
                    @click="
                        emit(
                            'toggle-audio',
                            selectedLayout === 'focus' && primaryFocusedStream
                                ? primaryFocusedStream.video_id
                                : activeAudioVideoId || null,
                        )
                    "
                >
                    <Volume2
                        v-if="
                            (selectedLayout === 'focus' &&
                                primaryFocusedStream &&
                                activeAudioVideoId ===
                                    primaryFocusedStream.video_id) ||
                            (selectedLayout !== 'focus' && activeAudioVideoId)
                        "
                        class="size-3.5"
                    />
                    <VolumeX v-else class="size-3.5" />
                    <span>{{
                        (selectedLayout === "focus" &&
                            primaryFocusedStream &&
                            activeAudioVideoId ===
                                primaryFocusedStream.video_id) ||
                        (selectedLayout !== "focus" && activeAudioVideoId)
                            ? "Audio ON"
                            : "Audio OFF"
                    }}</span>
                </Button>

                <!-- Fullscreen Toggle -->
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="hidden sm:flex size-8 shrink-0"
                            aria-label="Toggle Layar Penuh (Fullscreen)"
                            @click="emit('toggle-fullscreen')"
                        >
                            <Maximize class="size-3.5" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        Toggle Layar Penuh (Fullscreen)
                    </TooltipContent>
                </Tooltip>
            </div>
        </div>
    </TooltipProvider>
</template>
