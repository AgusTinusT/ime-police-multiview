<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

// Icons & Branding
import logoSaspColor from '@/Components/Icons/SASP256.jpg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconSearch from '@/Components/Icons/search-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconFullscreen from '@/Components/Icons/full-screen-svgrepo-com.svg';
import iconExitFullscreen from '@/Components/Icons/minimize-svgrepo-com.svg';

const props = defineProps({
    tacChannels: {
        type: Array,
        default: () => [],
    },
    deptStats: {
        type: Object,
        default: () => ({}),
    },
    lastSyncedAt: {
        type: String,
        default: '',
    },
});

const activeTab = ref('CODES'); // 'CODES' or 'TAC'
const searchCode = ref('');
const selectedCategory = ref('ALL');
const isFullscreen = ref(false);

const standardPolice10Codes = [
    { code: '10-4', title: 'Message Received / Roger', meaning: 'Pesan atau instruksi telah diterima dengan jelas dan dipahami sepenuhnya.', scenario: 'Konfirmasi penerimaan tugas patroli atau instruksi dispatcher.', category: 'Umum' },
    { code: '10-7', title: 'Out of Service / Off Duty', meaning: 'Petugas keluar dari dinas patroli atau unit dinonaktifkan / kembali ke stasiun.', scenario: 'Selesai bertugas, istirahat makan, atau perbaikan kendaraan.', category: 'Status' },
    { code: '10-8', title: 'In Service / On Duty', meaning: 'Petugas aktif bertugas, siap menerima panggilan dispatch dan penugasan.', scenario: 'Mulai dinas patroli atau kembali aktif setelah menangani kejadian.', category: 'Status' },
    { code: '10-20', title: 'Location / Coordinates', meaning: 'Permintaan atau konfirmasi posisi / lokasi spesifik unit saat ini.', scenario: 'Pengecekan koordinat posisi unit oleh dispatcher atau unit bantuan.', category: 'Navigasi' },
    { code: '10-23', title: 'Arrived on Scene', meaning: 'Unit telah tiba di lokasi kejadian / Tempat Kejadian Perkara (TKP).', scenario: 'Konfirmasi ketibaan di lokasi laporan warga atau lokasi perampokan.', category: 'Taktis' },
    { code: '10-33', title: 'Officer in Distress / Emergency', meaning: 'Panggilan darurat kritis! Petugas berada di bawah ancaman berat dan butuh bantuan segera.', scenario: 'Petugas tertembak, diserang massa bersenjata, atau terkepung.', category: 'Darurat' },
    { code: '10-50', title: 'Motor Vehicle Accident', meaning: 'Terjadi kecelakaan lalu lintas atau tabrakan kendaraan bermotor.', scenario: 'Tabrakan saat patroli, pengejaran, atau kecelakaan sipil di jalan raya.', category: 'Lantas' },
    { code: '10-70', title: 'Foot Pursuit', meaning: 'Pengejaran tersangka dengan berlari / berjalan kaki.', scenario: 'Tersangka melarikan diri meninggalkan kendaraan masuk ke gang atau perumahan.', category: 'Pengejaran' },
    { code: '10-80', title: 'High Speed Pursuit', meaning: 'Pengejaran kendaraan berkecepatan tinggi yang berpotensi membahayakan publik.', scenario: 'Tersangka menolak berhenti dan memacu kendaraan dengan kecepatan tinggi.', category: 'Pengejaran' },
    { code: '10-90', title: 'Armed Robbery in Progress', meaning: 'Perampokan bersenjata sedang berlangsung (Toko, Bank Fleeca, Pacific Standard).', scenario: 'Alarm toko/bank berbunyi dan visual tersangka bersenjata terkonfirmasi.', category: 'Darurat' },
    { code: '10-99', title: 'Situation Under Control / Code 4', meaning: 'Situasi telah sepenuhnya terkendali, area aman kembali.', scenario: 'Tersangka berhasil diamankan atau TKP telah disterilkan.', category: 'Status' },
    { code: 'Code 1', title: 'Routine Response', meaning: 'Respon rutin normal tanpa sirine dan tanpa strobo, patuhi rambu lalu lintas.', scenario: 'Panggilan investigasi non-darurat atau administrasi.', category: 'Respon' },
    { code: 'Code 2', title: 'Urgent Silent Run', meaning: 'Respon mendesak dengan lampu strobo tanpa sirine untuk mendekati TKP diam-diam.', scenario: 'Mendekati lokasi perampokan bersandera agar tidak memicu kepanikan tersangka.', category: 'Respon' },
    { code: 'Code 3', title: 'Emergency Full Lights & Sirens', meaning: 'Respon darurat penuh prioritas tertinggi, sirine dan strobo aktif.', scenario: 'Panggilan 10-33, perampokan besar, atau pengejaran 10-80.', category: 'Respon' },
    { code: 'Code 4', title: 'No Further Assistance Needed', meaning: 'Situasi aman terkendali, tidak diperlukan unit bantuan tambahan ke lokasi.', scenario: 'Unit sekunder diizinkan kembali ke patroli rutin masing-masing.', category: 'Respon' },
    { code: 'Signal 100', title: 'Radio Silence for High-Risk Event', meaning: 'Seluruh unit dilarang berbicara di radio selain unit komando penanganan situasi.', scenario: 'Pengejaran intensif atau operasi penggerebekan senjata berat.', category: 'Protokol' },
];

