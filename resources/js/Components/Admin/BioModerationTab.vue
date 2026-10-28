<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const emit = defineEmits(["toast"]);

const subTab = ref("links"); // 'links' | 'domains' | 'audit'

// 1. LINKS MODERATION STATE
const links = ref([]);
const linksPagination = ref({});
const isLoadingLinks = ref(false);
const linkStatusFilter = ref("all");
const linkTypeFilter = ref("all");
const linkSearch = ref("");

// Link action modals
const showModerateModal = ref(false);
const activeLink = ref(null);
const targetStatus = ref("approved");
const moderationReason = ref("");
const alsoBanDomain = ref(false);
const isProcessing = ref(false);

// 2. DOMAIN BAN STATE
const banDomainInput = ref("");
const banDomainReason = ref("");
const isBanningDomain = ref(false);

// 3. AUDIT LOGS STATE
const auditLogs = ref([]);
const auditPagination = ref({});
const isLoadingAudit = ref(false);
const auditActionFilter = ref("all");

// Fetch Links
const fetchLinks = async (page = 1) => {
    isLoadingLinks.value = true;
    try {
        const res = await axios.get("/api/v1/admin/bio-links", {
            params: {
                status: linkStatusFilter.value,
                link_type: linkTypeFilter.value,
                search: linkSearch.value,
                page,
            },
        });
        links.value = res.data.links?.data || [];
        linksPagination.value = {
            current_page: res.data.links?.current_page || 1,
            last_page: res.data.links?.last_page || 1,
            total: res.data.links?.total || 0,
        };
    } catch (err) {
        emit("toast", "Gagal memuat link: " + (err.response?.data?.message || err.message), "error");
    } finally {
        isLoadingLinks.value = false;
    }
};

const openModerateModal = (link, status) => {
    activeLink.value = link;
    targetStatus.value = status;
    moderationReason.value = status === 'approved' ? 'Terverifikasi aman oleh Admin' : '';
    alsoBanDomain.value = false;
    showModerateModal.value = true;
};

const handleModerate = async () => {
    if (!activeLink.value) return;
    if (targetStatus.value === 'banned' && (!moderationReason.value || moderationReason.value.trim().length < 3)) {
        emit("toast", "Alasan pemblokiran wajib diisi.", "error");
        return;
    }

    isProcessing.value = true;
    try {
        const res = await axios.patch(`/api/v1/admin/bio-links/${activeLink.value.id}/moderate`, {
            status: targetStatus.value,
            reason: moderationReason.value,
            ban_domain: alsoBanDomain.value,
        });

        emit("toast", res.data.message || "Status moderasi link berhasil diubah.", "success");
        showModerateModal.value = false;
        fetchLinks(linksPagination.value.current_page);
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal mengubah status moderasi.", "error");
    } finally {
        isProcessing.value = false;
    }
};

const deleteLink = async (link) => {
    const reason = prompt(`Masukkan alasan menghapus paksa link "${link.title}":`, "Pelanggaran SOP Kepolisian");
    if (reason === null) return;

    try {
        const res = await axios.delete(`/api/v1/admin/bio-links/${link.id}`, {
            data: { reason },
        });
        emit("toast", res.data.message || "Tautan berhasil dihapus permanen.", "success");
        fetchLinks(linksPagination.value.current_page);
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal menghapus tautan.", "error");
    }
};

// Global Domain Ban
const submitBanDomain = async () => {
    if (!banDomainInput.value.trim() || !banDomainReason.value.trim()) {
        emit("toast", "Domain host dan alasan pemblokiran wajib diisi.", "error");
        return;
    }

    isBanningDomain.value = true;
    try {
        const res = await axios.post("/api/v1/admin/link-denylist", {
            host: banDomainInput.value.trim(),
            reason: banDomainReason.value.trim(),
        });

        emit("toast", res.data.message || "Domain berhasil dimasukkan ke denylist global.", "success");
        banDomainInput.value = "";
        banDomainReason.value = "";
        fetchLinks();
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal memblokir domain.", "error");
    } finally {
        isBanningDomain.value = false;
    }
};

