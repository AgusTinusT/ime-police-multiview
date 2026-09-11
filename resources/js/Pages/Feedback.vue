<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

import logoSaspColor from '@/Components/Icons/SASP256.jpg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconQuickAdd from '@/Components/Icons/button-plus-svgrepo-com.svg';
import iconEdit from '@/Components/Icons/edit-2-svgrepo-com.svg';
import iconBug from '@/Components/Icons/bug-svgrepo-com.svg';
import iconSend from '@/Components/Icons/send-svgrepo-com.svg';
import iconRefresh from '@/Components/Icons/refresh-cw-svgrepo-com.svg';

const props = defineProps({
    prefillType: {
        type: String,
        default: 'CHANNEL_REQUEST',
    },
});

const feedbackForm = ref({
    type: props.prefillType || 'CHANNEL_REQUEST',
    sender_name: '',
    handle_or_url: '',
    officer_name: '',
    department: 'LSPD',
    message: '',
});

const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const submitFeedbackForm = async () => {
    if (!feedbackForm.value.message || feedbackForm.value.message.trim() === '') {
        alert('Mohon isi pesan / detail laporan Anda.');
        return;
    }

    isSubmitting.value = true;
    successMessage.value = '';
    errorMessage.value = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/v1/feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(feedbackForm.value),
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
            successMessage.value = data.message || 'Laporan berhasil dikirim langsung ke Discord Dispatcher!';
            feedbackForm.value = {
                type: 'CHANNEL_REQUEST',
                sender_name: '',
                handle_or_url: '',
                officer_name: '',
                department: 'LSPD',
                message: '',
            };
        } else {
            errorMessage.value = data.message || 'Gagal mengirim masukan. Silakan coba lagi.';
        }
    } catch (e) {
        errorMessage.value = 'Terjadi kesalahan jaringan saat mengirim laporan.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Lapor & Usulan Dispatcher - IME RP Police Command Center" />

    <div class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased">
        
        <!-- Tactical Header Bar -->
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-4 py-2 flex items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Area: Branding & Standalone Page Navigation Links -->
            <div class="flex items-center space-x-3 shrink-0">
                <!-- Branding: IME Roleplay Police Division -->
                <Link href="/" class="flex items-center space-x-2.5 shrink-0 group">
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 shadow-inner p-1 overflow-hidden group-hover:border-blue-400 transition">
                        <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-black tracking-wider text-blue-400 uppercase leading-tight group-hover:text-blue-300 transition">IME ROLEPLAY</span>
                        <span class="text-[10px] font-bold tracking-wide text-slate-300 uppercase leading-tight">POLICE DIVISION</span>
                    </div>
                </Link>

                <!-- Vertical Divider -->
                <div class="h-6 w-px bg-slate-800/80 hidden md:block"></div>

                <!-- Page Navigation Links (Clean Tab Style) -->
                <nav class="hidden md:flex items-center space-x-1">
                    <Link 
                        href="/officers"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-1.5 group"
                        title="Officer Directory (LSPD, BCSO, SASP)"
                    >
                        <span>Officer Directory</span>
                    </Link>

                    <!-- Perlu dilakukan penyesuaian tampilan untuk radio-codes, about, dan feedback -->
                    <!-- <Link 
                        href="/radio-codes"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-1.5 group"
                        title="10-Codes & Tactical Radio Channels (TAC 1-10)"
                    >
                        <span>10-Codes & Radio</span>
                    </Link>

                    <Link 
                        href="/about"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-1.5 group"
                        title="About Police Command Center"
                    >
                        <span>About Platform</span>
                    </Link> -->

                    <Link 
                        href="/feedback"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600/30 text-blue-300 border border-blue-500/50 shadow-sm transition"
                        title="Active Page: Channel Requests & System Feedback"
                    >
                        <span>Feedback & Reports</span>
                    </Link>
                </nav>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center space-x-2">
                <Link 
                    href="/" 
                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-600/30 transition flex items-center gap-1.5"
                    title="Kembali ke Halaman Utama CCTV Multiview"
                >
                    <span>Multiview</span>
                </Link>
            </div>
        </header>

        <!-- Main Content Portal -->
        <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-8 sm:py-12 flex flex-col gap-6">
            
            <!-- Hero Title -->
            <div class="space-y-2 text-center sm:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-950/80 border border-sky-500/40 text-sky-300 font-mono text-xs font-bold">
                    <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>Direct Dispatcher Line</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight">Portal Laporan & Usulan Streamer</h1>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Ajukan streamer polisi baru, laporkan perubahan pangkat/callsign dinas, atau sampaikan masukan langsung ke channel Discord Dispatcher IME Roleplay.
                </p>
            </div>

            <!-- Feedback Toasts -->
            <div v-if="successMessage" class="bg-emerald-950/90 border border-emerald-500/50 rounded-2xl p-4 text-xs text-emerald-300 font-mono flex items-start gap-3 shadow-lg">
                <span class="text-lg leading-none">✓</span>
                <div>
                    <div class="font-bold text-emerald-200 mb-0.5">Laporan Berhasil Terkirim!</div>
                    <div>{{ successMessage }}</div>
                </div>
            </div>

            <div v-if="errorMessage" class="bg-red-950/90 border border-red-500/50 rounded-2xl p-4 text-xs text-red-300 font-mono flex items-start gap-3 shadow-lg">
                <span class="text-lg leading-none">✕</span>
                <div>
                    <div class="font-bold text-red-200 mb-0.5">Gagal Mengirim Laporan</div>
                    <div>{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submitFeedbackForm" class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 sm:p-7 shadow-2xl space-y-5">
                
                <!-- Category Selector -->
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono block mb-2">Pilih Kategori Laporan *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button 
                            type="button" 
                            @click="feedbackForm.type = 'CHANNEL_REQUEST'"
                            :class="feedbackForm.type === 'CHANNEL_REQUEST' ? 'bg-sky-600 text-white font-bold border-sky-400 shadow-md shadow-sky-600/30' : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="p-3 rounded-xl border text-xs text-left transition flex flex-col gap-2"
                        >
                            <img :src="iconQuickAdd" class="w-4 h-4 invert opacity-90" alt="" />
                            <div>
                                <div class="font-bold">Usul Streamer</div>
                                <div class="text-[10px] text-slate-300/80 font-normal mt-0.5">Streamer polisi baru</div>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            @click="feedbackForm.type = 'DATA_CORRECTION'"
                            :class="feedbackForm.type === 'DATA_CORRECTION' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="p-3 rounded-xl border text-xs text-left transition flex flex-col gap-2"
                        >
                            <img :src="iconEdit" class="w-4 h-4 invert opacity-90" alt="" />
                            <div>
                                <div class="font-bold">Koreksi Data</div>
                                <div class="text-[10px] text-slate-300/80 font-normal mt-0.5">Update rank/callsign</div>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            @click="feedbackForm.type = 'BUG_REPORT'"
                            :class="feedbackForm.type === 'BUG_REPORT' ? 'bg-red-600 text-white font-bold border-red-400 shadow-md shadow-red-600/30' : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="p-3 rounded-xl border text-xs text-left transition flex flex-col gap-2"
                        >
                            <img :src="iconBug" class="w-4 h-4 invert opacity-90" alt="" />
                            <div>
                                <div class="font-bold">Lapor Bug</div>
                                <div class="text-[10px] text-slate-300/80 font-normal mt-0.5">Kendala website</div>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            @click="feedbackForm.type = 'OTHER'"
                            :class="feedbackForm.type === 'OTHER' ? 'bg-purple-600 text-white font-bold border-purple-400 shadow-md shadow-purple-600/30' : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="p-3 rounded-xl border text-xs text-left transition flex flex-col gap-2"
                        >
                            <img :src="iconFeedback" class="w-4 h-4 invert opacity-90" alt="" />
                            <div>
                                <div class="font-bold">Lainnya</div>
                                <div class="text-[10px] text-slate-300/80 font-normal mt-0.5">Pesan & masukan</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Sender Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Pengirim / IC Name (Opsional)</label>
                        <input 
                            v-model="feedbackForm.sender_name" 
                            type="text" 
                            placeholder="Contoh: Citizen Ray / Raymond"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500 placeholder-slate-600"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-300 block mb-1">Handle / Link YouTube Streamer</label>
                        <input 
                            v-model="feedbackForm.handle_or_url" 
                            type="text" 
                            placeholder="@HandleStreamer atau URL Channel"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500 font-mono placeholder-slate-600"
                        />
                    </div>
                </div>

                <!-- Officer / Department Specific Info -->
                <div v-if="feedbackForm.type === 'CHANNEL_REQUEST' || feedbackForm.type === 'DATA_CORRECTION'" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Karakter Polisi / Callsign</label>
                        <input 
                            v-model="feedbackForm.officer_name" 
                            type="text" 
                            placeholder="Ofc. Budi / 1-ADAM-12"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500 placeholder-slate-600"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300 block mb-1">Kesatuan / Department</label>
                        <select 
                            v-model="feedbackForm.department" 
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                        >
                            <option value="LSPD">LSPD (Police)</option>
                            <option value="BCSO">BCSO (Sheriff)</option>
                            <option value="SASP">SASP (State Police)</option>
                            <option value="SAPR">SAPR (Park Rangers)</option>
                        </select>
                    </div>
                </div>

                <!-- Message Detail -->
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Detail Pesan / Catatan Laporan *</label>
                    <textarea 
                        v-model="feedbackForm.message" 
                        required 
                        rows="5" 
                        :placeholder="feedbackForm.type === 'CHANNEL_REQUEST' ? 'Jelaskan jadwal live rutin streamer atau informasi pendukung lainnya...' : (feedbackForm.type === 'DATA_CORRECTION' ? 'Jelaskan data apa yang perlu diperbarui (misal kenaikan pangkat jadi Sergeant, perbaikan callsign)...' : 'Tuliskan detail masukan atau kendala teknis yang Anda temui...')"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500 placeholder-slate-600 leading-relaxed"
                    ></textarea>
                </div>

                <!-- Submit Action Footer -->
                <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500 font-mono hidden sm:inline">Terkirim langsung ke Discord Dispatcher</span>
                    
                    <button 
                        type="submit" 
                        :disabled="isSubmitting" 
                        class="w-full sm:w-auto px-6 py-2.5 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 flex items-center justify-center gap-2 transition"
                    >
                        <img v-if="isSubmitting" :src="iconRefresh" class="w-4 h-4 animate-spin invert" alt="" />
                        <img v-else :src="iconSend" class="w-4 h-4 invert" alt="" />
                        <span>{{ isSubmitting ? 'Mengirim Laporan...' : 'Kirim Laporan ke Dispatcher' }}</span>
                    </button>
                </div>

            </form>

        </main>

        <!-- Page Footer -->
        <footer class="bg-[#080d18] border-t border-slate-800/80 py-6 text-center text-xs text-slate-500 font-mono">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <span>© 2026 IME Roleplay Police Command Center.</span>
                <span>Direct Dispatcher Integration</span>
            </div>
        </footer>

    </div>
</template>