const categories = ['ALL', 'Status', 'Darurat', 'Pengejaran', 'Respon', 'Navigasi', 'Taktis', 'Umum', 'Protokol'];

const filtered10Codes = computed(() => {
    let list = standardPolice10Codes;
    if (selectedCategory.value !== 'ALL') {
        list = list.filter(c => c.category === selectedCategory.value);
    }
    if (searchCode.value.trim()) {
        const q = searchCode.value.toLowerCase().trim();
        list = list.filter(c => 
            c.code.toLowerCase().includes(q) ||
            c.title.toLowerCase().includes(q) ||
            c.meaning.toLowerCase().includes(q) ||
            c.scenario.toLowerCase().includes(q) ||
            c.category.toLowerCase().includes(q)
        );
    }
    return list;
});

const tacChannelGuides = [
    {
        code: 'TAC 1',
        tac_code: 'TAC_1',
        title: 'Dispatch & General Patrol',
        badgeColor: 'blue',
        scope: 'Komunikasi lalu lintas patroli reguler, tilang harian, dan respon panggilan 911 standar.',
        protocol: 'Digunakan oleh seluruh unit patroli LSPD, BCSO, dan SASP saat tidak berada dalam situasi khusus. Pertahankan transmisi radio singkat, padat, dan jelas.'
    },
    {
        code: 'TAC 2',
        tac_code: 'TAC_2',
        title: 'High Speed Vehicle Pursuit (10-80)',
        badgeColor: 'amber',
        scope: 'Pengejaran kendaraan tersangka dan koordinasi formasi interception di jalan raya.',
        protocol: 'Lead unit bertanggung jawab memberikan callout arah (heading, visual mobil, nomor plat, kecepatan). Unit sekunder menyiapkan manuver PIT atau Spikestrip.'
    },
    {
        code: 'TAC 3',
        tac_code: 'TAC_3',
        title: 'Major Robbery & Bank Heist (10-90)',
        badgeColor: 'red',
        scope: 'Penanganan perampokan toko bersenjata, Fleeca Bank, Paleto Bank, Pacific Standard.',
        protocol: 'Hanya unit yang ditugaskan di perimeter dalam dan negosiator yang berkomunikasi. Unit lain menjaga perimeter luar dan memblokir jalur pelarian.'
    },
    {
        code: 'TAC 4',
        tac_code: 'TAC_4',
        title: 'Special Weapons & SWAT Tactical Ops',
        badgeColor: 'purple',
        scope: 'Operasi penggerebekan senjata berat, drug lab raid, dan hostile hostage rescue.',
        protocol: 'Di bawah komando langsung SWAT Commander / Tactical Supervisor. Disiplin radio penuh, gunakan formasi breaching standar dan callout terkoordinasi.'
    },
    {
        code: 'TAC 5',
        tac_code: 'TAC_5',
        title: 'Air Support & Inter-Agency Joint Command',
        badgeColor: 'indigo',
        scope: 'Koordinasi unit udara Air-1/Helikopter, Unit Maritim, dan komando gabungan lintas instansi.',
        protocol: 'Memberikan visual bird-eye view kepada ground units. Koordinasi gabungan LSPD, BCSO, SASP, dan EMS.'
    },
    {
        code: 'TAC 6',
        tac_code: 'TAC_6',
        title: 'Traffic Control & Checkpoint Operations',
        badgeColor: 'emerald',
        scope: 'Operasi razia lalu lintas, pemblokiran jalan raya, dan pengawalan VIP/Konvoi.',
        protocol: 'Digunakan oleh unit Traffic Division untuk mengatur arus kendaraan dan pos penyekatan wilayah.'
    },
    {
        code: 'TAC 7',
        tac_code: 'TAC_7',
        title: 'Special Event & Crowd Control Ops',
        badgeColor: 'sky',
        scope: 'Pengamanan demonstrasi massal, event kota, dan area publik berisiko tinggi.',
        protocol: 'Komunikasi khusus pemantauan situasi lapangan dan koordinasi unit kontinjensi.'
    },
    {
        code: 'TAC 8',
        tac_code: 'TAC_8',
        title: 'Undercover & Detective Bureau Operations',
        badgeColor: 'teal',
        scope: 'Operasi intelijen, pengintaian rahasia (surveillance), dan penyelidikan geng/narkoba.',
        protocol: 'Kanal tertutup khusus unit Detective/CID. Transmisi radio terbatas dan sangat rahasia.'
    },
    {
        code: 'TAC 9',
        tac_code: 'TAC_9',
        title: 'Regional Emergency Response & K9 Division',
        badgeColor: 'orange',
        scope: 'Pencarian orang hilang, operasi unit Anjing Pelacak (K9), dan tanggap bencana wilayah.',
        protocol: 'Koordinasi unit K9 handler dan pencarian jejak tersangka di perbukitan/hutan.'
    },
    {
        code: 'TAC 10',
        tac_code: 'TAC_10',
        title: 'High-Security Transport & State Marshal Command',
        badgeColor: 'rose',
        scope: 'Pengawalan tahanan kelas berat menuju Bolingbroke Penitentiary dan operasi State Marshal.',
        protocol: 'Protokol keamanan maksimum. Seluruh pergerakan konvoi tahanan dilaporkan secara real-time.'
    },
];

