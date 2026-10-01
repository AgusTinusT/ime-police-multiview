<script setup>
import { ref } from "vue";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    stream: { type: Object, default: () => ({}) },
    currentTime: { type: Number, default: 0 },
});

const emit = defineEmits(["close", "submit"]);

const title = ref("");
const category = ref("PURSUIT_1080");
const startTime = ref("00:00");
const endTime = ref("00:30");
const authorName = ref("");

const setStartFromCurrent = () => {
    if (props.currentTime > 0) {
        const m = Math.floor(props.currentTime / 60);
        const s = Math.floor(props.currentTime % 60);
        startTime.value = `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
    } else {
        startTime.value = "10:15";
    }
};

const setEndFromCurrent = () => {
    if (props.currentTime > 0) {
        const m = Math.floor((props.currentTime + 30) / 60);
        const s = Math.floor((props.currentTime + 30) % 60);
        endTime.value = `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
    } else {
        endTime.value = "10:45";
    }
};

const handleSubmit = () => {
    if (!title.value.trim()) {
        alert("Harap masukkan judul klip momen aksi!");
        return;
    }

    emit("submit", {
        title: title.value,
        category: category.value,
        startTime: startTime.value,
        endTime: endTime.value,
        authorName: authorName.value || "Penonton Komunitas",
    });

    title.value = "";
    emit("close");
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 bg-slate-950/80 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4">
            <!-- MODAL HEADER -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 0L4 4m5.121 5.121L4 14.121M14.121 9.121L19 4"/>
                    </svg>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-100 font-mono">Tandai Momen Aksi Baru</h3>
                        <p class="text-[10px] text-slate-400 font-mono">Bookmark & Bagikan Klip Siaran Taktis</p>
                    </div>
                </div>
                <button @click="emit('close')" class="text-slate-400 hover:text-white text-xs font-mono px-2 py-1 rounded bg-slate-800">✕</button>
            </div>

            <!-- FORM BODY -->
            <div class="space-y-3.5 font-mono text-xs">
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Judul Momen Aksi / Klip *</label>
                    <input
                        v-model="title"
                        type="text"
                        placeholder="Judul klip momen..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs"
                    />
                </div>

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Kategori Momen Aksi</label>
                    <select
                        v-model="category"
                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs cursor-pointer"
                    >
                        <option value="PURSUIT_1080">Kejaran Taktis (10-80 Pursuit)</option>
                        <option value="SHOOTOUT_1099">Baku Tembak (10-99 Shootout)</option>
                        <option value="FUNNY">Momen Lucu / Funny RP</option>
                        <option value="ARREST">Penghentian & Penangkapan</option>
                        <option value="TACTICAL_OPS">Operasi Khusus (Air/Water/Tactical)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-slate-300 font-medium">Waktu Mulai (MM:SS)</label>
                            <button @click="setStartFromCurrent" class="text-[10px] text-blue-400 hover:underline">Ambil Detik Ini</button>
                        </div>
                        <input
                            v-model="startTime"
                            type="text"
                            placeholder="10:15"
                            class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs text-center font-semibold"
                        />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-slate-300 font-medium">Waktu Selesai (MM:SS)</label>
                            <button @click="setEndFromCurrent" class="text-[10px] text-blue-400 hover:underline">+30 Detik</button>
                        </div>
                        <input
                            v-model="endTime"
                            type="text"
                            placeholder="10:45"
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

            <!-- MODAL FOOTER -->
            <div class="pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                <button @click="emit('close')" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono font-medium rounded-md transition-colors">
                    Batal
                </button>
                <button @click="handleSubmit" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-mono font-semibold rounded-md transition-colors cursor-pointer">
                    Simpan Klip
                </button>
            </div>
        </div>
    </div>
</template>
