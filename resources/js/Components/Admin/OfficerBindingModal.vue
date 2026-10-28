<script setup>
import { ref, computed } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: "bind", // 'bind' | 'unbind' | 'slug'
    },
    officer: {
        type: Object,
        default: null,
    },
    usersList: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "success", "toast"]);

const selectedUserId = ref("");
const reasonInput = ref("");
const slugInput = ref("");
const isSubmitting = ref(false);
const formError = ref("");
const userSearch = ref("");

const filteredUsers = computed(() => {
    if (!userSearch.value) return props.usersList;
    const q = userSearch.value.toLowerCase();
    return props.usersList.filter(
        (u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    );
});

const handleBind = async () => {
    if (!selectedUserId.value) {
        formError.value = "Silakan pilih akun user yang akan di-bind.";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";

    try {
        const res = await axios.post(`/api/v1/admin/officers/${props.officer.id}/bind-user`, {
            user_id: selectedUserId.value,
            reason: reasonInput.value || null,
        });

        emit("toast", res.data.message || "User berhasil dihubungkan.", "success");
        emit("success");
        emit("close");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal menghubungkan perwira ke akun.";
    } finally {
        isSubmitting.value = false;
    }
};

const handleUnbind = async () => {
    if (!reasonInput.value || reasonInput.value.trim().length < 5) {
        formError.value = "Alasan pemutusan ikatan wajib diisi (minimal 5 karakter).";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";

    try {
        const res = await axios.post(`/api/v1/admin/officers/${props.officer.id}/unbind-user`, {
            reason: reasonInput.value.trim(),
        });

        emit("toast", res.data.message || "Ikatan perwira berhasil dicabut.", "success");
        emit("success");
        emit("close");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal mencabut ikatan perwira.";
    } finally {
        isSubmitting.value = false;
    }
};

const handleUpdateSlug = async () => {
    if (!slugInput.value.trim() || !reasonInput.value.trim()) {
        formError.value = "Slug baru dan alasan perubahan wajib diisi.";
        return;
    }

    const profileId = props.officer?.bio_profile?.id;
    if (!profileId) {
        formError.value = "Profil bio perwira belum tersedia.";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";

    try {
        const res = await axios.patch(`/api/v1/admin/bio-profiles/${profileId}/slug`, {
            slug: slugInput.value.trim(),
            reason: reasonInput.value.trim(),
        });

        emit("toast", res.data.message || "Slug bio perwira berhasil diperbarui.", "success");
        emit("success");
        emit("close");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal mengubah slug profil.";
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div
        v-if="show && officer"
        class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div class="w-full max-w-md bg-slate-900 border border-slate-700 rounded-2xl p-5 shadow-2xl text-left space-y-4 font-mono text-xs">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-white/10 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-base">
                        {{ mode === 'bind' ? '🔗' : mode === 'unbind' ? '⛓️‍💥' : '🏷️' }}
                    </span>
                    <h3 class="text-sm font-bold text-white uppercase">
                        {{
                            mode === 'bind'
                                ? 'Bind Akun ke Perwira'
                                : mode === 'unbind'
                                ? 'Cabut Ikatan Akun (Unbind)'
                                : 'Ubah Slug Publik Bio-Link'
                        }}
                    </h3>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-slate-400 hover:text-white text-sm"
                >
                    ✕
                </button>
            </div>

            <!-- OFFICER DETAILS -->
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                <div class="font-bold text-white text-sm">
                    {{ officer.officer_name }}
                </div>
                <div class="text-slate-400 text-[11px]">
                    Callsign: <span class="text-cyan-400">{{ officer.callsign }}</span> • Departemen: {{ officer.department }}
                </div>
                <div v-if="officer.user" class="text-emerald-400 text-[11px] pt-1 border-t border-white/5">
                    User Saat Ini: {{ officer.user.name }} ({{ officer.user.email }})
                </div>
                <div v-if="officer.bio_profile" class="text-slate-400 text-[11px]">
                    Slug Bio: <span class="text-cyan-300 font-mono">/@{{ officer.bio_profile.slug }}</span>
                </div>
            </div>

            <!-- ERROR ALERT -->
            <div v-if="formError" class="p-2.5 rounded-sm bg-rose-950 border border-rose-500 text-rose-200 text-xs">
                {{ formError }}
            </div>

            <!-- MODE 1: BIND -->
            <div v-if="mode === 'bind'" class="space-y-3">
                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase">Pilih Akun User Aktif & Terverifikasi <span class="text-cyan-400">*</span></label>
                    <input
                        v-model="userSearch"
                        type="text"
                        placeholder="Filter nama / email user..."
                        class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:border-cyan-500 outline-hidden mb-1"
                    />
                    <select
                        v-model="selectedUserId"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-cyan-500 outline-hidden"
                    >
                        <option value="">-- Pilih User ({{ filteredUsers.length }} akun) --</option>
                        <option v-for="u in filteredUsers" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }}) [{{ u.role }}]
                        </option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase">Catatan / Alasan Binding (Opsional)</label>
                    <input
                        v-model="reasonInput"
                        type="text"
                        placeholder="Contoh: Streamer resmi SASP"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-cyan-500 outline-hidden"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="$emit('close')"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleBind"
                        :disabled="isSubmitting"
                        class="px-4 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-bold uppercase tracking-wider"
                    >
                        {{ isSubmitting ? "Menyimpan..." : "Simpan Binding" }}
                    </button>
                </div>
            </div>

            <!-- MODE 2: UNBIND -->
            <div v-else-if="mode === 'unbind'" class="space-y-3">
                <p class="text-xs text-rose-300 font-sans leading-relaxed">
                    ⚠️ Memutus ikatan akan mencabut hak perwira dari akun <strong class="text-white">{{ officer.user?.name }}</strong>, menonaktifkan halaman bio-link publiknya, serta mencabut seluruh slot delegasi clipper.
                </p>

                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase">Alasan Pemutusan Ikatan (Wajib) <span class="text-rose-400">*</span></label>
                    <textarea
                        v-model="reasonInput"
                        rows="2"
                        placeholder="Contoh: Resign / Dishonorable discharge / Pergantian streamer"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-rose-500 outline-hidden"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="$emit('close')"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleUnbind"
                        :disabled="isSubmitting"
                        class="px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-bold uppercase tracking-wider"
                    >
                        {{ isSubmitting ? "Memutus..." : "Konfirmasi Unbind" }}
                    </button>
                </div>
            </div>

            <!-- MODE 3: SLUG -->
            <div v-else-if="mode === 'slug'" class="space-y-3">
                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase">Slug Publik Baru <span class="text-cyan-400">*</span></label>
                    <div class="flex items-center">
                        <span class="px-3 py-2 rounded-l-xl bg-slate-800 text-slate-400 border border-r-0 border-slate-700">/@</span>
                        <input
                            v-model="slugInput"
                            type="text"
                            placeholder="nama-slug-baru"
                            class="w-full px-3 py-2 rounded-r-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-cyan-500 outline-hidden"
                        />
                    </div>
                    <p class="text-[10px] text-slate-400">
                        Slug lama akan otomatis di-redirect 301 ke slug baru ini.
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase">Alasan Perubahan Slug <span class="text-cyan-400">*</span></label>
                    <input
                        v-model="reasonInput"
                        type="text"
                        placeholder="Contoh: Penyesuaian nama panggilan dinas"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-cyan-500 outline-hidden"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="$emit('close')"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleUpdateSlug"
                        :disabled="isSubmitting"
                        class="px-4 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-bold uppercase tracking-wider"
                    >
                        {{ isSubmitting ? "Menyimpan..." : "Ubah Slug" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