const getTacUnitCount = (tacCode) => {
    const ch = props.tacChannels.find(c => c.code === tacCode);
    return ch && ch.video_ids ? ch.video_ids.length : 0;
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        document.exitFullscreen();
        isFullscreen.value = false;
    }
};
</script>

<template>
    <Head title="Panduan Kode 10 & Radio TAC - IME Police Command Center" />

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

                <!-- Page Navigation Links (Clean Minimalist Text Tabs) -->
                <nav class="hidden md:flex items-center space-x-1">
                    <Link 
                        href="/officers"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="Officer Directory (LSPD, BCSO, SASP)"
                    >
                        Officer Directory
                    </Link>

                    <Link 
                        href="/radio-codes"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600/30 text-blue-300 border border-blue-500/50 shadow-sm transition"
                        title="Active Page: 10-Codes & Tactical Radio Channels (TAC 1-10)"
                    >
                        10-Codes & Radio
                    </Link>

                    <Link 
                        href="/about"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="About Police Command Center"
                    >
                        About Platform
                    </Link>

                    <Link 
                        href="/feedback"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-sky-300 hover:bg-sky-950/40 transition"
                        title="Channel Requests & System Feedback"
                    >
                        Feedback & Reports
                    </Link>
                </nav>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center space-x-2">
                <button 
                    @click="toggleFullscreen"
                    class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-700 bg-slate-900 text-slate-300 hover:bg-slate-800 transition flex items-center gap-1.5"
                    title="Toggle Fullscreen"
                >
                    <img :src="isFullscreen ? iconExitFullscreen : iconFullscreen" class="w-3.5 h-3.5 invert opacity-90" alt="Fullscreen" />
                    <span class="hidden md:inline">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
                </button>

                <Link 
                    href="/" 
                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-600/30 transition flex items-center gap-1.5"
                    title="Kembali ke Halaman Utama CCTV Multiview"
                >
                    <img :src="iconFocus" class="w-3.5 h-3.5 invert" alt="" />
                    <span>CCTV Multiview</span>
                </Link>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            
            <!-- Hero Title & Mode Switcher -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2 py-0.5 rounded-full bg-blue-950 text-blue-300 border border-blue-500/40 text-[10px] font-mono font-bold uppercase tracking-wider">
                            SOP Dispatch & Radio Taktis
                        </span>
                        <span class="text-xs text-slate-500 font-mono">• Standar Kepolisian LSPD / BCSO / SASP</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight">
                        PANDUAN KODE 10 & PROTOKOL RADIO
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        Kamus resmi kode radio komunikasi 10-Codes serta SOP penanganan situasi darurat pada kanal Tactical Radio (TAC 1 hingga TAC 10).
                    </p>
                </div>

                <!-- Tab Toggle: Kode 10 vs TAC Protocols -->
                <div class="bg-slate-900 p-1 rounded-2xl border border-slate-800 flex items-center space-x-1 shrink-0">
                    <button 
                        @click="activeTab = 'CODES'"
                        :class="activeTab === 'CODES' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition font-mono flex items-center gap-1.5"
                    >
                        <span>Kode 10 Kepolisian</span>
                    </button>
                    <button 
                        @click="activeTab = 'TAC'"
                        :class="activeTab === 'TAC' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition font-mono flex items-center gap-1.5"
                    >
                        <img :src="iconRadio" class="w-3.5 h-3.5 invert" alt="" />
                        <span>Kanal Radio TAC 1–10</span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: 10-CODES CHEATSHEET -->
            <div v-if="activeTab === 'CODES'" class="space-y-6">
                
                <!-- Search & Category Filters -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 shadow-lg">
                    <div class="relative flex-1 max-w-md">
                        <input 
                            v-model="searchCode" 
                            type="text" 
                            placeholder="Cari kode (misal 10-80, 10-33, pursuit, darurat, ambulans)..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-500 font-mono"
                        />
                        <img :src="iconSearch" class="w-4 h-4 invert opacity-40 absolute left-3 top-3" alt="" />
                    </div>

                    <div class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none">
                        <button 
                            v-for="cat in categories" 
                            :key="cat"
                            @click="selectedCategory = cat"
                            :class="selectedCategory === cat ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400' : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition whitespace-nowrap font-mono"
                        >
                            {{ cat === 'ALL' ? 'Semua Kategori' : cat }}
                        </button>
                    </div>
                </div>

                <!-- Codes Grid Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="item in filtered10Codes" 
                        :key="item.code"
                        class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between hover:border-amber-500/40 hover:shadow-xl transition group"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2.5">
                                <span class="px-2.5 py-1 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/40 font-mono font-black text-sm group-hover:bg-amber-500/30 transition">
                                    {{ item.code }}
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-950 text-slate-400 border border-slate-800 font-mono">
                                    {{ item.category }}
                                </span>
                            </div>

                            <h3 class="text-sm font-bold text-slate-100 group-hover:text-amber-300 transition">
                                {{ item.title }}
                            </h3>

                            <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                {{ item.meaning }}
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] text-slate-400 leading-relaxed bg-slate-950/60 p-2.5 rounded-xl border border-slate-800/60 font-mono">
                            <span class="text-amber-400 font-bold">Contoh Situasi:</span> {{ item.scenario }}
                        </div>
                    </div>
                </div>

            </div>

            <!-- TAB 2: TAC RADIO GUIDELINES (TAC 1 - 5) -->
            <div v-else-if="activeTab === 'TAC'" class="space-y-6">
                
                <!-- Protocol Info Banner -->
                <div class="bg-amber-950/20 border border-amber-500/30 rounded-2xl p-4 text-xs text-amber-200/90 leading-relaxed flex items-start gap-3.5">
                    <div class="p-2.5 rounded-xl bg-amber-500/20 border border-amber-400/40 text-amber-300 shrink-0">
                        <img :src="iconRadio" class="w-5 h-5 invert" alt="" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-300 font-mono">Aturan Penggunaan Kanal Taktis Radio (TAC)</h3>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            Kanal TAC adalah frekuensi radio khusus yang diaktifkan secara otomatis saat terjadi insiden prioritas tinggi (pengejaran, perampokan besar, operasi SWAT) agar saluran radio patroli reguler tidak terganggu.
                        </p>
                    </div>
                </div>

                <!-- 5 TAC Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div 
                        v-for="tac in tacChannelGuides" 
                        :key="tac.code"
                        class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/40 transition hover:shadow-xl space-y-4 flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center space-x-2.5">
                                    <span class="px-3 py-1 rounded-xl bg-blue-600 text-white font-mono font-black text-sm shadow-md shadow-blue-600/30">
                                        {{ tac.code }}
                                    </span>
                                    <h3 class="text-base font-bold text-slate-100">{{ tac.title }}</h3>
                                </div>
                                <span class="text-[11px] px-2.5 py-1 rounded-full font-mono font-bold"
                                    :class="getTacUnitCount(tac.tac_code) > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse' : 'bg-slate-950 text-slate-500 border border-slate-800'"
                                >
                                    {{ getTacUnitCount(tac.tac_code) }} Unit Aktif
                                </span>
                            </div>

                            <div class="text-xs text-slate-300 leading-relaxed bg-slate-950/80 p-3 rounded-xl border border-slate-800/80">
                                <span class="text-blue-400 font-bold font-mono">Ruang Lingkup Operasi:</span>
                                <p class="mt-1 text-slate-300">{{ tac.scope }}</p>
                            </div>

                            <div class="text-xs text-slate-400 bg-slate-950/80 p-3 rounded-xl border border-slate-800/80 leading-relaxed">
                                <span class="text-amber-400 font-bold font-mono">SOP & Protokol Radio:</span>
                                <p class="mt-1 text-slate-300">{{ tac.protocol }}</p>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end">
                            <Link 
                                href="/" 
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-blue-600 text-slate-200 hover:text-white text-xs font-semibold border border-slate-700 hover:border-blue-500 transition flex items-center gap-2 font-mono shadow"
                            >
                                <span>Pantau di CCTV Multiview</span>
                                <span>›</span>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>

        </main>

        <!-- TACTICAL COMMAND CENTER FOOTER (4 COLUMNS) -->
        <footer class="mt-20 bg-[#080d18]/95 border-t border-blue-900/40 text-slate-400 text-xs backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    
                    <!-- Column 1: Branding & Live Status -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-950/70 via-slate-900 to-slate-950 border border-blue-500/40 p-1 shadow-inner overflow-hidden shrink-0">
                                <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black tracking-wider text-blue-400 uppercase font-mono leading-tight">IME ROLEPLAY</h3>
                                <p class="text-[11px] font-bold text-slate-300 tracking-wide uppercase leading-tight">POLICE COMMAND CENTER</p>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Dashboard pemantauan multiview taktis siaran langsung seluruh petugas kepolisian (LSPD, BCSO, SASP) terdaftar di server GTA V IME Roleplay.
                        </p>
                    </div>

                    <!-- Column 2: Quick Tactical Navigation -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Navigasi Taktis</span>
                        </h4>
                        <ul class="space-y-2 text-[11px]">
                            <li>
                                <Link href="/" class="hover:text-blue-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span>›</span>
                                    <span>CCTV Multiview & Cinema Hub</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/officers" class="hover:text-blue-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span>›</span>
                                    <span>Direktori Petugas</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/radio-codes" class="text-amber-400 font-semibold flex items-center gap-1.5">
                                    <span>›</span>
                                    <span>Panduan Kode 10 & Radio TAC 1–5</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/about" class="hover:text-indigo-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span>›</span>
                                    <span>Tentang Command Center Platform</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Police Departments -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Kesatuan Wilayah</span>
                        </h4>
                        <ul class="space-y-2 text-[11px]">
                            <li>
                                <Link href="/officers" class="hover:text-blue-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <span>LSPD - Mission Row HQ</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/officers" class="hover:text-amber-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>BCSO - Paleto & Sandy Shores</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/officers" class="hover:text-teal-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                    <span>SASP - State Troopers</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/officers" class="hover:text-green-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    <span>SAPR - San Andreas Park Rangers</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Community Support -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Dispatcher & Bantuan</span>
                        </h4>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Punya streamer polisi baru atau ingin update callsign dan pangkat dinas? Laporkan langsung ke tim dispatcher.
                        </p>
                        <div class="flex flex-col gap-2 pt-1">
                            <Link 
                                href="/feedback" 
                                class="w-full py-2 px-3 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 transition flex items-center justify-center gap-2"
                            >
                                <img :src="iconFeedback" class="w-3.5 h-3.5 invert" alt="" />
                                <span>Kirim Laporan Dispatcher</span>
                            </Link>
                        </div>
                    </div>

                </div>

                <!-- Bottom Copyright -->
                <div class="mt-10 pt-6 border-t border-slate-800/80 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-slate-500 font-mono">
                    <div>
                        © 2026 IME Roleplay Police Command Center. Tactical Multiview System.
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
