<script setup>
import {
    Star,
    PlusCircle,
    Search,
    RefreshCw,
    ExternalLink,
    RotateCcw,
    Link2,
    Trash2,
    User,
    X,
    Check,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    quickAddMode: { type: String, default: "SEARCH" },
    liveSearchQuery: { type: String, default: "" },
    isLiveSearching: { type: Boolean, default: false },
    liveSearchError: { type: String, default: null },
    liveSearchSuccessNotice: { type: String, default: null },
    liveSearchResults: { type: Array, default: () => [] },
    popularHashtagPresets: { type: Array, default: () => [] },
    quickAddInput: {
        type: Object,
        default: () => ({
            urlOrId: "",
            officerName: "",
            callsign: "",
            department: "LSPD",
            patrolZone: "Mission Row",
        }),
    },
    totalPersonalCount: { type: Number, default: 0 },
    totalSavedPersonalCount: { type: Number, default: 0 },
    savedPersonalList: { type: Array, default: () => [] },
    personalStreamIds: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "close",
    "update:quickAddMode",
    "update:liveSearchQuery",
    "search-live-streams",
    "add-live-stream-to-personal",
    "quick-add-stream",
    "clear-all-personal",
    "remove-personal-stream",
]);

const isPersonalStream = (channelOrHandle) => {
    if (!channelOrHandle) return false;
    const target = String(channelOrHandle).trim().toLowerCase();
    return (props.personalStreamIds || []).some(
        (id) => String(id).trim().toLowerCase() === target,
    );
};
</script>

