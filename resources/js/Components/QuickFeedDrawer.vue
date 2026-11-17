<script setup>
import { ref } from "vue";
import { Monitor, X, Link2, Search, Zap } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    quickAddInput: {
        type: Object,
        default: () => ({
            videoUrlOrId: "",
            officerName: "",
            callsign: "",
            department: "LSPD",
        }),
    },
    isCheckingChannel: {
        type: Boolean,
        default: false,
    },
    liveSearchResults: {
        type: Array,
        default: () => [],
    },
    isSearchingLive: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "close",
    "addStream",
    "searchLive",
    "addQuickSearchStream",
]);

const activeTab = ref("quick");
const searchQuery = ref("#imeroleplay");

const handleSubmitQuickAdd = () => {
    emit("addStream", { ...props.quickAddInput });
};

const handleSearch = () => {
    emit("searchLive", searchQuery.value);
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-xs animate-in fade-in duration-200 font-sans"
    >
        <div
            class="bg-card border border-border rounded-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.stop
        >
            <!-- Modal Header -->
            <div
                class="px-6 py-4 bg-card border-b border-border flex items-center justify-between"
            >
                <div class="flex items-center space-x-3">
                    <div
                        class="w-8 h-8 rounded-md bg-background border border-border flex items-center justify-center text-primary"
                    >
                        <Monitor class="w-4 h-4 text-primary" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-foreground font-mono uppercase tracking-wide"
                        >
                            Pusat Tambah Feed Siaran Taktis
                        </h3>
                        <p class="text-xs text-muted-foreground font-mono">
                            Input URL/Video ID atau cari siaran live publik
                        </p>
                    </div>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    size="icon-sm"
                    @click="emit('close')"
                    aria-label="Tutup Modal"
                    class="w-8 h-8"
                >
                    <X class="w-4 h-4" />
                </Button>
            </div>

            <!-- Tab Switcher -->
            <div
                class="flex border-b border-border bg-background px-6 pt-2 gap-4 text-xs font-mono"
            >
                <button
                    type="button"
                    @click="activeTab = 'quick'"
                    class="pb-2.5 font-bold transition border-b-2 flex items-center gap-1.5 cursor-pointer"
                    :class="
                        activeTab === 'quick'
                            ? 'text-primary border-primary'
                            : 'text-muted-foreground border-transparent hover:text-foreground'
                    "
                >
                    <Link2 class="w-3.5 h-3.5" />
                    <span>Input URL / Video ID YouTube</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'search'"
                    class="pb-2.5 font-bold transition border-b-2 flex items-center gap-1.5 cursor-pointer"
                    :class="
                        activeTab === 'search'
                            ? 'text-primary border-primary'
                            : 'text-muted-foreground border-transparent hover:text-foreground'
                    "
                >
                    <Search class="w-3.5 h-3.5" />
                    <span>Cari Siaran Live #imeroleplay</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <!-- TAB 1: QUICK ADD FORM -->
                <div v-if="activeTab === 'quick'" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-mono font-bold text-foreground uppercase mb-1.5"
                        >
                            URL Video / ID Stream YouTube
                            <span class="text-destructive">*</span>
                        </label>
                        <Input
                            v-model="quickAddInput.videoUrlOrId"
                            type="text"
                            placeholder="Contoh: https://www.youtube.com/watch?v=dQw4w9WgXcQ atau dQw4w9WgXcQ"
                            class="h-9 text-xs font-mono"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label
                                class="block text-xs font-mono font-bold text-foreground uppercase mb-1.5"
                            >
                                Nama Perwira / Unit
                            </label>
                            <Input
                                v-model="quickAddInput.officerName"
                                type="text"
                                placeholder="Misal: Officer John"
                                class="h-9 text-xs font-sans"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-mono font-bold text-foreground uppercase mb-1.5"
                            >
                                Callsign / Unit ID
                            </label>
                            <Input
                                v-model="quickAddInput.callsign"
                                type="text"
                                placeholder="Misal: 1A-12"
                                class="h-9 text-xs font-mono uppercase"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-mono font-bold text-foreground uppercase mb-1.5"
                            >
                                Agensi Kepolisian
                            </label>
                            <select
                                v-model="quickAddInput.department"
                                class="w-full h-9 bg-background border border-input rounded-md px-3 py-1.5 text-xs text-foreground focus:outline-hidden focus:border-ring font-mono"
                            >
                                <option value="LSPD">
                                    LSPD (Los Santos PD)
                                </option>
                                <option value="LSCSD">
                                    LSCSD (Sheriff Dept)
                                </option>
                                <option value="SASP">
                                    SASP (State Police)
                                </option>
                                <option value="SAPR">
                                    SAPR (Park Ranger)
                                </option>
                                <option value="EMS">EMS / SAMFD</option>
                                <option value="CIVILIAN">
                                    CIVILIAN / DISPATCH
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <Button
                            type="button"
                            @click="handleSubmitQuickAdd"
                            :disabled="
                                !quickAddInput.videoUrlOrId.trim() ||
                                isCheckingChannel
                            "
                            class="font-bold text-xs font-mono gap-2"
                        >
                            <Zap class="w-3.5 h-3.5" />
                            <span>{{
                                isCheckingChannel
                                    ? "Memeriksa Stream..."
                                    : "Tambahkan Stream ke Multiview"
                            }}</span>
                        </Button>
                    </div>
                </div>

                <!-- TAB 2: LIVE SEARCH FEED -->
                <div v-else class="space-y-4">
                    <div class="flex gap-2">
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kata kunci hashtag live..."
                            class="flex-1 h-9 text-xs font-mono"
                            @keyup.enter="handleSearch"
                        />
                        <Button
                            type="button"
                            @click="handleSearch"
                            :disabled="isSearchingLive"
                            class="h-9 px-4 font-bold text-xs font-mono"
                        >
                            {{ isSearchingLive ? "Mencari..." : "Cari" }}
                        </Button>
                    </div>

                    <!-- Search Results -->
                    <div class="space-y-2 max-h-[300px] overflow-y-auto">
                        <div
                            v-if="liveSearchResults.length === 0"
                            class="py-8 text-center text-muted-foreground font-mono text-xs"
                        >
                            Tekan tombol Cari untuk memuat siaran live terkini.
                        </div>

                        <div
                            v-for="item in liveSearchResults"
                            :key="item.video_id"
                            class="p-3 bg-background border border-border hover:border-primary/40 rounded-md flex items-center justify-between gap-3 transition"
                        >
                            <div class="flex items-center space-x-3 min-w-0">
                                <img
                                    :src="item.thumbnail"
                                    class="w-16 h-10 object-cover rounded-md border border-border shrink-0"
                                    alt=""
                                />
                                <div class="min-w-0">
                                    <h5
                                        class="text-xs font-bold text-foreground truncate"
                                    >
                                        {{ item.title }}
                                    </h5>
                                    <p
                                        class="text-xs text-muted-foreground font-mono mt-0.5 truncate"
                                    >
                                        {{ item.channel_title }}
                                    </p>
                                </div>
                            </div>

                            <Button
                                type="button"
                                size="sm"
                                @click="emit('addQuickSearchStream', item)"
                                class="h-8 px-3 font-bold text-xs font-mono shrink-0"
                            >
                                + Tambah
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
