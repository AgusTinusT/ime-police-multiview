<script setup>
import { ref } from "vue";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    officer: { type: Object, default: () => ({}) },
    stream: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["close", "submit"]);

const authorName = ref("");
const impressionType = ref("COMMUNITY_NOTE");
const content = ref("");

const handleSubmit = () => {
    if (!content.value.trim()) {
        alert("Harap tuliskan catatan kesan atau pengalaman Anda!");
        return;
    }

    emit("submit", {
        author_name: authorName.value.trim() || "Penonton Komunitas",
        impression_type: impressionType.value,
        content: content.value.trim(),
    });

    content.value = "";
    emit("close");
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 bg-slate-950/80 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-100 font-mono">Tulis Kesan & Catatan Perwira</h3>
                        <p class="text-[10px] text-slate-400 font-mono">
                            Perwira: {{ officer?.officer_name || stream?.officer?.officer_name || "Trooper" }}
                        </p>
                    </div>
                </div>
                <button @click="emit('close')" class="text-slate-400 hover:text-white text-xs font-mono px-2 py-1 rounded bg-slate-800">✕</button>
            </div>

            <!-- FORM BODY -->
            <div class="space-y-3.5 font-mono text-xs">
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Tipe Kesan / Catatan</label>
                    <select
                        v-model="impressionType"
                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs cursor-pointer"
                    >
                        <option value="COMMUNITY_NOTE">Catatan Komunitas & Rekap RP</option>
                        <option value="TACTICAL_RATING">Rating & Ulasan Gaya Taktis / Pursuit</option>
                        <option value="FUNNY_MOMENT">Momen Lucu</option>
                        <option value="REPUTATION">Catatan Reputasi & Integritas RP</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Nama Penulis / Panggilan Anda</label>
                    <input
                        v-model="authorName"
                        type="text"
                        placeholder="Contoh: Citizen_V"
                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs"
                    />
                </div>

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Isi Kesan & Catatan Lore *</label>
                    <textarea
                        v-model="content"
                        rows="4"
                        placeholder="Bagikan ulasan atau rekap gaya patroli perwira ini..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-md p-3 text-slate-100 focus:border-blue-500 outline-none text-xs leading-relaxed resize-none"
                    ></textarea>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                <button @click="emit('close')" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono font-medium rounded-md transition-colors">
                    Batal
                </button>
                <button @click="handleSubmit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-mono font-semibold rounded-md transition-colors cursor-pointer">
                    Kirim Catatan
                </button>
            </div>
        </div>
    </div>
</template>