<template>
    <div>
        <!-- Backdrop Overlay -->
        <div
            v-if="isOpen"
            @click="emit('close')"
            class="fixed inset-0 bg-background/70 backdrop-blur-xs z-50 transition-opacity duration-300"
        ></div>

        <!-- Sliding Drawer Panel -->
        <aside
            class="fixed inset-y-0 right-0 z-50 bg-background border-l border-border flex flex-col transition-all duration-300 ease-in-out w-full sm:w-[440px] md:w-[480px]"
            :class="[
                isOpen
                    ? 'translate-x-0'
                    : 'translate-x-full pointer-events-none',
            ]"
        >
            <!-- Drawer Top Header Bar -->
            <div
                class="bg-card px-4 py-3.5 border-b border-border flex items-center justify-between shrink-0"
            >
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-base shrink-0 border bg-success/15 border-success/40 text-success"
                    >
                        <PlusCircle class="w-4 h-4" />
                    </div>
                    <div class="truncate">
                        <div class="flex items-center space-x-2">
                            <h2
                                class="text-xs font-extrabold tracking-wider uppercase font-mono truncate text-success"
                            >
                                QUICK ADD LIVE FEED
                            </h2>
                            <Badge
                                variant="outline"
                                class="text-xs px-1.5 py-0 font-mono uppercase bg-success/15 text-success border-success/40"
                            >
                                Watchlist
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground truncate">
                            Inject external YouTube live patrol feed into multifeed
                        </p>
                    </div>
                </div>

                <!-- Header Actions: Close Button -->
                <div class="flex items-center space-x-1.5 shrink-0 ml-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon-sm"
                        @click="emit('close')"
                        class="h-7 w-7 text-muted-foreground hover:text-foreground"
                        aria-label="Tutup Panel (Esc)"
                        title="Tutup Panel (Esc)"
                    >
                        <X class="w-3.5 h-3.5" />
                    </Button>
                </div>
            </div>

            <!-- DRAWER CONTENT BODY -->
            <div
                class="flex-1 overflow-y-auto scrollbar-thin flex flex-col min-h-0 bg-background p-4 gap-3.5"
            >
                <div
                    v-if="liveSearchSuccessNotice"
                    class="bg-success/15 border border-success/40 rounded-xl px-3.5 py-2 text-xs text-success font-mono flex items-center gap-2 shadow-lg animate-in fade-in duration-200"
                >
                    <Check class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ liveSearchSuccessNotice }}</span>
                </div>

                <div
                    class="bg-primary/10 border border-primary/30 rounded-xl p-3 text-xs text-foreground leading-relaxed"
                >
                    <div
                        class="font-bold flex items-center justify-between mb-1 text-primary"
                    >
                        <span class="flex items-center gap-1.5">
                            <Star class="w-3.5 h-3.5" />
                            <span>Quick Watchlist & Personal</span>
                        </span>
                        <Badge
                            variant="outline"
                            class="text-xs bg-primary/15 px-2 py-0 rounded-full font-mono font-bold text-primary border-primary/30"
                        >
                            {{ totalPersonalCount }}/6 Video
                        </Badge>
                    </div>
                    Cari lawan/gang atau masukkan live stream YouTube. Stream yang dipilih akan langsung masuk ke tab <strong>PERSONAL</strong> browser lokal Anda.
                </div>

                <div
                    class="grid grid-cols-2 bg-card p-1 rounded-xl border border-border gap-1"
                >
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            quickAddMode === 'SEARCH' ? 'default' : 'ghost'
                        "
                        @click="emit('update:quickAddMode', 'SEARCH')"
                        class="h-8 text-xs gap-1.5"
                    >
                        <Search class="w-3.5 h-3.5" />
                        <span>Cari Live Hashtag</span>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            quickAddMode === 'MANUAL' ? 'default' : 'ghost'
                        "
                        @click="emit('update:quickAddMode', 'MANUAL')"
                        class="h-8 text-xs gap-1.5"
                    >
                        <Link2 class="w-3.5 h-3.5" />
                        <span>Input Manual</span>
                    </Button>
                </div>

                <!-- SEARCH HASHTAG -->
                <div
                    v-if="quickAddMode === 'SEARCH'"
                    class="flex flex-col gap-3"
                >
                    <form
                        @submit.prevent="
                            emit('search-live-streams', liveSearchQuery)
                        "
                        class="flex flex-col gap-2"
                    >
                        <div class="relative">
                            <Input
                                :model-value="liveSearchQuery"
                                @update:model-value="
                                    emit('update:liveSearchQuery', $event)
                                "
                                type="text"
                                required
                                placeholder="Ketik hashtag misal #imeroleplay #burgenk..."
                                class="w-full h-10 pl-8 pr-20 text-xs font-mono"
                            />
                            <Search
                                class="w-3.5 h-3.5 text-muted-foreground absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
                            />
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="isLiveSearching"
                                class="absolute right-1.5 top-1.5 h-7 px-3 text-xs font-bold gap-1"
                            >
                                <RefreshCw
                                    v-if="isLiveSearching"
                                    class="w-3 h-3 motion-safe:animate-spin"
                                />
                                <span>{{
                                    isLiveSearching ? "Mencari..." : "Cari"
                                }}</span>
                            </Button>
                        </div>
                    </form>

                    <div class="flex flex-col gap-1.5">
                        <span
                            class="text-xs font-mono text-muted-foreground uppercase tracking-wider"
                            >Hashtag / Gang Populer:</span
                        >
                        <div class="flex flex-wrap gap-1.5">
                            <Button
                                v-for="preset in popularHashtagPresets"
                                :key="preset.label"
                                type="button"
                                size="sm"
                                :variant="
                                    liveSearchQuery === preset.query
                                        ? 'default'
                                        : 'outline'
                                "
                                @click="
                                    emit('search-live-streams', preset.query)
                                "
                                class="h-7 text-xs px-2.5 font-mono"
                            >
                                {{ preset.label }}
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="liveSearchError"
                        class="p-3 bg-destructive/15 border border-destructive/40 rounded-xl text-xs text-destructive"
                    >
                        {{ liveSearchError }}
                    </div>

                    <div
                        v-if="isLiveSearching"
                        class="py-8 flex flex-col items-center justify-center text-muted-foreground gap-2"
                    >
                        <div
                            class="w-7 h-7 border-2 border-primary border-t-transparent rounded-full motion-safe:animate-spin"
                        ></div>
                        <span class="text-xs font-mono"
                            >Sedang mencari stream YouTube live aktif...</span
                        >
                    </div>

                    <div
                        v-else-if="liveSearchResults.length > 0"
                        class="flex flex-col gap-2.5"
                    >
                        <div
                            class="flex items-center justify-between text-xs text-muted-foreground font-mono px-0.5"
                        >
                            <span
                                >Ditemukan:
                                <strong class="text-foreground">{{
                                    liveSearchResults.length
                                }}</strong>
                                Live Stream</span
                            >
                            <span class="text-xs text-success font-bold"
                                >● 10-8 LIVE</span
                            >
                        </div>

                        <div class="space-y-2.5">
                            <div
                                v-for="item in liveSearchResults"
                                :key="item.video_id"
                                class="bg-card border border-border hover:border-primary/50 rounded-xl p-2.5 transition flex gap-3 items-start"
                            >
                                <div
                                    class="w-28 aspect-video bg-background rounded-lg overflow-hidden shrink-0 relative"
                                >
                                    <img
                                        :src="item.thumbnail_url"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute bottom-1 right-1 bg-background/80 px-1 py-0 rounded-sm text-xs font-mono text-live font-bold"
                                    >
                                        LIVE
                                    </div>
                                </div>

                                <div
                                    class="flex-1 min-w-0 flex flex-col justify-between h-full"
                                >
                                    <div>
                                        <h4
                                            class="text-xs font-bold text-foreground line-clamp-2 leading-snug flex-1"
                                            :title="item.title"
                                        >
                                            {{ item.title }}
                                        </h4>
                                        <div
                                            class="text-xs text-muted-foreground truncate mt-1 flex items-center gap-1.5"
                                        >
                                            <User class="w-3 h-3 shrink-0" />
                                            <span
                                                class="text-primary font-semibold truncate"
                                                >{{ item.channel_name }}</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        class="mt-2 flex items-center justify-between gap-2"
                                    >
                                        <Button
                                            type="button"
                                            size="sm"
                                            :variant="
                                                isPersonalStream(item.video_id)
                                                    ? 'secondary'
                                                    : 'default'
                                            "
                                            @click="
                                                emit(
                                                    'add-live-stream-to-personal',
                                                    item,
                                                )
                                            "
                                            class="h-7 px-2.5 text-xs font-bold font-mono"
                                        >
                                            <span>{{
                                                isPersonalStream(item.video_id)
                                                    ? "✓ Pinned"
                                                    : "Pin ke Personal"
                                            }}</span>
                                        </Button>

                                        <Button
                                            as-child
                                            variant="ghost"
                                            size="icon-sm"
                                            class="h-7 w-7 text-muted-foreground hover:text-foreground"
                                            title="Open on YouTube"
                                            aria-label="Open on YouTube"
                                        >
                                            <a
                                                :href="`https://www.youtube.com/watch?v=${item.video_id}`"
                                                target="_blank"
                                            >
                                                <ExternalLink
                                                    class="w-3.5 h-3.5"
                                                />
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MANUAL INPUT -->
                <div v-else-if="quickAddMode === 'MANUAL'">
                    <form
                        @submit.prevent="emit('quick-add-stream')"
                        class="flex flex-col gap-3.5"
                    >
                        <div>
                            <label
                                class="text-xs font-semibold text-foreground block mb-1"
                                >YouTube Live URL or 11-char Video ID *</label
                            >
                            <Input
                                v-model="quickAddInput.urlOrId"
                                type="text"
                                required
                                placeholder="https://youtube.com/watch?v=xxxxxxxxxxx or dQw4w9WgXcQ"
                                class="h-9 text-xs font-mono"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-xs font-semibold text-foreground block mb-1"
                                    >Officer / Unit Name</label
                                >
                                <Input
                                    v-model="quickAddInput.officerName"
                                    type="text"
                                    placeholder="Ofc. Raymond"
                                    class="h-9 text-xs"
                                />
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-foreground block mb-1"
                                    >Callsign</label
                                >
                                <Input
                                    v-model="quickAddInput.callsign"
                                    type="text"
                                    placeholder="1-ADAM-99"
                                    class="h-9 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-xs font-semibold text-foreground block mb-1"
                                    >Department</label
                                >
                                <select
                                    v-model="quickAddInput.department"
                                    class="w-full h-9 bg-card border border-input rounded-md px-3 py-1.5 text-xs text-foreground focus:outline-hidden focus:border-ring"
                                >
                                    <option value="LSPD">LSPD (Police)</option>
                                    <option value="BCSO">BCSO (Sheriff)</option>
                                    <option value="SASP">
                                        SASP (State Police)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-foreground block mb-1"
                                    >Patrol Zone</label
                                >
                                <Input
                                    v-model="quickAddInput.patrolZone"
                                    type="text"
                                    placeholder="Mission Row"
                                    class="h-9 text-xs"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t border-border flex items-center justify-end space-x-2"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="emit('close')"
                                class="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                            >
                                <PlusCircle class="w-3.5 h-3.5" />
                                <span>Inject Live Feed</span>
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- SAVED PERSONAL WATCHLIST MANAGER -->
                <div
                    class="mt-2 pt-3 border-t border-border flex flex-col gap-2.5"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Star class="w-3.5 h-3.5 text-primary" />
                            <span
                                class="text-xs font-bold text-foreground uppercase font-mono tracking-wide"
                            >
                                Watchlist Tersimpan
                            </span>
                            <Badge
                                variant="outline"
                                class="text-xs bg-primary/15 px-2 py-0 rounded-full font-mono text-primary border-primary/40 font-bold"
                            >
                                {{ totalPersonalCount }}/6 Online ({{
                                    totalSavedPersonalCount
                                }}
                                Total)
                            </Badge>
                        </div>
                        <button
                            v-if="totalSavedPersonalCount > 0"
                            type="button"
                            @click="emit('clear-all-personal')"
                            class="text-xs text-destructive hover:text-destructive/80 font-mono flex items-center gap-1 hover:underline cursor-pointer"
                            title="Kosongkan seluruh pin personal"
                        >
                            <RotateCcw class="w-3 h-3" />
                            <span>Reset Semua</span>
                        </button>
                    </div>

                    <div
                        v-if="totalSavedPersonalCount === 0"
                        class="p-3.5 rounded-xl bg-card border border-border text-center text-xs text-muted-foreground font-mono"
                    >
                        Belum ada video atau channel yang disimpan di Personal.
                    </div>

                    <div
                        v-else
                        class="space-y-2 max-h-60 overflow-y-auto scrollbar-thin pr-0.5"
                    >
                        <div
                            v-for="savedItem in savedPersonalList"
                            :key="savedItem.id"
                            class="bg-card border border-border rounded-xl p-2.5 flex items-center justify-between gap-2.5 transition hover:border-primary/40"
                        >
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <img
                                    v-if="savedItem.thumbnail"
                                    :src="savedItem.thumbnail"
                                    class="w-12 h-8 rounded-sm object-cover shrink-0 border border-border"
                                />
                                <div
                                    v-else
                                    class="w-12 h-8 rounded-sm bg-background border border-border flex items-center justify-center text-xs text-muted-foreground shrink-0"
                                >
                                    <Star class="w-4 h-4 opacity-40" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <Badge
                                            variant="outline"
                                            class="text-xs px-1.5 py-0 font-mono font-bold shrink-0"
                                            :class="
                                                savedItem.isOnline
                                                    ? 'bg-success/15 text-success border-success/40'
                                                    : 'bg-background text-muted-foreground border-border'
                                            "
                                        >
                                            {{
                                                savedItem.isOnline
                                                    ? "● 10-8 LIVE"
                                                    : "○ 10-7 OFFLINE"
                                            }}
                                        </Badge>
                                        <span
                                            class="text-xs font-bold text-foreground truncate"
                                            :title="savedItem.name"
                                            >{{ savedItem.name }}</span
                                        >
                                    </div>
                                    <div
                                        class="text-xs text-muted-foreground font-mono truncate mt-0.5"
                                    >
                                        {{ savedItem.subtext }}
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                @click="
                                    emit(
                                        'remove-personal-stream',
                                        savedItem.rawId,
                                    )
                                "
                                class="h-7 w-7 text-muted-foreground hover:text-destructive hover:border-destructive/40 shrink-0"
                                aria-label="Hapus dari daftar personal"
                                title="Hapus dari daftar personal"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>
