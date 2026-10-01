<script setup>
/**
 * ==============================================================================
 * SPESIFIKASI DATA & CONTRACT INTEGRASI - ACTION CLIP CREATION MODAL
 * ==============================================================================
 * 
 * 1. PROPS DIBUTUHKAN:
 *    - isOpen: Boolean (Toggle modal terbuka/tertutup)
 *    - stream: Object (Data siaran aktif, memuat video_id & title)
 *    - currentTime: Number (Posisi detik playback terkini dari player)
 * 
 * 2. EMIT EVENTS & PAYLOAD (POST /api/clips):
 *    - @submit: {
 *        title: String (Judul Klip Aksi),
 *        startTime: String (Format MM:SS),
 *        endTime: String (Format MM:SS),
 *        authorName: String (Nama Penanda Klip)
 *      }
 * 
 * ==============================================================================
 * CATATAN INTEGRASI:
 * Jika modal ini sudah terhubung penuh dengan endpoint simpan klip backend,
 * HAPUS BLOK KOMENTAR INI.
 * ==============================================================================
 */
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    stream: { type: Object, default: () => ({}) },
    currentTime: { type: Number, default: 0 },
});

const emit = defineEmits(["close", "submit"]);

const title = ref("");
const startTime = ref("00:00");
const endTime = ref("00:30");
const authorName = ref("");

const durationPreset = ref(30);

const setDurationPreset = (seconds) => {
    durationPreset.value = seconds;
    const parts = startTime.value.split(":");
    let totalSecs = 0;
    if (parts.length === 2) {
        totalSecs = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }
    const endSecs = totalSecs + seconds;
    const em = Math.floor(endSecs / 60);
    const es = Math.floor(endSecs % 60);
    endTime.value = `${em.toString().padStart(2, "0")}:${es.toString().padStart(2, "0")}`;
};

const openStudioClipper = () => {
    if (props.stream?.video_id) {
        const targetUrl = `https://www.youtube.com/watch?v=${props.stream.video_id}`;
        router.visit(`/clipper?url=${encodeURIComponent(targetUrl)}`);
        emit("close");
    }
};

const handleSubmit = () => {
    if (!title.value.trim()) {
        alert("Harap masukkan judul klip momen aksi!");
        return;
    }

    emit("submit", {
        title: title.value,
        startTime: startTime.value,
        endTime: endTime.value,
        authorName: authorName.value || "Penonton Komunitas",
    });

    title.value = "";
    emit("close");
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4 shadow-2xl">
            <!-- MODAL HEADER -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 0L4 4m5.121 5.121L4 14.121M14.121 9.121L19 4"/>
                    </svg>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-100 font-mono">Tandai Momen Aksi Baru</h3>
                        <p class="text-[10px] text-slate-400 font-mono">Pratinjau Video & Bookmark Klip Siaran</p>
                    </div>
                </div>
                <button @click="emit('close')" class="text-slate-400 hover:text-white text-xs font-mono px-2 py-1 rounded bg-slate-800 cursor-pointer">✕</button>
            </div>

            <!-- EMBEDDED MINI PREVIEW PLAYER -->
            <div v-if="stream?.video_id" class="space-y-2">
                <div class="relative w-full aspect-video bg-slate-950 rounded-lg overflow-hidden border border-slate-800">
                    <iframe
                        :src="`https://www.youtube.com/embed/${stream.video_id}?autoplay=1&controls=1&rel=0`"
                        class="w-full h-full border-0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
                <p class="text-[10px] text-slate-400 font-mono italic text-center">
                    Gunakan pemutar video di atas untuk melihat & memastikan stempel waktu klip secara langsung.
                </p>
            </div>

            <!-- FORM BODY -->
            <div class="space-y-3.5 font-mono text-xs">
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Judul Momen Aksi / Klip *</label>
                    <input
                        v-model="title"
                        type="text"
                        placeholder="Misal: PIT Maneuver di Highway 68..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs"
                    />
                </div>

                <!-- PRESET DURATION BUTTONS -->
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Durasi Klip Cepat</label>
                    <div class="flex items-center gap-2">
                        <button
                            @click="setDurationPreset(30)"
                            :class="durationPreset === 30 ? 'bg-slate-800 text-slate-100 border-slate-700 font-bold' : 'bg-slate-950 text-slate-400 border-slate-800'"
                            class="px-2.5 py-1 rounded border text-[11px] font-mono hover:text-slate-200 cursor-pointer"
                        >
                            30 Detik
                        </button>
                        <button
                            @click="setDurationPreset(60)"
                            :class="durationPreset === 60 ? 'bg-slate-800 text-slate-100 border-slate-700 font-bold' : 'bg-slate-950 text-slate-400 border-slate-800'"
                            class="px-2.5 py-1 rounded border text-[11px] font-mono hover:text-slate-200 cursor-pointer"
                        >
                            60 Detik
                        </button>
                        <button
                            @click="setDurationPreset(90)"
                            :class="durationPreset === 90 ? 'bg-slate-800 text-slate-100 border-slate-700 font-bold' : 'bg-slate-950 text-slate-400 border-slate-800'"
                            class="px-2.5 py-1 rounded border text-[11px] font-mono hover:text-slate-200 cursor-pointer"
                        >
                            90 Detik
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Waktu Mulai (MM:SS)</label>
                        <input
                            v-model="startTime"
                            type="text"
                            placeholder="00:00"
                            class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs text-center font-semibold"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Waktu Selesai (MM:SS)</label>
                        <input
                            v-model="endTime"
                            type="text"
                            placeholder="00:30"
                            class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs text-center font-semibold"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Nama Pembuat Klip (Opsional)</label>
                    <input
                        v-model="authorName"
                        type="text"
                        placeholder="Panggilan Anda"
                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs"
                    />
                </div>
            </div>

            <!-- ALTERNATIVE METHOD: STUDIO CLIPPER LINK -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                <button
                    @click="openStudioClipper"
                    class="text-[11px] font-mono text-blue-400 hover:text-blue-300 underline flex items-center gap-1 cursor-pointer"
                >
                    <span>Buka Pembuat Klip Pro (Studio Clipper) &rarr;</span>
                </button>
            </div>

            <!-- MODAL FOOTER -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-end space-x-2">
                <button @click="emit('close')" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono font-medium rounded-md transition-colors cursor-pointer">
                    Batal
                </button>
                <button @click="handleSubmit" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-mono font-semibold rounded-md transition-colors cursor-pointer">
                    Simpan Klip
                </button>
            </div>
        </div>
    </div>
</template>
