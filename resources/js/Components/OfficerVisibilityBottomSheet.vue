<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import {
    Settings,
    X,
    Search,
    Pin,
    ChevronUp,
    ChevronDown,
    RotateCcw,
} from "@lucide/vue";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { DeptBadge, OfficerAvatar } from "@/Components/tactical";

const { t } = useI18n();

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    allActiveStreams: {
        type: Array,
        default: () => [],
    },
    offlineOfficers: {
        type: Array,
        default: () => [],
    },
    customStreamOrder: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "close",
    "toggle-priority",
    "move-priority-up",
    "move-priority-down",
    "reset-priority",
]);

const {
    getOfficerKey,
    isOfficerDisabled,
    toggleOfficerStatus,
    resetAllFilters,
} = useOfficerFilter();

const searchQuery = ref("");
const selectedDeptFilter = ref("ALL");

const getOfficerRank = (rawItem) => {
    if (!rawItem || !props.customStreamOrder) return 0;
    const key = getOfficerKey(rawItem);
    if (!key) return 0;

    const idx = props.customStreamOrder.indexOf(key);
    if (idx !== -1) return idx + 1;

    if (rawItem.video_id) {
        const vIdx = props.customStreamOrder.indexOf(
            String(rawItem.video_id).trim(),
        );
        if (vIdx !== -1) return vIdx + 1;
    }

    return 0;
};

const combinedOfficers = computed(() => {
    const list = [];
    const seenKeys = new Set();

    (props.allActiveStreams || []).forEach((stream) => {
        const key = getOfficerKey(stream);
        if (key && !seenKeys.has(key)) {
            seenKeys.add(key);
            list.push({
                key,
                raw: stream,
                officer_name:
                    stream.officer?.officer_name ||
                    stream.streamer_name ||
                    "Officer",
                handle: stream.officer?.handle || stream.channel_id || "",
                department: stream.officer?.department || "UNIT",
                callsign: stream.officer?.callsign || "",
                avatar_url:
                    stream.officer?.avatar_url ||
                    stream.avatar ||
                    `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(key)}`,
                is_live: true,
                live_viewers: stream.viewers_count || 0,
            });
        }
    });

    (props.offlineOfficers || []).forEach((officer) => {
        const key = getOfficerKey(officer);
        if (key && !seenKeys.has(key)) {
            seenKeys.add(key);
            list.push({
                key,
                raw: officer,
                officer_name: officer.officer_name || officer.name || "Officer",
                handle: officer.handle || officer.channel_id || "",
                department: officer.department || "UNIT",
                callsign: officer.callsign || "",
                avatar_url:
                    officer.avatar_url ||
                    `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(key)}`,
                is_live: false,
                live_viewers: 0,
            });
        }
    });

    return list;
});

const filteredOfficersList = computed(() => {
    return combinedOfficers.value.filter((officer) => {
        if (selectedDeptFilter.value !== "ALL") {
            const d = (officer.department || "").toUpperCase();
            if (selectedDeptFilter.value === "SAPR") {
                if (d !== "SAPR" && d !== "PARK RANGER") return false;
            } else if (d !== selectedDeptFilter.value) {
                return false;
            }
        }

        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const nameMatch = officer.officer_name.toLowerCase().includes(q);
            const deptMatch = officer.department.toLowerCase().includes(q);
            const callsignMatch = officer.callsign.toLowerCase().includes(q);
            const handleMatch = officer.handle.toLowerCase().includes(q);
            return nameMatch || deptMatch || callsignMatch || handleMatch;
        }

        return true;
    });
});

