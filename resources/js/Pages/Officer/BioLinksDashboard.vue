<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        required: true,
    },
    otherOfficers: {
        type: Array,
        default: () => [],
    },
    themes: {
        type: Object,
        default: () => ({}),
    },
    buttonStyles: {
        type: Array,
        default: () => [],
    },
    allowedDomains: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({ total_views: 0, total_clicks: 0, links_count: 0 }),
    },
});

const page = usePage();
const activeTab = ref("links"); // 'links' | 'appearance' | 'history'

// Copy URL Notification
const copied = ref(false);
const publicUrl = computed(() => `${window.location.origin}/@${props.profile.slug}`);

const copyPublicUrl = async () => {
    try {
        await navigator.clipboard.writeText(publicUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    } catch {
        // fallback
    }
};

// Notification message banner
const statusMessage = ref("");
const errorMessage = ref("");

const flash = (msg, isError = false) => {
    if (isError) {
        errorMessage.value = msg;
        statusMessage.value = "";
    } else {
        statusMessage.value = msg;
        errorMessage.value = "";
    }
    setTimeout(() => {
        statusMessage.value = "";
        errorMessage.value = "";
    }, 4000);
};

// Links List Local Reactive State
const localLinks = ref([...(props.profile.links || [])]);

// Link Modal Form State
const isModalOpen = ref(false);
const editingLinkId = ref(null);
const linkForm = ref({
    title: "",
    subtitle: "",
    link_type: "custom",
    url: "",
    recommended_officer_id: "",
    badge_label: "",
    is_highlighted: false,
    icon: "external-link",
});
const isSavingLink = ref(false);

const openAddModal = () => {
    editingLinkId.value = null;
    linkForm.value = {
        title: "",
        subtitle: "",
        link_type: "donation",
        url: "",
        recommended_officer_id: "",
        badge_label: "",
        is_highlighted: false,
        icon: "external-link",
    };
    isModalOpen.value = true;
};

const openEditModal = (link) => {
    editingLinkId.value = link.id;
    linkForm.value = {
        title: link.title,
        subtitle: link.subtitle || "",
        link_type: link.link_type,
        url: link.url || "",
        recommended_officer_id: link.recommended_officer_id || "",
        badge_label: link.badge_label || "",
        is_highlighted: !!link.is_highlighted,
        icon: link.icon || "external-link",
    };
    isModalOpen.value = true;
};

// Password Confirmation State for Donation Links (T-03)
const isPasswordConfirmModalOpen = ref(false);
const confirmPasswordInput = ref("");
const passwordConfirmError = ref("");
const isSubmittingPassword = ref(false);

// Save link (create or update)
const saveLink = async () => {
    isSavingLink.value = true;
    passwordConfirmError.value = "";
    try {
        if (editingLinkId.value) {
            const res = await axios.put(`/api/v1/officer/bio-links/${editingLinkId.value}`, linkForm.value);
            const idx = localLinks.value.findIndex((l) => l.id === editingLinkId.value);
            if (idx !== -1) {
                localLinks.value[idx] = res.data.link;
            }
            flash(res.data.message);
        } else {
            const res = await axios.post("/api/v1/officer/bio-links", linkForm.value);
            localLinks.value.push(res.data.link);
            flash(res.data.message);
        }
        isModalOpen.value = false;
        isPasswordConfirmModalOpen.value = false;
        confirmPasswordInput.value = "";
        delete linkForm.value.password_confirmation_inline;
    } catch (err) {
        if (err.response?.status === 423 || err.response?.data?.error === "password_confirmation_required") {
            isPasswordConfirmModalOpen.value = true;
            passwordConfirmError.value = "Konfirmasi password diperlukan untuk mengamankan link donasi.";
        } else if (err.response?.data?.error === "password_mismatch") {
            passwordConfirmError.value = "Password salah. Silakan coba lagi.";
            isPasswordConfirmModalOpen.value = true;
        } else {
            flash(err.response?.data?.message || "Gagal menyimpan tautan.", true);
        }
    } finally {
        isSavingLink.value = false;
        isSubmittingPassword.value = false;
    }
};

const submitPasswordConfirmation = async () => {
    if (!confirmPasswordInput.value) {
        passwordConfirmError.value = "Masukkan password akun Anda.";
        return;
    }
    isSubmittingPassword.value = true;
    linkForm.value.password_confirmation_inline = confirmPasswordInput.value;
    await saveLink();
};

// Toggle active
const toggleLink = async (link) => {
    try {
        const res = await axios.patch(`/api/v1/officer/bio-links/${link.id}/toggle`);
        link.is_active = res.data.is_active;
        flash(res.data.message);
    } catch (err) {
        flash(err.response?.data?.message || "Gagal mengubah status tautan.", true);
    }
};

// Delete link
const deleteLink = async (link) => {
    if (!confirm(`Hapus tautan '${link.title}' secara permanen?`)) return;

    try {
        const res = await axios.delete(`/api/v1/officer/bio-links/${link.id}`);
        localLinks.value = localLinks.value.filter((l) => l.id !== link.id);
        flash(res.data.message);
    } catch (err) {
        flash(err.response?.data?.message || "Gagal menghapus tautan.", true);
    }
};

// Move Link Up / Down
const moveLink = async (index, direction) => {
    const targetIdx = index + direction;
    if (targetIdx < 0 || targetIdx >= localLinks.value.length) return;

    const list = [...localLinks.value];
    const temp = list[index];
    list[index] = list[targetIdx];
    list[targetIdx] = temp;
    localLinks.value = list;

    try {
        const orderedIds = list.map((l) => l.id);
        await axios.post("/api/v1/officer/bio-links/reorder", { ordered_ids: orderedIds });
    } catch (err) {
        flash("Gagal menyimpan urutan.", true);
    }
};

// Appearance Form
const appearanceForm = ref({
    display_title: props.profile.display_title || props.officer.officer_name,
    bio_tagline: props.profile.bio_tagline || "",
    theme_preset: props.profile.theme_preset || "default_glass",
    button_style: props.profile.button_style || "glass_tactical",
    accent_color: props.profile.accent_color || "#06b6d4",
    show_live_stream_banner: props.profile.show_live_stream_banner !== false,
    is_active: props.profile.is_active !== false,
});
const isSavingAppearance = ref(false);

const saveAppearance = async () => {
    isSavingAppearance.value = true;
    try {
        const res = await axios.put("/api/v1/officer/bio-profile", appearanceForm.value);
        flash(res.data.message);
    } catch (err) {
        flash(err.response?.data?.message || "Gagal menyimpan pengaturan tampilan.", true);
    } finally {
        isSavingAppearance.value = false;
    }
};

// Audit History Tab
const historyLogs = ref([]);
const isLoadingHistory = ref(false);

const loadHistory = async () => {
    isLoadingHistory.value = true;
    try {
        const res = await axios.get("/api/v1/officer/bio-links/history");
        historyLogs.value = res.data.logs || [];
    } catch {
        // ignore
    } finally {
        isLoadingHistory.value = false;
    }
};

const onTabChange = (tab) => {
    activeTab.value = tab;
    if (tab === "history" && historyLogs.value.length === 0) {
        loadHistory();
    }
};

// Helper for accepted domains text
const domainHint = computed(() => {
    const t = linkForm.value.link_type;
    if (props.allowedDomains[t]) {
        return "Domain yang diizinkan: " + props.allowedDomains[t].join(", ");
    }
    return "Domain bebas (kecuali denylist terlarang). Gunakan https://";
});
</script>

<template>
    <Head title="Kelola Tactical Bio-Link — Police Duty" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-cyan-500 selection:text-black">
        
        <!-- TOP HEADER BAR -->
        <header class="border-b border-white/10 bg-slate-900/80 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link
                    href="/"
                    class="flex items-center gap-2 text-cyan-400 hover:text-cyan-300 font-mono font-bold text-xs uppercase"
                >
                    <span>← SASP Command</span>
                </Link>
                <span class="text-slate-600 font-mono">/</span>
                <span class="text-xs font-mono font-black uppercase text-white tracking-wider">
                    Tactical Bio-Link Hub
                </span>
            </div>

            <!-- Officer Profile Seal -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-xl bg-slate-800 border border-slate-700 font-mono text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="font-bold text-slate-200">{{ officer.officer_name }}</span>
                    <span class="text-slate-400">#{{ officer.callsign }}</span>
                </div>

                <a
                    :href="`/@${profile.slug}`"
                    target="_blank"
                    class="px-3 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-black font-mono font-black text-xs uppercase tracking-wider transition shadow-md flex items-center gap-1.5"
                >
                    <span>Buka Bio</span>
                    <span>↗</span>
                </a>
            </div>
        </header>

        <!-- MAIN CONTENT WORKSPACE -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- LEFT COLUMN: CONTROLS & TABS (7 cols) -->
            <div class="lg:col-span-7 flex flex-col gap-6">

                <!-- STATUS NOTIFICATION BANNERS -->
                <div v-if="statusMessage" class="p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-500/60 text-emerald-300 text-xs font-mono flex items-center gap-2 shadow-lg">
                    <span>✓</span>
                    <span>{{ statusMessage }}</span>
                </div>
                <div v-if="errorMessage" class="p-3.5 rounded-xl bg-red-950/80 border border-red-500/60 text-red-300 text-xs font-mono flex items-center gap-2 shadow-lg">
                    <span>⚠️</span>
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- SUSPENSION LOCKDOWN BANNER -->
                <div
                    v-if="profile.is_suspended"
                    class="p-4 rounded-2xl bg-amber-950/50 border-2 border-amber-500/60 text-amber-200 text-xs shadow-xl flex items-start gap-3"
                >
                    <span class="text-2xl shrink-0">⚠️</span>
                    <div class="space-y-1">
                        <h4 class="font-bold font-mono uppercase text-amber-300 text-sm">
                            HALAMAN BIO-LINK DINONAKTIFKAN SEMENTARA (SUSPENDED)
                        </h4>
                        <p class="leading-relaxed">
                            Alasan: <strong>{{ profile.suspension_reason || "Sedang dalam peninjauan moderasi Komando SASP." }}</strong>
                        </p>
                        <p class="text-[11px] text-amber-400/80">
                            Dasbor saat ini dalam mode read-only. Penambahan dan pengeditan tautan dikunci sampai pembekuan dicabut.
                        </p>
                    </div>
                </div>

                <!-- HERO SUMMARY CARD -->
                <div class="p-5 rounded-2xl bg-slate-900/90 border border-white/10 shadow-xl flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <img
                            :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                            class="w-16 h-16 rounded-2xl bg-slate-950 border border-slate-700 object-cover shrink-0"
                            alt=""
                        />
                        <div class="text-left">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-base font-black font-mono uppercase text-white">
                                    {{ officer.officer_name }}
                                </h2>
                                <span class="px-2 py-0.5 rounded-sm text-[9px] font-black font-mono uppercase bg-cyan-950 text-cyan-300 border border-cyan-500/40">
                                    VERIFIED
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 font-mono mt-0.5">
                                {{ officer.department }} • {{ officer.rank }} • Callsign #{{ officer.callsign }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <code class="px-2 py-1 rounded-sm bg-slate-950 border border-white/10 text-cyan-300 font-mono text-[11px] select-all">
                                    /@{{ profile.slug }}
                                </code>
                                <button
                                    @click="copyPublicUrl"
                                    class="px-2 py-1 rounded-sm bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono transition"
                                >
                                    {{ copied ? "✓ Tersalin" : "Salin URL" }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STATS BADGES -->
                    <div class="grid grid-cols-2 gap-2 text-center w-full sm:w-auto">
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-white/5">
                            <div class="text-xs text-slate-400 font-mono uppercase text-[10px]">Tayangan</div>
                            <div class="text-lg font-black font-mono text-cyan-400">{{ stats.total_views }}</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-white/5">
                            <div class="text-xs text-slate-400 font-mono uppercase text-[10px]">Klik Tautan</div>
                            <div class="text-lg font-black font-mono text-emerald-400">{{ stats.total_clicks }}</div>
                        </div>
                    </div>
                </div>

                <!-- NAVIGATION TABS -->
                <div class="flex items-center gap-2 border-b border-white/10 pb-2 font-mono text-xs font-bold">
                    <button
                        @click="onTabChange('links')"
                        class="px-4 py-2 rounded-xl transition flex items-center gap-2"
                        :class="activeTab === 'links' ? 'bg-cyan-600 text-black shadow-md' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                    >
                        <span>🔗 Daftar Tautan</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'links' ? 'bg-black/30 text-black' : 'bg-slate-800 text-slate-300'">
                            {{ localLinks.length }}
                        </span>
                    </button>

                    <button
                        @click="onTabChange('appearance')"
                        class="px-4 py-2 rounded-xl transition flex items-center gap-2"
                        :class="activeTab === 'appearance' ? 'bg-cyan-600 text-black shadow-md' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                    >
                        <span>🎨 Tampilan & Tema</span>
                    </button>

                    <button
                        @click="onTabChange('history')"
                        class="px-4 py-2 rounded-xl transition flex items-center gap-2"
                        :class="activeTab === 'history' ? 'bg-cyan-600 text-black shadow-md' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                    >
                        <span>📜 Riwayat Audit</span>
                    </button>
                </div>

                <!-- TAB 1: LINKS MANAGEMENT -->
                <div v-show="activeTab === 'links'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono uppercase text-slate-400">
                            Kelola Tautan (Maks. 30)
                        </span>
                        <button
                            v-if="!profile.is_suspended"
                            @click="openAddModal"
                            class="px-3 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md flex items-center gap-1.5"
                        >
                            <span>+ Tambah Tautan</span>
                        </button>
                    </div>

                    <!-- LINKS LIST -->
                    <div v-if="localLinks.length > 0" class="space-y-2.5">
                        <div
                            v-for="(link, idx) in localLinks"
                            :key="link.id"
                            class="p-3.5 rounded-2xl bg-slate-900/80 border border-white/10 hover:border-slate-700 transition flex items-center justify-between gap-3 shadow-md"
                            :class="{ 'opacity-60': !link.is_active }"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Reorder Buttons -->
                                <div class="flex flex-col gap-0.5 shrink-0">
                                    <button
                                        @click="moveLink(idx, -1)"
                                        :disabled="idx === 0 || profile.is_suspended"
                                        class="w-6 h-5 rounded-sm bg-slate-800 hover:bg-slate-700 disabled:opacity-30 text-[10px] text-slate-300 flex items-center justify-center transition"
                                    >
                                        ▲
                                    </button>
                                    <button
                                        @click="moveLink(idx, 1)"
                                        :disabled="idx === localLinks.length - 1 || profile.is_suspended"
                                        class="w-6 h-5 rounded-sm bg-slate-800 hover:bg-slate-700 disabled:opacity-30 text-[10px] text-slate-300 flex items-center justify-center transition"
                                    >
                                        ▼
                                    </button>
                                </div>

                                <!-- Icon & Type -->
                                <div class="w-9 h-9 rounded-xl bg-slate-950 border border-white/10 flex items-center justify-center text-base shrink-0">
                                    <span v-if="link.link_type === 'donation'">🪙</span>
                                    <span v-else-if="link.link_type === 'officer_recommendation'">🚓</span>
                                    <span v-else-if="link.link_type === 'discord'">💬</span>
                                    <span v-else-if="link.link_type === 'social'">▶️</span>
                                    <span v-else>🔗</span>
                                </div>

                                <!-- Details -->
                                <div class="min-w-0 text-left">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-black font-mono text-white truncate">
                                            {{ link.title }}
                                        </h4>
                                        <span
                                            class="px-1.5 py-0.2 rounded-sm text-[8px] font-mono font-bold uppercase"
                                            :class="link.link_type === 'donation' ? 'bg-amber-950 text-amber-300 border border-amber-500/30' : 'bg-slate-800 text-slate-300'"
                                        >
                                            {{ link.link_type }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 font-mono truncate mt-0.5">
                                        {{ link.subtitle || link.url || 'Rekan patroli' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Active Toggle -->
                                <button
                                    @click="toggleLink(link)"
                                    :disabled="profile.is_suspended"
                                    class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition"
                                    :class="link.is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'"
                                    :title="link.is_active ? 'Tautan Aktif' : 'Tautan Disembunyikan'"
                                >
                                    {{ link.is_active ? "Aktif" : "Mati" }}
                                </button>

                                <button
                                    v-if="!profile.is_suspended"
                                    @click="openEditModal(link)"
                                    class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center text-xs transition"
                                    title="Edit Tautan"
                                >
                                    ✏️
                                </button>

                                <button
                                    v-if="!profile.is_suspended"
                                    @click="deleteLink(link)"
                                    class="w-7 h-7 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 flex items-center justify-center text-xs transition"
                                    title="Hapus Tautan"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center text-slate-500 font-mono text-xs border border-dashed border-slate-800 rounded-2xl">
                        Belum ada tautan. Klik "+ Tambah Tautan" untuk menambahkan Saweria, Discord, atau rekan patroli.
                    </div>
                </div>

                <!-- TAB 2: APPEARANCE & THEME CONFIG -->
                <div v-show="activeTab === 'appearance'" class="space-y-5 p-5 rounded-2xl bg-slate-900/80 border border-white/10">
                    <h3 class="text-sm font-black font-mono uppercase text-white">
                        Kustomisasi Tampilan Bio-Link
                    </h3>

                    <!-- Display Title -->
                    <div class="space-y-1.5 text-left">
                        <label class="block text-xs font-mono text-slate-300 uppercase">Judul Profil / Display Name</label>
                        <input
                            v-model="appearanceForm.display_title"
                            type="text"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                            placeholder="Contoh: Ofc. John Doe"
                        />
                    </div>

                    <!-- Tagline -->
                    <div class="space-y-1.5 text-left">
                        <label class="block text-xs font-mono text-slate-300 uppercase">Bio Tagline / Pesan Singkat (Maks. 255)</label>
                        <textarea
                            v-model="appearanceForm.bio_tagline"
                            rows="2"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-sans text-white focus:border-cyan-500 outline-hidden resize-none"
                            placeholder="Tuliskan motto patroli atau info jadwal streaming Anda..."
                        ></textarea>
                    </div>

                    <!-- Theme Presets Grid -->
                    <div class="space-y-2 text-left">
                        <label class="block text-xs font-mono text-slate-300 uppercase">Preset Tema Departemen</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div
                                v-for="(theme, key) in themes"
                                :key="key"
                                @click="appearanceForm.theme_preset = key; appearanceForm.accent_color = theme.accent"
                                class="p-3 rounded-xl border cursor-pointer transition relative"
                                :class="appearanceForm.theme_preset === key ? 'border-cyan-400 bg-cyan-950/20 ring-1 ring-cyan-500/50' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold font-mono text-white">{{ theme.name }}</span>
                                    <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: theme.accent }"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-snug">{{ theme.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Button Style -->
                    <div class="space-y-1.5 text-left">
                        <label class="block text-xs font-mono text-slate-300 uppercase">Gaya Tombol (Button Style)</label>
                        <select
                            v-model="appearanceForm.button_style"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        >
                            <option v-for="style in buttonStyles" :key="style" :value="style">
                                {{ style.replace('_', ' ').toUpperCase() }}
                            </option>
                        </select>
                    </div>

                    <!-- Accent Color Picker -->
                    <div class="space-y-1.5 text-left">
                        <label class="block text-xs font-mono text-slate-300 uppercase">Warna Aksen Taktis (Hex)</label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="appearanceForm.accent_color"
                                type="color"
                                class="w-10 h-10 rounded-xl cursor-pointer bg-transparent border-0 p-0"
                            />
                            <input
                                v-model="appearanceForm.accent_color"
                                type="text"
                                class="w-36 px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white uppercase focus:border-cyan-500 outline-hidden"
                            />
                        </div>
                    </div>

                    <!-- Live Stream Banner Toggle -->
                    <div class="flex items-center justify-between pt-2 border-t border-white/5">
                        <div>
                            <div class="text-xs font-bold font-mono text-white">Tampilkan Banner Patroli Live</div>
                            <div class="text-[11px] text-slate-400">Muncul otomatis ketika siaran YouTube Anda sedang aktif.</div>
                        </div>
                        <input
                            v-model="appearanceForm.show_live_stream_banner"
                            type="checkbox"
                            class="w-4 h-4 rounded-sm text-cyan-600 focus:ring-0 bg-slate-800 border-slate-700"
                        />
                    </div>

                    <button
                        @click="saveAppearance"
                        :disabled="isSavingAppearance || profile.is_suspended"
                        class="w-full py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-mono font-black text-xs uppercase tracking-wider transition shadow-lg"
                    >
                        {{ isSavingAppearance ? "Menyimpan..." : "Simpan Tampilan Profil" }}
                    </button>
                </div>

                <!-- TAB 3: AUDIT HISTORY -->
                <div v-show="activeTab === 'history'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono uppercase text-slate-400">
                            Log Perubahan Tautan & Profil Anda
                        </span>
                        <button
                            @click="loadHistory"
                            class="text-xs font-mono text-cyan-400 hover:underline"
                        >
                            ↻ Muat Ulang
                        </button>
                    </div>

                    <div v-if="historyLogs.length > 0" class="space-y-2">
                        <div
                            v-for="log in historyLogs"
                            :key="log.id"
                            class="p-3 rounded-xl bg-slate-900/60 border border-white/5 text-xs text-left font-mono"
                        >
                            <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1">
                                <span class="font-bold text-cyan-300 uppercase">{{ log.action }}</span>
                                <span>{{ new Date(log.created_at).toLocaleString() }}</span>
                            </div>
                            <p class="text-slate-200 text-xs font-sans">
                                {{ log.reason || "Perubahan sistem / konfigurasi." }}
                            </p>
                        </div>
                    </div>

                    <div v-else-if="!isLoadingHistory" class="py-10 text-center text-slate-500 font-mono text-xs border border-dashed border-slate-800 rounded-xl">
                        Belum ada riwayat aktivitas yang tercatat.
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: INTERACTIVE REAL-TIME MOBILE PREVIEW FRAME (5 cols) -->
            <div class="lg:col-span-5 flex flex-col items-center sticky top-20">
                <div class="text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <span>📱 PRATINJAU LANGSUNG MOBILE (380px)</span>
                </div>

                <!-- Realistic Smartphone Mockup -->
                <div class="w-full max-w-[360px] h-[640px] rounded-[40px] bg-slate-950 border-[6px] border-slate-800 shadow-2xl relative overflow-hidden flex flex-col ring-1 ring-white/10">
                    <!-- Notch / Speaker grill -->
                    <div class="w-full h-6 bg-slate-950 flex items-center justify-center shrink-0 z-20">
                        <div class="w-20 h-3.5 bg-slate-900 rounded-full flex items-center justify-end px-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500/80"></span>
                        </div>
                    </div>

                    <!-- Scrollable Preview Viewport -->
                    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4 text-center scrollbar-none font-sans text-xs">
                        <!-- Mini Hero -->
                        <div class="flex flex-col items-center">
                            <img
                                :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                                class="w-16 h-16 rounded-full border-2 border-cyan-400 bg-slate-900 object-cover mb-2 shadow-lg"
                                alt=""
                            />
                            <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-sm text-[8px] font-mono font-black bg-cyan-950 text-cyan-300 border border-cyan-500/40 mb-1">
                                🛡️ VERIFIED OFFICER
                            </div>
                            <h4 class="text-sm font-black font-mono uppercase text-white leading-tight">
                                {{ appearanceForm.display_title || officer.officer_name }}
                            </h4>
                            <p class="text-[10px] text-cyan-400 font-mono">
                                {{ officer.streamer_name }}
                            </p>
                            <p v-if="appearanceForm.bio_tagline" class="text-[10px] text-slate-300 mt-1 max-w-[240px] leading-relaxed">
                                {{ appearanceForm.bio_tagline }}
                            </p>
                        </div>

                        <!-- Mini Links List -->
                        <div class="space-y-2 pt-2">
                            <div
                                v-for="l in localLinks.filter((x) => x.is_active)"
                                :key="l.id"
                                class="p-2.5 rounded-xl bg-slate-900/90 border border-white/10 flex items-center justify-between text-left shadow-xs"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs">
                                        <span v-if="l.link_type === 'donation'">🪙</span>
                                        <span v-else-if="l.link_type === 'officer_recommendation'">🚓</span>
                                        <span v-else-if="l.link_type === 'discord'">💬</span>
                                        <span v-else>🔗</span>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-black font-mono text-white truncate">{{ l.title }}</div>
                                        <div v-if="l.subtitle" class="text-[9px] text-slate-400 truncate">{{ l.subtitle }}</div>
                                    </div>
                                </div>
                                <span class="text-[10px] text-cyan-400">→</span>
                            </div>

                            <div v-if="localLinks.filter((x) => x.is_active).length === 0" class="py-8 text-[11px] text-slate-600 font-mono">
                                (Belum ada tautan aktif)
                            </div>
                        </div>

                        <div class="text-[9px] text-slate-600 font-mono pt-4 border-t border-white/5">
                            Police Duty Multiview • policeduty.sasp-motionliferp.com
                        </div>
                    </div>

                    <!-- Home Bar -->
                    <div class="w-full h-4 bg-slate-950 flex items-center justify-center shrink-0 z-20">
                        <div class="w-24 h-1 bg-slate-700 rounded-full"></div>
                    </div>
                </div>
            </div>

        </main>

        <!-- ADD / EDIT LINK MODAL -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="w-full max-w-md bg-slate-900 border border-white/10 rounded-2xl p-5 shadow-2xl text-left space-y-4">
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <h3 class="text-sm font-black font-mono uppercase text-white">
                        {{ editingLinkId ? "Edit Tautan" : "Tambah Tautan Baru" }}
                    </h3>
                    <button
                        @click="isModalOpen = false"
                        class="text-slate-400 hover:text-white font-mono text-sm"
                    >
                        ✕
                    </button>
                </div>

                <!-- Link Type Selector -->
                <div class="space-y-1 text-left">
                    <label class="block text-xs font-mono text-slate-300 uppercase">Tipe Tautan</label>
                    <select
                        v-model="linkForm.link_type"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                    >
                        <option value="donation">🪙 Donasi (Saweria, Bagibagi, Trakteer, Sociabuzz)</option>
                        <option value="discord">💬 Komunitas Discord</option>
                        <option value="officer_recommendation">🚓 Rekan Patroli (Squad Officer)</option>
                        <option value="social">▶️ Media Sosial (YouTube, TikTok, IG, X, Twitch)</option>
                        <option value="custom">🌐 Tautan Kustom</option>
                    </select>
                </div>

                <!-- Squad Partner Selector (If officer_recommendation) -->
                <div v-if="linkForm.link_type === 'officer_recommendation'" class="space-y-1 text-left">
                    <label class="block text-xs font-mono text-slate-300 uppercase">Pilih Perwira Rekanan</label>
                    <select
                        v-model="linkForm.recommended_officer_id"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                    >
                        <option value="">-- Pilih Perwira Patroli --</option>
                        <option v-for="o in otherOfficers" :key="o.id" :value="o.id">
                            {{ o.officer_name }} ({{ o.streamer_name }}) [{{ o.department }} #{{ o.callsign }}]
                        </option>
                    </select>
                </div>

                <!-- URL Input (If not officer_recommendation) -->
                <div v-else class="space-y-1 text-left">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-mono text-slate-300 uppercase">URL Tautan (HTTPS)</label>
                    </div>
                    <input
                        v-model="linkForm.url"
                        type="url"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        placeholder="https://..."
                    />
                    <p class="text-[10px] text-cyan-400/90 font-mono leading-tight mt-1">
                        {{ domainHint }}
                    </p>
                </div>

                <!-- Title -->
                <div class="space-y-1 text-left">
                    <label class="block text-xs font-mono text-slate-300 uppercase">Judul Tombol</label>
                    <input
                        v-model="linkForm.title"
                        type="text"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        placeholder="Contoh: Saweria Donasi Taktis"
                    />
                </div>

                <!-- Subtitle -->
                <div class="space-y-1 text-left">
                    <label class="block text-xs font-mono text-slate-300 uppercase">Sub-Teks (Opsional)</label>
                    <input
                        v-model="linkForm.subtitle"
                        type="text"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        placeholder="Contoh: Min 10k TTS On / Komunitas Resmi"
                    />
                </div>

                <!-- Badge Label -->
                <div class="space-y-1 text-left">
                    <label class="block text-xs font-mono text-slate-300 uppercase">Badge Promo (Opsional)</label>
                    <input
                        v-model="linkForm.badge_label"
                        type="text"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        placeholder="Contoh: NEW, REKOMENDASI, 24/7"
                    />
                </div>

                <!-- Highlighted Toggle -->
                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs font-mono text-slate-300">Beri Efek Glowing / Highlight</span>
                    <input
                        v-model="linkForm.is_highlighted"
                        type="checkbox"
                        class="w-4 h-4 rounded-sm text-cyan-600 focus:ring-0 bg-slate-800 border-slate-700"
                    />
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-white/10">
                    <button
                        @click="isModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="saveLink"
                        :disabled="isSavingLink"
                        class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md"
                    >
                        {{ isSavingLink ? "Menyimpan..." : "Simpan Tautan" }}
                    </button>
                </div>
            </div>
        </div>

        <!-- PASSWORD CONFIRMATION MODAL (T-03) -->
        <div
            v-if="isPasswordConfirmModalOpen"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="w-full max-w-sm bg-slate-900 border border-amber-500/30 rounded-2xl p-5 shadow-2xl text-left space-y-4">
                <div class="flex items-center gap-2 text-amber-400 font-mono text-sm font-bold border-b border-white/10 pb-2">
                    <span>🛡️ KONFIRMASI KEAMANAN AKUN</span>
                </div>
                <p class="text-xs text-slate-300 font-sans leading-relaxed">
                    Untuk melindungi akun Anda dari pembajakan tautan donasi finansial, silakan masukkan password akun Anda untuk melanjutkan:
                </p>
                <div class="space-y-1">
                    <input
                        v-model="confirmPasswordInput"
                        type="password"
                        @keyup.enter="submitPasswordConfirmation"
                        placeholder="Masukkan password akun Anda..."
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-amber-400 outline-hidden"
                        autofocus
                    />
                    <p v-if="passwordConfirmError" class="text-[11px] font-mono text-rose-400 pt-1">
                        {{ passwordConfirmError }}
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        @click="isPasswordConfirmModalOpen = false; confirmPasswordInput = '';"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitPasswordConfirmation"
                        :disabled="isSubmittingPassword"
                        class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md"
                    >
                        {{ isSubmittingPassword ? "Memverifikasi..." : "Konfirmasi & Simpan" }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