// Fetch Audit Logs
const fetchAuditLogs = async (page = 1) => {
    isLoadingAudit.value = true;
    try {
        const res = await axios.get("/api/v1/admin/audit-logs", {
            params: {
                action: auditActionFilter.value,
                page,
            },
        });
        auditLogs.value = res.data.logs?.data || [];
        auditPagination.value = {
            current_page: res.data.logs?.current_page || 1,
            last_page: res.data.logs?.last_page || 1,
            total: res.data.logs?.total || 0,
        };
    } catch (err) {
        emit("toast", "Gagal memuat log audit: " + (err.response?.data?.message || err.message), "error");
    } finally {
        isLoadingAudit.value = false;
    }
};

const onSubTabChange = (tab) => {
    subTab.value = tab;
    if (tab === 'links') fetchLinks(1);
    if (tab === 'audit') fetchAuditLogs(1);
};

onMounted(() => {
    fetchLinks(1);
});
</script>

<template>
    <div class="space-y-4 font-mono text-xs">
        <!-- SUB-NAVIGATION BAR -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    @click="onSubTabChange('links')"
                    class="px-3.5 py-1.5 rounded-lg font-bold transition border"
                    :class="subTab === 'links' ? 'bg-cyan-600 text-black border-cyan-500 shadow-sm' : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white'"
                >
                    🔗 Moderasi Tautan Global
                </button>
                <button
                    @click="onSubTabChange('domains')"
                    class="px-3.5 py-1.5 rounded-lg font-bold transition border"
                    :class="subTab === 'domains' ? 'bg-cyan-600 text-black border-cyan-500 shadow-sm' : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white'"
                >
                    🚫 Denylist Domain
                </button>
                <button
                    @click="onSubTabChange('audit')"
                    class="px-3.5 py-1.5 rounded-lg font-bold transition border"
                    :class="subTab === 'audit' ? 'bg-cyan-600 text-black border-cyan-500 shadow-sm' : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white'"
                >
                    📜 Log Audit Keamanan
                </button>
            </div>
        </div>

        <!-- 1. LINKS MODERATION VIEW -->
        <div v-show="subTab === 'links'" class="space-y-3">
            <!-- Filter Toolbar -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 flex flex-wrap items-center gap-3 justify-between">
                <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                    <input
                        v-model="linkSearch"
                        @keyup.enter="fetchLinks(1)"
                        type="text"
                        placeholder="Cari judul tautan, URL, host..."
                        class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-md px-3 py-1.5 text-xs focus:border-cyan-500 outline-none"
                    />
                    <button
                        @click="fetchLinks(1)"
                        class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 text-cyan-400 border border-slate-800 rounded-md shrink-0 font-bold"
                    >
                        Cari
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Status Filter -->
                    <select
                        v-model="linkStatusFilter"
                        @change="fetchLinks(1)"
                        class="bg-slate-950 border border-slate-800 text-slate-200 rounded-md px-2.5 py-1.5 text-xs"
                    >
                        <option value="all">Semua Status</option>
                        <option value="approved">Approved</option>
                        <option value="flagged">Flagged</option>
                        <option value="banned">Banned</option>
                    </select>

                    <!-- Type Filter -->
                    <select
                        v-model="linkTypeFilter"
                        @change="fetchLinks(1)"
                        class="bg-slate-950 border border-slate-800 text-slate-200 rounded-md px-2.5 py-1.5 text-xs"
                    >
                        <option value="all">Semua Tipe</option>
                        <option value="donation">Donasi</option>
                        <option value="discord">Discord</option>
                        <option value="officer_recommendation">Squad Mate</option>
                        <option value="social">Social</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-lg">
                <div v-if="isLoadingLinks" class="py-12 text-center text-slate-400">
                    Memuat tautan...
                </div>
                <div v-else-if="links.length === 0" class="py-12 text-center text-slate-500 italic">
                    Tidak ada tautan ditemukan.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950 text-[11px] uppercase text-slate-400 border-b border-slate-800">
                                <th class="py-3 px-4">Tautan / Profil</th>
                                <th class="py-3 px-4">Tipe & URL Host</th>
                                <th class="py-3 px-4">Status Moderasi</th>
                                <th class="py-3 px-4 text-right">Aksi Moderasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr
                                v-for="link in links"
                                :key="link.id"
                                class="hover:bg-slate-800/40 transition"
                            >
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-sm">
                                        {{ link.title }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-sm">
                                        <a :href="link.url" target="_blank" class="hover:text-cyan-300 underline font-sans">
                                            {{ link.url }}
                                        </a>
                                    </div>
                                    <div class="text-[10px] text-cyan-400/80 mt-0.5">
                                        Profil: /@{{ link.profile?.slug }} • {{ link.profile?.officer?.officer_name }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-950 text-slate-300 border border-slate-800">
                                        {{ link.link_type }}
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        {{ link.url_host || "-" }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                        :class="{
                                            'bg-emerald-950 text-emerald-300 border border-emerald-500/40': link.moderation_status === 'approved',
                                            'bg-amber-950 text-amber-300 border border-amber-500/40': link.moderation_status === 'flagged',
                                            'bg-rose-950 text-rose-300 border border-rose-500/40': link.moderation_status === 'banned',
                                        }"
                                    >
                                        {{ link.moderation_status }}
                                    </span>
                                    <div v-if="link.moderation_reason" class="text-[10px] text-slate-400 italic mt-1 max-w-xs">
                                        "{{ link.moderation_reason }}"
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        <button
                                            v-if="link.moderation_status !== 'approved'"
                                            @click="openModerateModal(link, 'approved')"
                                            class="px-2 py-1 rounded bg-emerald-950 hover:bg-emerald-900 text-emerald-300 border border-emerald-700 text-[11px]"
                                        >
                                            ✓ Approve
                                        </button>
                                        <button
                                            v-if="link.moderation_status !== 'flagged'"
                                            @click="openModerateModal(link, 'flagged')"
                                            class="px-2 py-1 rounded bg-amber-950 hover:bg-amber-900 text-amber-300 border border-amber-700 text-[11px]"
                                        >
                                            ! Flag
                                        </button>
                                        <button
                                            v-if="link.moderation_status !== 'banned'"
                                            @click="openModerateModal(link, 'banned')"
                                            class="px-2 py-1 rounded bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-700 text-[11px]"
                                        >
                                            ✕ Ban
                                        </button>
                                        <button
                                            @click="deleteLink(link)"
                                            class="px-2 py-1 rounded bg-slate-950 hover:bg-rose-950 text-slate-400 hover:text-rose-400 border border-slate-800 text-[11px]"
                                            title="Hapus Paksa"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. DOMAIN BAN VIEW -->
        <div v-show="subTab === 'domains'" class="space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 max-w-2xl text-left space-y-4">
                <h3 class="text-sm font-bold text-white uppercase flex items-center gap-2">
                    <span>🚫 Masukkan Domain ke Denylist Global</span>
                </h3>
                <p class="text-slate-400 text-xs font-sans leading-relaxed">
                    Domain yang dimasukkan ke denylist akan otomatis dinonaktifkan di seluruh profil bio-link perwira, dan tidak dapat ditambahkan kembali oleh perwira manapun (termasuk subdomain terkait).
                </p>

                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase text-xs">Nama Host / Domain</label>
                    <input
                        v-model="banDomainInput"
                        type="text"
                        placeholder="Contoh: phishing-site.xyz atau judi-online.com"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-rose-500 outline-none"
                    />
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-300 uppercase text-xs">Alasan Pemblokiran <span class="text-rose-400">*</span></label>
                    <input
                        v-model="banDomainReason"
                        type="text"
                        placeholder="Contoh: Terdeteksi URL scam donasi / phishing kredensial"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-rose-500 outline-none"
                    />
                </div>

                <button
                    @click="submitBanDomain"
                    :disabled="isBanningDomain"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-bold uppercase rounded-xl tracking-wider shadow-md"
                >
                    {{ isBanningDomain ? "Memblokir Domain..." : "Blokir Domain Global" }}
                </button>
            </div>
        </div>

        <!-- 3. AUDIT LOGS VIEW -->
        <div v-show="subTab === 'audit'" class="space-y-3">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex items-center justify-between">
                <span class="text-slate-400 text-xs uppercase font-bold">
                    Catatan Audit Aksi Sensitif (Append-Only)
                </span>
                <button
                    @click="fetchAuditLogs(auditPagination.current_page)"
                    class="px-2.5 py-1 rounded bg-slate-950 hover:bg-slate-800 text-cyan-400 border border-slate-800 text-xs"
                >
                    ↻ Refresh
                </button>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-lg">
                <div v-if="isLoadingAudit" class="py-12 text-center text-slate-400">
                    Memuat log audit...
                </div>
                <div v-else-if="auditLogs.length === 0" class="py-12 text-center text-slate-500 italic">
                    Belum ada riwayat aksi tercatat.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950 text-[11px] uppercase text-slate-400 border-b border-slate-800">
                                <th class="py-3 px-4">Waktu</th>
                                <th class="py-3 px-4">Aktor / Admin</th>
                                <th class="py-3 px-4">Aksi</th>
                                <th class="py-3 px-4">Subjek</th>
                                <th class="py-3 px-4">Alasan & Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr
                                v-for="log in auditLogs"
                                :key="log.id"
                                class="hover:bg-slate-800/40 text-xs"
                            >
                                <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="py-3 px-4 font-bold text-cyan-300">
                                    {{ log.actor?.name || "System" }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-amber-300">
                                    {{ log.action }}
                                </td>
                                <td class="py-3 px-4 text-slate-400">
                                    {{ log.subject_type }} #{{ log.subject_id }}
                                </td>
                                <td class="py-3 px-4 text-slate-200 font-sans">
                                    {{ log.reason || "-" }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODERATE MODAL -->
        <div
            v-if="showModerateModal"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
        >
            <div class="w-full max-w-md bg-slate-900 border border-slate-700 rounded-2xl p-5 shadow-2xl text-left space-y-4">
                <div class="flex items-center gap-2 text-white font-bold border-b border-white/10 pb-2">
                    <span>MODERASI TAUTAN: {{ targetStatus.toUpperCase() }}</span>
                </div>

                <div class="text-xs text-slate-300 space-y-1">
                    <p>Judul: <strong class="text-white">{{ activeLink?.title }}</strong></p>
                    <p class="truncate">URL: <span class="text-cyan-400 font-mono">{{ activeLink?.url }}</span></p>
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-300 text-xs uppercase">Alasan Moderasi</label>
                    <input
                        v-model="moderationReason"
                        type="text"
                        placeholder="Contoh: Link disetujui / Link terindikasi penipuan"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-cyan-500 outline-none"
                    />
                </div>

                <div v-if="targetStatus === 'banned'" class="flex items-center gap-2 pt-1">
                    <input
                        v-model="alsoBanDomain"
                        type="checkbox"
                        id="banDomainChk"
                        class="w-4 h-4 rounded text-rose-600 bg-slate-800 border-slate-700"
                    />
                    <label for="banDomainChk" class="text-xs text-rose-300 cursor-pointer">
                        Blokir juga seluruh domain ({{ activeLink?.url_host }}) secara global
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="showModerateModal = false"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleModerate"
                        :disabled="isProcessing"
                        class="px-4 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-bold uppercase tracking-wider"
                    >
                        {{ isProcessing ? "Menyimpan..." : "Simpan Moderasi" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