const totalOfficerCount = computed(() => combinedOfficers.value.length);
const disabledCount = computed(() => {
    return combinedOfficers.value.filter((o) => isOfficerDisabled(o.raw))
        .length;
});
const activeCount = computed(
    () => totalOfficerCount.value - disabledCount.value,
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 bg-background/90 backdrop-blur-xs transform-gpu flex flex-col justify-end sm:justify-center items-center p-0 sm:p-4"
                @click.self="emit('close')"
            >
                <!-- Bottom Sheet Drawer Panel (Mobile Slide Up, Desktop Center Box) -->
                <Transition
                    enter-active-class="transition duration-200 ease-out transform-gpu"
                    enter-from-class="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
                    enter-to-class="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in transform-gpu"
                    leave-from-class="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
                    leave-to-class="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
                >
                    <div
                        v-if="isOpen"
                        class="w-full sm:max-w-xl bg-card border-t sm:border border-border rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[85vh] sm:max-h-[80vh] flex flex-col font-sans transform-gpu will-change-transform"
                    >
                        <!-- Mobile Drag Indicator Bar -->
                        <div
                            class="pt-2 pb-1 bg-card shrink-0 sm:hidden flex justify-center cursor-pointer"
                            @click="emit('close')"
                        >
                            <div
                                class="w-12 h-1.5 bg-muted-foreground/40 rounded-full"
                            ></div>
                        </div>

                        <!-- Panel Header -->
                        <div
                            class="p-4 bg-background border-b border-border flex items-center justify-between gap-3 shrink-0"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="p-2 rounded-md bg-card border border-border text-primary shrink-0"
                                >
                                    <Settings class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <h3
                                        class="text-sm font-bold text-foreground tracking-tight uppercase flex items-center gap-2"
                                    >
                                        <span>{{
                                            $t("visibility_panel.title")
                                        }}</span>
                                    </h3>
                                    <p
                                        class="text-xs font-mono text-muted-foreground truncate flex items-center gap-1.5"
                                    >
                                        <span class="text-success font-semibold"
                                            >{{ activeCount }}
                                            {{
                                                $t("visibility_panel.shown")
                                            }}</span
                                        >
                                        <span>•</span>
                                        <span
                                            class="text-destructive font-semibold"
                                            >{{ disabledCount }}
                                            {{
                                                $t("visibility_panel.hidden")
                                            }}</span
                                        >
                                        <span>•</span>
                                        <span class="text-warning font-semibold"
                                            >{{
                                                (customStreamOrder || []).length
                                            }}
                                            {{
                                                $t("visibility_panel.priority")
                                            }}</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                @click="emit('close')"
                                class="h-8 w-8 shrink-0"
                                aria-label="Tutup Panel"
                                title="Tutup Panel"
                            >
                                <X class="w-4 h-4" />
                            </Button>
                        </div>

                        <!-- Filter Controls Bar (Department Tabs & Search Input) -->
                        <div
                            class="p-3 bg-background/60 border-b border-border space-y-2.5 shrink-0"
                        >
                            <!-- Search & Action Buttons -->
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1 items-center">
                                    <Input
                                        v-model="searchQuery"
                                        type="text"
                                        :placeholder="
                                            $t(
                                                'visibility_panel.search_placeholder',
                                            )
                                        "
                                        class="w-full h-8 pl-8 pr-7 text-xs font-mono"
                                    />
                                    <Search
                                        class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground pointer-events-none"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        type="button"
                                        @click="searchQuery = ''"
                                        aria-label="Hapus pencarian"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground text-xs font-bold leading-none p-0.5 rounded-sm cursor-pointer"
                                    >
                                        <X class="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <Button
                                    v-if="disabledCount > 0"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="resetAllFilters"
                                    class="h-8 px-3 text-primary font-mono text-xs font-semibold shrink-0"
                                    title="Tampilkan Kembali Semua Perwira"
                                >
                                    <span>{{
                                        $t("visibility_panel.reset")
                                    }}</span>
                                </Button>
                            </div>

                            <!-- Department Pills Selector -->
                            <div
                                class="flex items-center gap-1.5 overflow-x-auto whitespace-nowrap scrollbar-none py-0.5 font-mono text-xs"
                            >
                                <Button
                                    v-for="dept in [
                                        'ALL',
                                        'LSPD',
                                        'BCSO',
                                        'SASP',
                                        'SAPR',
                                    ]"
                                    :key="dept"
                                    type="button"
                                    size="sm"
                                    :variant="
                                        selectedDeptFilter === dept
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    @click="selectedDeptFilter = dept"
                                    :class="
                                        selectedDeptFilter === dept
                                            ? 'text-primary font-bold'
                                            : 'text-muted-foreground'
                                    "
                                    class="h-7 px-2.5 text-xs"
                                >
                                    {{
                                        dept === "ALL"
                                            ? $t("visibility_panel.all_depts")
                                            : dept
                                    }}
                                </Button>
                            </div>
                        </div>

                        <!-- Officer List (Scrollable Content) -->
                        <div
                            class="p-3 overflow-y-auto space-y-2 flex-1 scrollbar-thin"
                        >
                            <div
                                v-if="filteredOfficersList.length === 0"
                                class="p-8 text-center text-muted-foreground font-mono text-xs"
                            >
                                {{ $t("visibility_panel.no_officers_found") }}
                            </div>

                            <div
                                v-for="officer in filteredOfficersList"
                                :key="officer.key"
                                class="p-2.5 rounded-lg border transition-colors flex items-center justify-between gap-2.5"
                                :class="[
                                    isOfficerDisabled(officer.raw)
                                        ? 'bg-background/60 border-border/60 opacity-50'
                                        : 'bg-background border-border hover:border-primary/40',
                                ]"
                            >
                                <!-- Left Info (Rank Badge, Avatar, Name, Dept, Callsign) -->
                                <div
                                    class="flex items-center gap-2.5 min-w-0 flex-1"
                                >
                                    <Badge
                                        v-if="getOfficerRank(officer.raw) > 0"
                                        variant="outline"
                                        class="px-1.5 py-0 text-xs font-mono font-bold bg-warning/15 text-warning border-warning/40 shrink-0"
                                        title="Urutan Prioritas Penayangan Grid"
                                    >
                                        #{{ getOfficerRank(officer.raw) }}
                                    </Badge>

                                    <div class="relative shrink-0">
                                        <OfficerAvatar
                                            :src="officer.avatar_url"
                                            :name="officer.officer_name"
                                            :callsign="officer.callsign"
                                            :dept="officer.department"
                                            size="sm"
                                        />
                                        <span
                                            class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-background"
                                            :class="
                                                officer.is_live
                                                    ? 'bg-live'
                                                    : 'bg-muted-foreground'
                                            "
                                        ></span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex items-center gap-1.5 flex-wrap"
                                        >
                                            <span
                                                class="text-xs font-bold text-foreground truncate"
                                            >
                                                {{ officer.officer_name }}
                                            </span>
                                            <DeptBadge
                                                :dept="officer.department"
                                            />
                                        </div>
                                        <p
                                            class="text-xs font-mono text-muted-foreground truncate"
                                        >
                                            {{
                                                officer.callsign ||
                                                officer.handle
                                            }}
                                            <span
                                                v-if="officer.is_live"
                                                class="text-live font-semibold ml-1"
                                                >LIVE</span
                                            >
                                            <span
                                                v-else
                                                class="text-muted-foreground ml-1"
                                                >Standby</span
                                            >
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Controls: Priority Pin + Up/Down + Tampil/Sembunyi -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <div
                                        v-if="!isOfficerDisabled(officer.raw)"
                                        class="flex items-center gap-1"
                                    >
                                        <Button
                                            type="button"
                                            size="icon-sm"
                                            :variant="
                                                getOfficerRank(officer.raw) > 0
                                                    ? 'secondary'
                                                    : 'outline'
                                            "
                                            @click="
                                                emit(
                                                    'toggle-priority',
                                                    officer.raw,
                                                )
                                            "
                                            :class="
                                                getOfficerRank(officer.raw) > 0
                                                    ? 'text-warning'
                                                    : 'text-muted-foreground hover:text-warning'
                                            "
                                            class="h-7 w-7"
                                            :aria-label="
                                                getOfficerRank(officer.raw) > 0
                                                    ? 'Lepas dari Prioritas Utama'
                                                    : 'Pin ke Prioritas Utama'
                                            "
                                            :title="
                                                getOfficerRank(officer.raw) > 0
                                                    ? 'Lepas dari Prioritas Utama'
                                                    : 'Pin ke Prioritas Utama (#1, #2, #3...)'
                                            "
                                        >
                                            <Pin class="w-3.5 h-3.5" />
                                        </Button>

                                        <div
                                            v-if="
                                                getOfficerRank(officer.raw) > 0
                                            "
                                            class="flex flex-col gap-0.5"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    emit(
                                                        'move-priority-up',
                                                        officer.raw,
                                                    )
                                                "
                                                aria-label="Naikkan Urutan Prioritas"
                                                class="px-1 py-0 rounded-sm text-xs bg-card hover:bg-muted text-foreground border border-border leading-none cursor-pointer"
                                                title="Naikkan Urutan Prioritas"
                                            >
                                                <ChevronUp
                                                    class="w-2.5 h-2.5"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                @click="
                                                    emit(
                                                        'move-priority-down',
                                                        officer.raw,
                                                    )
                                                "
                                                aria-label="Turunkan Urutan Prioritas"
                                                class="px-1 py-0 rounded-sm text-xs bg-card hover:bg-muted text-foreground border border-border leading-none cursor-pointer"
                                                title="Turunkan Urutan Prioritas"
                                            >
                                                <ChevronDown
                                                    class="w-2.5 h-2.5"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Enable/Disable Switch Toggle -->
                                    <Button
                                        type="button"
                                        size="sm"
                                        :variant="
                                            isOfficerDisabled(officer.raw)
                                                ? 'outline'
                                                : 'secondary'
                                        "
                                        @click="
                                            toggleOfficerStatus(officer.raw)
                                        "
                                        :class="
                                            isOfficerDisabled(officer.raw)
                                                ? 'text-muted-foreground'
                                                : 'text-success'
                                        "
                                        class="h-8 px-3 text-xs font-mono font-medium shrink-0"
                                        :title="
                                            isOfficerDisabled(officer.raw)
                                                ? 'Klik untuk menampilkan perwira'
                                                : 'Klik untuk menyembunyikan perwira'
                                        "
                                    >
                                        <span>{{
                                            isOfficerDisabled(officer.raw)
                                                ? "Sembunyi"
                                                : "Tampil"
                                        }}</span>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Bottom Footer -->
                        <div
                            class="p-3 bg-background border-t border-border flex items-center justify-between gap-2 shrink-0 text-xs font-mono"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-xs">
                                    Filter tersimpan otomatis di browser
                                </span>
                                <Button
                                    v-if="(customStreamOrder || []).length > 0"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="emit('reset-priority')"
                                    class="h-7 px-2 text-warning text-xs gap-1"
                                    title="Reset seluruh urutan prioritas ke standar"
                                >
                                    <RotateCcw class="w-3 h-3" />
                                    <span>Reset Prioritas</span>
                                </Button>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                @click="emit('close')"
                                class="h-8 px-4 font-semibold"
                            >
                                Selesai
                            </Button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
