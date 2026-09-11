<script setup>
import { ref, onMounted, computed, watch, onUnmounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

// SVG Icon Assets & Branding Logos
import logoSaspColor from '@/Components/Icons/SASP256.jpg';
import iconLspd from '@/Components/Icons/LSPD_HD.svg';
import iconBcso from '@/Components/Icons/Logo_LSCSD.svg';
import iconSasp from '@/Components/Icons/SASP_HD.svg';
import iconSapr from '@/Components/Icons/ranger_logo.svg';
import iconAllUnits from '@/Components/Icons/category-svgrepo-com.svg';
import iconPersonal from '@/Components/Icons/star-svgrepo-com.svg';
import iconSaver from '@/Components/Icons/gauge-low-svgrepo-com.svg';
import iconPlayAll from '@/Components/Icons/play-full-svgrepo-com.svg';
import iconMute from '@/Components/Icons/mute-svgrepo-com.svg';
import iconUnmute from '@/Components/Icons/unmute-svgrepo-com.svg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconQuickAdd from '@/Components/Icons/button-plus-svgrepo-com.svg';
import iconFullscreen from '@/Components/Icons/full-screen-svgrepo-com.svg';
import iconExitFullscreen from '@/Components/Icons/minimize-svgrepo-com.svg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconLogout from '@/Components/Icons/leave-svgrepo-com.svg';
import iconSearch from '@/Components/Icons/search-svgrepo-com.svg';
import iconRefresh from '@/Components/Icons/refresh-cw-svgrepo-com.svg';
import iconPinPlus from '@/Components/Icons/star-line-svgrepo-com.svg';
import iconPinMinus from '@/Components/Icons/star-svgrepo-com.svg';
import iconEdit from '@/Components/Icons/edit-2-svgrepo-com.svg';
import iconDelete from '@/Components/Icons/delete-2-svgrepo-com.svg';
import iconClock from '@/Components/Icons/time-svgrepo-com.svg';
import iconUser from '@/Components/Icons/user-svgrepo-com.svg';
import iconRoster from '@/Components/Icons/doc-svgrepo-com.svg';
import iconBug from '@/Components/Icons/bug-svgrepo-com.svg';
import iconExternal from '@/Components/Icons/link-external-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconReset from '@/Components/Icons/reset-svgrepo-com.svg';
import iconSend from '@/Components/Icons/send-svgrepo-com.svg';
import iconUrl from '@/Components/Icons/url-checker-svgrepo-com.svg';
import iconChat from '@/Components/Icons/chat-svgrepo-com.svg';
import iconChatRemove from '@/Components/Icons/chat-remove-svgrepo-com.svg';
import iconTarget from '@/Components/Icons/target-svgrepo-com.svg';

const props = defineProps({
    initialStreams: {
        type: Array,
        required: true,
    },
    initialOfflineOfficers: {
        type: Array,
        required: true,
    },
    initialTacChannels: {
        type: Array,
        default: () => [],
    },
    initialReplays: {
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

// State Management
const streams = ref(props.initialStreams);
const offlineOfficers = ref(props.initialOfflineOfficers);
const recentReplays = ref(props.initialReplays || []);
const selectedDepartment = ref('ALL');
const activeTab = ref('10-8'); // '10-8' (online feeds) or '10-7' (offline roster)
const isSidebarOpen = ref(true);
const selectedLayout = ref('auto'); // 'auto', 'grid-2x2', 'grid-3x3', 'grid-4x4', 'focus'
const activeAudioVideoId = ref(null);
const searchFilter = ref('');
const focusedStreamId = ref(null);

// Tactical Radio Channels (TAC 1 to TAC 10) State
const defaultTacChannels = [
    { id: 1, code: 'TAC_1', name: 'TAC 1', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 2, code: 'TAC_2', name: 'TAC 2', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 3, code: 'TAC_3', name: 'TAC 3', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 4, code: 'TAC_4', name: 'TAC 4', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 5, code: 'TAC_5', name: 'TAC 5', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 6, code: 'TAC_6', name: 'TAC 6', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 7, code: 'TAC_7', name: 'TAC 7', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 8, code: 'TAC_8', name: 'TAC 8', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 9, code: 'TAC_9', name: 'TAC 9', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
    { id: 10, code: 'TAC_10', name: 'TAC 10', video_ids: [], expires_at: null, remaining_seconds: 0, is_active: false, unit_count: 0 },
];
const tacChannels = ref(props.initialTacChannels && props.initialTacChannels.length > 0 ? props.initialTacChannels : defaultTacChannels);
const activeTacPopoverVideoId = ref(null);
const tacticalToast = ref(null);

const showTacticalToast = (message, type = 'info') => {
    tacticalToast.value = { message, type };
    setTimeout(() => {
        if (tacticalToast.value?.message === message) {
            tacticalToast.value = null;
        }
    }, 3500);
};

const getStreamTac = (videoId) => {
    if (!videoId) return null;
    const ch = tacChannels.value.find(c => c.video_ids && c.video_ids.includes(videoId));
    return ch ? ch.code : null;
};

const getTacChannel = (tacCode) => {
    return tacChannels.value.find(c => c.code === tacCode) || null;
};

const getTacUnitCount = (tacCode) => {
    const ch = getTacChannel(tacCode);
    return ch && ch.video_ids ? ch.video_ids.length : 0;
};

const getTacRemainingSeconds = (tacCode) => {
    const ch = getTacChannel(tacCode);
    return ch ? (ch.remaining_seconds || 0) : 0;
};

const isTacDepartment = (deptId) => {
    return typeof deptId === 'string' && deptId.startsWith('TAC_');
};

const formatRemainingTime = (seconds) => {
    if (!seconds || seconds <= 0) return '00:00';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

const getTacStreams = (tacCode) => {
    const ch = getTacChannel(tacCode);
    if (!ch || !ch.video_ids || ch.video_ids.length === 0) return [];
    const ids = ch.video_ids.map(id => String(id).trim());
    return allActiveStreams.value.filter(s => ids.includes(String(s.video_id).trim()));
};

// Fetch & Synchronize TAC Channels from Server
const fetchTacChannels = async () => {
    try {
        const res = await fetch('/api/v1/tac', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                tacChannels.value = data.data;
            }
        }
    } catch (e) {
        console.warn('TAC sync failed:', e);
    }
};

// 1-Click TAC Assignment
const assignStreamToTac = async (tacCode, videoId) => {
    if (!tacCode || !videoId) return;
    const cleanVideoId = String(videoId).trim();
    
    // Optimistic UI update
    tacChannels.value.forEach(ch => {
        if (ch.code !== tacCode && ch.video_ids && ch.video_ids.includes(cleanVideoId)) {
            ch.video_ids = ch.video_ids.filter(id => id !== cleanVideoId);
            ch.unit_count = ch.video_ids.length;
            if (ch.unit_count === 0) {
                ch.remaining_seconds = 0;
                ch.is_active = false;
            }
        }
    });

    const targetCh = getTacChannel(tacCode);
    if (targetCh) {
        if (!targetCh.video_ids) targetCh.video_ids = [];
        if (!targetCh.video_ids.includes(cleanVideoId)) {
            targetCh.video_ids.push(cleanVideoId);
        }
        targetCh.unit_count = targetCh.video_ids.length;
        if (!targetCh.remaining_seconds || targetCh.remaining_seconds <= 0) {
            targetCh.remaining_seconds = 1800; // 30 mins
        }
        targetCh.is_active = true;
    }

    showTacticalToast(`Unit berhasil dimasukkan ke ${tacCode.replace('_', ' ')} (30 Menit)`);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/v1/tac/assign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                tac_code: tacCode,
                video_id: cleanVideoId,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error('TAC Assign API Error:', res.status, await res.text());
        }
    } catch (e) {
        console.error('Failed to assign stream to TAC:', e);
    }
};

// Remove stream from TAC
const removeStreamFromTac = async (videoId, tacCode = null) => {
    if (!videoId) return;
    const cleanVideoId = String(videoId).trim();

    // Optimistic UI update
    tacChannels.value.forEach(ch => {
        if (!tacCode || ch.code === tacCode) {
            if (ch.video_ids && ch.video_ids.includes(cleanVideoId)) {
                ch.video_ids = ch.video_ids.filter(id => id !== cleanVideoId);
                ch.unit_count = ch.video_ids.length;
                if (ch.unit_count === 0) {
                    ch.remaining_seconds = 0;
                    ch.is_active = false;
                }
            }
        }
    });

    showTacticalToast('Unit dilepas dari Tactical Radio');

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/v1/tac/remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                video_id: cleanVideoId,
                tac_code: tacCode,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error('TAC Remove API Error:', res.status, await res.text());
        }
    } catch (e) {
        console.error('Failed to remove stream from TAC:', e);
    }
};

// Extend TAC Timer
const extendTacTimer = async (tacCode, minutes = 20) => {
    if (!tacCode) return;

    // Optimistic UI update
    const targetCh = getTacChannel(tacCode);
    if (targetCh) {
        targetCh.remaining_seconds = (targetCh.remaining_seconds || 0) + (minutes * 60);
    }

    showTacticalToast(`Waktu situasi ${tacCode.replace('_', ' ')} diperpanjang +${minutes} menit`);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/v1/tac/extend', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                tac_code: tacCode,
                minutes: minutes,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error('TAC Extend API Error:', res.status, await res.text());
        }
    } catch (e) {
        console.error('Failed to extend TAC timer:', e);
    }
};

// Disband / Clear TAC Channel
const disbandTacChannel = async (tacCode) => {
    if (!tacCode) return;

    if (!confirm(`Apakah Anda yakin ingin mengosongkan / membubarkan kanal ${tacCode.replace('_', ' ')} untuk seluruh penonton?`)) {
        return;
    }

    // Optimistic UI update
    const targetCh = getTacChannel(tacCode);
    if (targetCh) {
        targetCh.video_ids = [];
        targetCh.unit_count = 0;
        targetCh.remaining_seconds = 0;
        targetCh.is_active = false;
        targetCh.expires_at = null;
    }

    showTacticalToast(`Kanal ${tacCode.replace('_', ' ')} telah dibubarkan / dikosongkan`);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/v1/tac/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                tac_code: tacCode,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error('TAC Clear API Error:', res.status, await res.text());
        }
    } catch (e) {
        console.error('Failed to disband TAC channel:', e);
    }
};

// Expiring TAC channel for global alert prompt (Active only in last 60 seconds)
const expiringTacChannel = computed(() => {
    return tacChannels.value.find(c => 
        c.video_ids && 
        c.video_ids.length > 0 && 
        c.remaining_seconds > 0 && 
        c.remaining_seconds <= 60 &&
        selectedDepartment.value !== c.code
    );
});

// Focus Mode Right-Column Live Chat State
const isRightChatOpen = ref(false);

// Browser Fullscreen State & Controller
const isFullscreen = ref(false);
const toggleBrowserFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.warn(`Error attempting to enable fullscreen: ${err.message}`);
        });
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

const handleKeyDown = (e) => {
    if (e.key === 'F11') {
        e.preventDefault();
        toggleBrowserFullscreen();
    } else if (e.key === 'Escape') {
        if (showOfficerFormModal.value) {
            showOfficerFormModal.value = false;
        } else if (activeRightDrawer.value) {
            closeRightDrawer();
        } else if (activeTacPopoverVideoId.value) {
            activeTacPopoverVideoId.value = null;
        }
    }
};

const handleGlobalClick = (e) => {
    if (activeTacPopoverVideoId.value) {
        activeTacPopoverVideoId.value = null;
    }
};

let tacTimerInterval = null;
let tacPollInterval = null;
let streamPollInterval = null;

const fetchLiveStreamsSilently = async () => {
    try {
        const res = await fetch('/api/v1/streams', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        if (res.ok) {
            const json = await res.json();
            if (json.status === 'success' && Array.isArray(json.data)) {
                // Deduplicate incoming streams strictly by video_id
                const incomingMap = new Map();
                json.data.forEach(s => {
                    if (s && s.video_id) {
                        incomingMap.set(String(s.video_id).trim(), s);
                    }
                });
                const incomingStreams = Array.from(incomingMap.values());

                // 1. Smart merge into streams.value
                streams.value.forEach(existing => {
                    const fresh = incomingMap.get(String(existing.video_id).trim());
                    if (fresh) {
                        existing.title = fresh.title;
                        existing.viewers_count = fresh.viewers_count;
                        existing.status = fresh.status;
                        existing.description = fresh.description;
                        existing.incident_code = fresh.incident_code;
                        if (fresh.officer) {
                            existing.officer = fresh.officer;
                        }
                    }
                });

                // Remove ended streams from streams.value
                streams.value = streams.value.filter(existing => incomingMap.has(String(existing.video_id).trim()));

                // Add newly detected live streams
                incomingStreams.forEach(fresh => {
                    if (!streams.value.some(existing => String(existing.video_id).trim() === String(fresh.video_id).trim())) {
                        streams.value.push(fresh);
                    }
                });

                // 2. Update offline officers roster & replays
                if (Array.isArray(json.offline_officers)) {
                    offlineOfficers.value = json.offline_officers;
                }
                if (Array.isArray(json.replays)) {
                    recentReplays.value = json.replays;
                }
            }
        }
    } catch (e) {
        console.warn('Silent live streams polling failed:', e);
    }
};

const isSyncingFeeds = ref(false);

const triggerManualSync = async () => {
    if (isSyncingFeeds.value) return;
    isSyncingFeeds.value = true;
    showTacticalToast('Menghubungkan ke satelit YouTube untuk mendeteksi unit 10-8...', 'info');

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/v1/sync', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            }
        });
        await fetchLiveStreamsSilently();
        showTacticalToast('Sinkronisasi selesai! Siaran terbaru telah dimuat.', 'info');
    } catch (e) {
        console.warn('Manual sync failed:', e);
        await fetchLiveStreamsSilently();
    } finally {
        isSyncingFeeds.value = false;
    }
};

const tickTacTimers = () => {
    let hasExpired = false;
    tacChannels.value.forEach(ch => {
        if (ch.remaining_seconds > 0) {
            ch.remaining_seconds -= 1;
            if (ch.remaining_seconds <= 0) {
                ch.remaining_seconds = 0;
                ch.is_active = false;
                ch.video_ids = [];
                ch.unit_count = 0;
                hasExpired = true;
            }
        }
    });
    if (hasExpired) {
        fetchTacChannels();
    }
};

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    window.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', handleGlobalClick);
    loadPersonalStreamsFromStorage();
    
    // TAC timer ticker (every 1s), TAC channel polling (every 15s), and Live Streams auto-sync (every 15s)
    tacTimerInterval = setInterval(tickTacTimers, 1000);
    tacPollInterval = setInterval(fetchTacChannels, 15000);
    streamPollInterval = setInterval(fetchLiveStreamsSilently, 15000);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    window.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('click', handleGlobalClick);
    if (tacTimerInterval) clearInterval(tacTimerInterval);
    if (tacPollInterval) clearInterval(tacPollInterval);
    if (streamPollInterval) clearInterval(streamPollInterval);
});

// Origin URL & Embed Domain for YouTube API Handshake
const originUrl = ref(typeof window !== 'undefined' ? (window.location.origin || (window.location.protocol + '//' + window.location.host)) : '');
const chatEmbedDomain = computed(() => {
    if (typeof window !== 'undefined' && window.location) {
        return window.location.hostname;
    }
    return 'ime-police-multiview.test';
});

// Bandwidth Optimization & Data Saver State
const isDataSaverEnabled = ref(true);
const activePreviewVideoIds = ref([]);
const activeGridVideoIds = ref([]);

const toggleSidebarPreview = (videoId) => {
    const idx = activePreviewVideoIds.value.indexOf(videoId);
    if (idx === -1) {
        activePreviewVideoIds.value.push(videoId);
        nextTick(() => {
            setTimeout(() => {
                initializePlayer(videoId);
            }, 200);
        });
    } else {
        activePreviewVideoIds.value.splice(idx, 1);
        controlPlayerAudio(videoId, false);
        delete players[videoId];
        if (activeAudioVideoId.value === videoId) {
            activeAudioVideoId.value = null;
        }
    }
};

const toggleGridStreamPlay = (videoId) => {
    const idx = activeGridVideoIds.value.indexOf(videoId);
    if (idx === -1) {
        activeGridVideoIds.value.push(videoId);
        nextTick(() => {
            setTimeout(() => {
                initializePlayer(videoId);
            }, 200);
        });
    } else {
        activeGridVideoIds.value.splice(idx, 1);
        controlPlayerAudio(videoId, false);
        delete players[videoId];
        if (activeAudioVideoId.value === videoId) {
            activeAudioVideoId.value = null;
        }
    }
};

const enableDataSaver = () => {
    isDataSaverEnabled.value = true;
    activeGridVideoIds.value = [];
    activePreviewVideoIds.value = [];
    if (selectedLayout.value !== 'focus') {
        activeAudioVideoId.value = null;
        muteAll();
    }
};

const disableDataSaverAndPlayAll = () => {
    isDataSaverEnabled.value = false;
    activeGridVideoIds.value = [];
    activePreviewVideoIds.value = [];
    nextTick(() => {
        setTimeout(() => {
            initializeAllPlayers();
        }, 250);
    });
};

const playAllGridStreams = () => {
    disableDataSaverAndPlayAll();
};

const pauseAllGridStreams = () => {
    enableDataSaver();
};

// 1-Click In-Place YouTube Subscription Popup (Never navigates away from dashboard)
const openSubscribePopup = (channelIdOrHandle, officerName = '') => {
    if (!channelIdOrHandle) return;

    let subUrl = '';
    if (channelIdOrHandle.startsWith('UC')) {
        subUrl = `https://www.youtube.com/channel/${channelIdOrHandle}?sub_confirmation=1`;
    } else {
        const cleanHandle = channelIdOrHandle.startsWith('@') ? channelIdOrHandle : `@${channelIdOrHandle}`;
        subUrl = `https://www.youtube.com/${cleanHandle}?sub_confirmation=1`;
    }

    const width = 640;
    const height = 660;
    const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - height) / 2));

    window.open(
        subUrl,
        'YouTubeSubscribeModal',
        `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,status=no,toolbar=no,menubar=no,location=no,resizable=yes`
    );
};

// Modals State
const isQuickAddModalOpen = ref(false);
const activeChatVideoId = ref(null);

// Personal Category & Custom Ad-hoc Streams (Browser LocalStorage, Max 6 Active Videos)
const MAX_PERSONAL_STREAMS = 6;
const personalVideoIds = ref([]);
const customStreams = ref([]);

const loadPersonalStreamsFromStorage = () => {
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            const savedIds = localStorage.getItem('ime_personal_video_ids');
            if (savedIds) {
                personalVideoIds.value = JSON.parse(savedIds);
            }
            const savedCustom = localStorage.getItem('ime_personal_custom_streams');
            if (savedCustom) {
                customStreams.value = JSON.parse(savedCustom);
            }
        }
    } catch (e) {
        console.warn('Failed to read personal streams from localStorage:', e);
    }
};

const savePersonalStreamsToStorage = () => {
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            localStorage.setItem('ime_personal_video_ids', JSON.stringify(personalVideoIds.value));
            localStorage.setItem('ime_personal_custom_streams', JSON.stringify(customStreams.value));
        }
    } catch (e) {
        console.warn('Failed to save personal streams to localStorage:', e);
    }
};

// All combined streams (Deduplicated strictly by video_id)
const allActiveStreams = computed(() => {
    const streamMap = new Map();
    // 1. Add official synced streams first
    (streams.value || []).forEach(s => {
        if (s && s.video_id) {
            streamMap.set(String(s.video_id).trim(), s);
        }
    });
    // 2. Add custom streams only if not already present from official sync
    (customStreams.value || []).forEach(cs => {
        if (cs && cs.video_id) {
            const key = String(cs.video_id).trim();
            if (!streamMap.has(key)) {
                streamMap.set(key, cs);
            }
        }
    });
    return Array.from(streamMap.values());
});

// Currently active/online personal streams (matches video_id, officer channel/handle, or custom stream)
const activePersonalStreams = computed(() => {
    return allActiveStreams.value.filter(s => 
        personalVideoIds.value.includes(s.video_id) || 
        (s.officer?.channel_id && personalVideoIds.value.includes(s.officer.channel_id)) ||
        (s.officer?.handle && personalVideoIds.value.includes(s.officer.handle)) ||
        customStreams.value.some(cs => cs.video_id === s.video_id)
    );
});

// Total count of currently active/online personal streams (0 to 6)
const totalPersonalCount = computed(() => {
    return activePersonalStreams.value.length;
});

// Total count of all saved personal entries (active + offline)
const totalSavedPersonalCount = computed(() => {
    const combined = new Set([
        ...personalVideoIds.value,
        ...customStreams.value.map(s => s.video_id)
    ]);
    return combined.size;
});

const isPersonalStream = (streamOrId) => {
    if (!streamOrId) return false;
    if (typeof streamOrId === 'string') {
        return personalVideoIds.value.includes(streamOrId) || customStreams.value.some(s => s.video_id === streamOrId);
    }
    const s = streamOrId;
    return personalVideoIds.value.includes(s.video_id) || 
           (s.officer?.channel_id && personalVideoIds.value.includes(s.officer.channel_id)) ||
           (s.officer?.handle && personalVideoIds.value.includes(s.officer.handle)) ||
           customStreams.value.some(cs => cs.video_id === s.video_id);
};

const togglePersonalStream = (videoId) => {
    const idx = personalVideoIds.value.indexOf(videoId);
    if (idx !== -1) {
        personalVideoIds.value.splice(idx, 1);
        customStreams.value = customStreams.value.filter(s => s.video_id !== videoId);
        savePersonalStreamsToStorage();
    } else {
        if (activePersonalStreams.value.length >= MAX_PERSONAL_STREAMS) {
            alert(`Maksimal ${MAX_PERSONAL_STREAMS} video aktif untuk kategori Personal! Hapus atau unpin salah satu video aktif terlebih dahulu.`);
            return;
        }
        personalVideoIds.value.push(videoId);
        savePersonalStreamsToStorage();
    }
};

const removePersonalStream = (id) => {
    personalVideoIds.value = personalVideoIds.value.filter(itemId => itemId !== id);
    customStreams.value = customStreams.value.filter(s => s.video_id !== id && s.id !== id);
    savePersonalStreamsToStorage();
};

const clearAllPersonalStreams = () => {
    if (confirm('Apakah Anda yakin ingin mengosongkan seluruh daftar pin / feed tersimpan di kategori Personal?')) {
        personalVideoIds.value = [];
        customStreams.value = [];
        savePersonalStreamsToStorage();
    }
};



// Safe HTML escaping helper to prevent XSS
const escapeHtml = (text) => {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

// Format description with clickable links and preserved line breaks
const formatDescriptionWithLinks = (text) => {
    if (!text) return '';
    const safeText = escapeHtml(text);
    
    // Convert standard URLs (https://, http://) into clickable styled links
    const urlRegex = /(https?:\/\/[^\s<>"']+)/g;
    return safeText.replace(urlRegex, (url) => {
        return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-sky-400 hover:text-sky-300 underline font-medium break-all hover:text-white transition-colors">${url}</a>`;
    });
};

// Stream Info / Description Toggle State & Lazy Detail Loader
const expandedInfoVideoIds = ref([]);
const isFetchingStreamDetail = ref({});

const fetchFullStreamDetails = async (videoId) => {
    if (!videoId || isFetchingStreamDetail.value[videoId]) return;
    isFetchingStreamDetail.value[videoId] = true;
    try {
        const res = await fetch(`/api/v1/stream-details?video_id=${videoId}`);
        if (res.ok) {
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                const data = json.data;
                // Update in customStreams if present
                const cs = customStreams.value.find(s => s.video_id === videoId);
                if (cs) {
                    if (data.description) cs.description = data.description;
                    if (data.title) cs.title = data.title;
                }
                // Update in streams if present
                const s = streams.value.find(s => s.video_id === videoId);
                if (s) {
                    if (data.description) s.description = data.description;
                    if (data.title) s.title = data.title;
                }
                savePersonalStreamsToStorage();
            }
        }
    } catch (e) {
        console.warn('Failed to fetch stream details for ' + videoId, e);
    } finally {
        isFetchingStreamDetail.value[videoId] = false;
    }
};

const toggleStreamInfo = (videoId) => {
    const idx = expandedInfoVideoIds.value.indexOf(videoId);
    if (idx !== -1) {
        expandedInfoVideoIds.value.splice(idx, 1);
        return;
    }
    
    expandedInfoVideoIds.value.push(videoId);

    // If description is missing or ends with "..." (search snippet), fetch full description in background
    const targetStream = allActiveStreams.value.find(s => s.video_id === videoId);
    if (!targetStream?.description || targetStream.description.endsWith('...') || targetStream.description.endsWith('…')) {
        fetchFullStreamDetails(videoId);
    }
};

const isStreamInfoOpen = (videoId) => expandedInfoVideoIds.value.includes(videoId);



// Unified list of saved personal items with online/offline status for drawer management
const savedPersonalList = computed(() => {
    const list = [];
    const seen = new Set();

    // 1. Add from customStreams
    customStreams.value.forEach(cs => {
        if (!seen.has(cs.video_id)) {
            seen.add(cs.video_id);
            const isOnline = allActiveStreams.value.some(s => s.video_id === cs.video_id);
            list.push({
                id: cs.video_id,
                rawId: cs.video_id,
                video_id: cs.video_id,
                name: cs.officer?.officer_name || cs.title || 'Custom Stream',
                subtext: cs.officer?.callsign || cs.video_id,
                thumbnail: cs.thumbnail,
                isOnline: isOnline,
            });
        }
    });

    // 2. Add from personalVideoIds
    personalVideoIds.value.forEach(id => {
        if (!seen.has(id)) {
            seen.add(id);
            const activeStream = allActiveStreams.value.find(s => 
                s.video_id === id || s.officer?.channel_id === id || s.officer?.handle === id
            );
            const offlineOfficer = offlineOfficers.value.find(o => 
                o.channel_id === id || o.handle === id
            );

            if (activeStream) {
                list.push({
                    id: id,
                    rawId: id,
                    video_id: activeStream.video_id,
                    name: activeStream.officer?.officer_name || activeStream.title || id,
                    subtext: activeStream.officer?.callsign || activeStream.officer?.handle || id,
                    thumbnail: activeStream.thumbnail,
                    isOnline: true,
                });
            } else if (offlineOfficer) {
                list.push({
                    id: id,
                    rawId: id,
                    video_id: id,
                    name: offlineOfficer.officer_name,
                    subtext: `${offlineOfficer.department} • ${offlineOfficer.callsign}`,
                    thumbnail: offlineOfficer.avatar_url,
                    isOnline: false,
                });
            } else {
                list.push({
                    id: id,
                    rawId: id,
                    video_id: id,
                    name: id.length === 11 ? `Video (${id})` : id,
                    subtext: 'Ended / Offline Stream',
                    thumbnail: id.length === 11 ? `https://i.ytimg.com/vi/${id}/hqdefault.jpg` : null,
                    isOnline: false,
                });
            }
        }
    });

    return list;
});

// Quick Add Live Hashtag / Keyword Search State
const quickAddMode = ref('SEARCH'); // 'SEARCH' or 'MANUAL'
const liveSearchQuery = ref('#imeroleplay');
const isLiveSearching = ref(false);
const liveSearchResults = ref([]);
const liveSearchError = ref('');
const liveSearchSuccessNotice = ref('');

// Popular / Gang Hashtag Preset Chips
const popularHashtagPresets = [
    { label: '#imeroleplay', query: '#imeroleplay' },
    { label: '#EMS', query: '#imeroleplay #emsime' },
    { label: '#DOJ', query: '#imeroleplay #DOJ' },
    { label: '#DOC', query: '#imeroleplay #DOC' },
    { label: '#GOV', query: '#imeroleplay #GOV' },
    { label: '#Vagabond', query: '#imeroleplay #Vagabond' },
    { label: '#Allstars', query: '#imeroleplay #Allstars' },
    { label: '#4Blood', query: '#imeroleplay #4Blood' },
    { label: '#5tar', query: '#imeroleplay #5tar' },
];

const quickAddInput = ref({
    urlOrId: '',
    officerName: 'External Unit',
    callsign: 'TAC-UNIT',
    department: 'LSPD',
    patrolZone: 'Incident Sector',
});

// Tactical Clock
const currentTime = ref('');
const currentDate = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', { hour12: false });
    currentDate.value = now.toLocaleDateString('en-US', { 
        weekday: 'short', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};

let timeInterval = null;
const closeMoreTac = () => {
    isMoreTacOpen.value = false;
};

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
    window.addEventListener('click', closeMoreTac);
});
onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
    window.removeEventListener('click', closeMoreTac);
});

// Main Visible Department Tabs (ALL, PERSONAL, LSPD, BCSO, SASP, SAPR + Primary TAC 1 to 3)
const departments = [
    { id: 'ALL', name: 'ALL UNITS', icon: iconAllUnits, isSvg: true, color: 'border-slate-600 text-slate-300' },
    { id: 'PERSONAL', name: 'PERSONAL', icon: iconPersonal, isSvg: true, color: 'border-purple-500 text-purple-300 bg-purple-950/40' },
    { id: 'LSPD', name: 'LSPD', icon: iconLspd, isSvg: true, color: 'border-blue-500 text-blue-400 bg-blue-950/40' },
    { id: 'BCSO', name: 'BCSO', icon: iconBcso, isSvg: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'SASP', name: 'SASP', icon: iconSasp, isSvg: true, color: 'border-teal-500 text-teal-400 bg-teal-950/40' },
    { id: 'SAPR', name: 'SAPR', icon: iconSapr, isSvg: true, color: 'border-green-500 text-green-400 bg-green-950/40' },
    { id: 'TAC_1', name: 'TAC 1', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_2', name: 'TAC 2', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_3', name: 'TAC 3', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
];

// Extended Dropdown TAC Channels (TAC 4 to TAC 10)
const dropdownTacDepartments = [
    { id: 'TAC_4', name: 'TAC 4', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_5', name: 'TAC 5', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_6', name: 'TAC 6', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_7', name: 'TAC 7', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_8', name: 'TAC 8', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_9', name: 'TAC 9', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'TAC_10', name: 'TAC 10', icon: iconRadio, isSvg: true, isTac: true, color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
];

const isMoreTacOpen = ref(false);

const getDeptIcon = (dept) => {
    if (typeof dept === 'string' && dept.startsWith('TAC_')) return iconRadio;
    switch (dept) {
        case 'LSPD': return iconLspd;
        case 'BCSO': return iconBcso;
        case 'SASP': return iconSasp;
        case 'SAPR':
        case 'PARK RANGER': return iconSapr;
        case 'PERSONAL': return iconPersonal;
        default: return iconAllUnits;
    }
};

// Department styling helper
const getDeptBadgeClass = (dept) => {
    if (typeof dept === 'string' && dept.startsWith('TAC_')) return 'bg-amber-600/30 text-amber-300 border-amber-500/50';
    switch (dept) {
        case 'LSPD': return 'bg-blue-600/30 text-blue-300 border-blue-500/50';
        case 'BCSO': return 'bg-amber-600/30 text-amber-300 border-amber-500/50';
        case 'SASP': return 'bg-teal-600/30 text-teal-300 border-teal-500/50';
        case 'SAPR':
        case 'PARK RANGER': return 'bg-green-600/30 text-green-300 border-green-500/50';
        default: return 'bg-slate-700/40 text-slate-300 border-slate-600';
    }
};

// Filtered Streams (Online 10-8)
const filteredStreams = computed(() => {
    let result = allActiveStreams.value;

    if (selectedDepartment.value === 'PERSONAL') {
        result = activePersonalStreams.value;
    } else if (isTacDepartment(selectedDepartment.value)) {
        result = getTacStreams(selectedDepartment.value);
    } else if (selectedDepartment.value !== 'ALL') {
        if (selectedDepartment.value === 'SAPR') {
            result = result.filter(s => s.officer && (s.officer.department === 'SAPR' || s.officer.department === 'PARK RANGER'));
        } else {
            result = result.filter(s => s.officer && s.officer.department === selectedDepartment.value);
        }
    }

    if (searchFilter.value.trim() !== '') {
        const query = searchFilter.value.toLowerCase();
        result = result.filter(s => 
            (s.title && s.title.toLowerCase().includes(query)) || 
            (s.officer?.officer_name && s.officer.officer_name.toLowerCase().includes(query)) || 
            (s.officer?.callsign && s.officer.callsign.toLowerCase().includes(query)) || 
            (s.officer?.badge_number && s.officer.badge_number.toLowerCase().includes(query)) || 
            (s.officer?.streamer_name && s.officer.streamer_name.toLowerCase().includes(query)) || 
            (s.officer?.patrol_zone && s.officer.patrol_zone.toLowerCase().includes(query))
        );
    }

    return result;
});

// Filtered Offline Officers (10-7)
const filteredOfflineOfficers = computed(() => {
    if (isTacDepartment(selectedDepartment.value)) {
        return [];
    }

    let result = offlineOfficers.value;

    if (selectedDepartment.value === 'PERSONAL') {
        result = result.filter(o => personalVideoIds.value.includes(o.channel_id) || (o.handle && personalVideoIds.value.includes(o.handle)));
    } else if (selectedDepartment.value !== 'ALL') {
        if (selectedDepartment.value === 'SAPR') {
            result = result.filter(o => o.department === 'SAPR' || o.department === 'PARK RANGER');
        } else {
            result = result.filter(o => o.department === selectedDepartment.value);
        }
    }

    if (searchFilter.value.trim() !== '') {
        const query = searchFilter.value.toLowerCase();
        result = result.filter(o => 
            o.officer_name.toLowerCase().includes(query) || 
            o.callsign.toLowerCase().includes(query) ||
            o.streamer_name.toLowerCase().includes(query) ||
            (o.badge_number && o.badge_number.toLowerCase().includes(query)) ||
            (o.patrol_zone && o.patrol_zone.toLowerCase().includes(query))
        );
    }

    return result;
});

// Sidebar Hidden stream video IDs
const hiddenStreamVideoIds = ref([]);
const toggleStreamVisibility = (videoId) => {
    const idx = hiddenStreamVideoIds.value.indexOf(videoId);
    if (idx === -1) {
        hiddenStreamVideoIds.value.push(videoId);
    } else {
        hiddenStreamVideoIds.value.splice(idx, 1);
    }
};

const visibleStreams = computed(() => {
    return filteredStreams.value.filter(s => !hiddenStreamVideoIds.value.includes(s.video_id));
});

// Displayed Grid Streams (Respects layout limits to prevent offscreen video bandwidth drain)
const displayedGridStreams = computed(() => {
    const list = visibleStreams.value;
    if (selectedLayout.value === 'grid-2x2') {
        return list.slice(0, 4);
    }
    if (selectedLayout.value === 'grid-3x3') {
        return list.slice(0, 9);
    }
    if (selectedLayout.value === 'grid-4x4') {
        return list.slice(0, 16);
    }
    return list;
});

// ==========================================
// NETFLIX-STYLE CINEMA HUB COMPUTED CATEGORIES
// ==========================================

// All combined catalog streams (Active Live Streams prioritized + Offline Patrol Replays, strictly deduplicated by video_id)
const allCatalogStreams = computed(() => {
    const catalogMap = new Map();

    // 1. Prioritize active live streams first
    allActiveStreams.value.forEach(s => {
        if (s && s.video_id) {
            catalogMap.set(String(s.video_id).trim(), s);
        }
    });

    // 2. Add recent replays only if video_id is not already present
    (recentReplays.value || []).forEach(replay => {
        if (replay && replay.video_id) {
            const key = String(replay.video_id).trim();
            if (!catalogMap.has(key)) {
                catalogMap.set(key, replay);
            }
        }
    });

    let list = Array.from(catalogMap.values());

    if (searchFilter.value.trim() !== '') {
        const query = searchFilter.value.toLowerCase();
        return list.filter(s => 
            (s.title && s.title.toLowerCase().includes(query)) || 
            (s.officer?.officer_name && s.officer.officer_name.toLowerCase().includes(query)) ||
            (s.officer?.callsign && s.officer.callsign.toLowerCase().includes(query)) ||
            (s.officer?.badge_number && s.officer.badge_number.toLowerCase().includes(query)) ||
            (s.officer?.streamer_name && s.officer.streamer_name.toLowerCase().includes(query)) ||
            (s.officer?.patrol_zone && s.officer.patrol_zone.toLowerCase().includes(query))
        );
    }

    return list;
});

// Hero Spotlight: #1 Live patrol stream, or if none live -> #1 Latest patrol replay
const topHeroStream = computed(() => {
    if (allActiveStreams.value.length > 0) {
        return [...allActiveStreams.value].sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0))[0];
    }
    if (recentReplays.value.length > 0) {
        return recentReplays.value[0];
    }
    return null;
});

// Live trending streams (sorted by viewers)
const trendingStreams = computed(() => {
    return [...allActiveStreams.value].sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0));
});

// Support 1K Subs (Streamers / Officers with < 1,000 subscribers)
const support1kStreams = computed(() => {
    return allCatalogStreams.value.filter(s => {
        const count = s.officer?.subscriber_count;
        return typeof count === 'number' && count > 0 && count < 1000;
    }).sort((a, b) => {
        if (a.status === 'LIVE' && b.status !== 'LIVE') return -1;
        if (b.status === 'LIVE' && a.status !== 'LIVE') return 1;
        return (b.officer?.subscriber_count || 0) - (a.officer?.subscriber_count || 0);
    });
});

// Recent offline patrol video replays / VODs
const recentReplayStreams = computed(() => {
    const liveIds = new Set(allActiveStreams.value.map(s => String(s.video_id).trim()));
    return recentReplays.value.filter(r => !liveIds.has(String(r.video_id).trim()));
});

// Department Catalog Streams (Live + Replay VODs)
const lspdCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(s => s.officer && s.officer.department === 'LSPD');
});

const bcsoCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(s => s.officer && s.officer.department === 'BCSO');
});

const saspCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(s => s.officer && s.officer.department === 'SASP');
});

const saprCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(s => s.officer && (s.officer.department === 'SAPR' || s.officer.department === 'PARK RANGER'));
});

const specialOpsCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(s => {
        const text = `${s.officer?.rank || ''} ${s.officer?.callsign || ''} ${s.title || ''}`.toLowerCase();
        return text.includes('swat') || text.includes('k9') || text.includes('k-9') || text.includes('srt') || text.includes('air') || text.includes('trooper') || text.includes('investigation') || text.includes('detective');
    });
});

const tacSituationalStreams = computed(() => {
    const allTacVideoIds = [];
    tacChannels.value.forEach(ch => {
        if (ch.video_ids && Array.isArray(ch.video_ids)) {
            ch.video_ids.forEach(id => allTacVideoIds.push(String(id).trim()));
        }
    });
    return allActiveStreams.value.filter(s => allTacVideoIds.includes(String(s.video_id).trim()));
});

// Scroll helper for horizontal swimlane rows
const scrollRow = (rowId, direction = 'right') => {
    const el = document.getElementById(rowId);
    if (!el) return;
    const scrollAmount = direction === 'left' ? -650 : 650;
    el.scrollBy({ left: scrollAmount, behavior: 'smooth' });
};

// 1-Click Launch from Cinema Hub into Focus Lead View
const playStreamInFocus = (stream) => {
    if (!stream) return;
    if (stream.officer?.department) {
        selectedDepartment.value = stream.officer.department;
    }
    selectedLayout.value = 'focus';
    focusedStreamId.value = stream.video_id || stream.id;
    
    // If it's a VOD not yet in allActiveStreams, inject into customStreams so Focus mode displays it
    if (!allActiveStreams.value.some(s => s.video_id === stream.video_id)) {
        if (!customStreams.value.some(cs => cs.video_id === stream.video_id)) {
            customStreams.value.push(stream);
        }
    }

    if (!isDataSaverEnabled.value) {
        toggleGridStreamPlay(stream.video_id);
    }
    showTacticalToast(`Memutar siaran unit: ${stream.officer?.callsign || 'Unit'} (${stream.officer?.officer_name || 'Officer'})`, 'info');
};

// YouTube Player Engine & Resilient Audio Controller
let players = {};
const ytApiReady = ref(false);

const loadYouTubeAPI = () => {
    if (window.YT && window.YT.Player) {
        ytApiReady.value = true;
        initializeAllPlayers();
        return;
    }

    if (!document.getElementById('yt-iframe-api-script')) {
        const tag = document.createElement('script');
        tag.id = 'yt-iframe-api-script';
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }

    window.onYouTubeIframeAPIReady = () => {
        ytApiReady.value = true;
        initializeAllPlayers();
    };
};

const initializePlayer = (videoId) => {
    if (!window.YT || !window.YT.Player) return;
    const elementId = `yt-bodycam-${videoId}`;
    const element = document.getElementById(elementId);
    
    // If element exists, bind player safely without destroy
    if (element) {
        try {
            delete players[videoId];
            players[videoId] = new window.YT.Player(elementId, {
                events: {
                    'onReady': (event) => {
                        const isFocusedLead = (selectedLayout.value === 'focus' && primaryFocusedStream.value?.video_id === videoId);
                        const isAudioActive = activeAudioVideoId.value === videoId;

                        // Audio initialization
                        if (isAudioActive) {
                            try {
                                event.target.unMute();
                                event.target.setVolume(100);
                            } catch(e) {}
                        } else {
                            try {
                                event.target.mute();
                            } catch(e) {}
                        }

                        // Quality initialization (144p/360p for all grid & support units, Auto-HD 1080p only for Lead Focus)
                        const shouldBeHighQuality = isFocusedLead;
                        applyQualityToPlayer(videoId, shouldBeHighQuality);

                        try {
                            event.target.playVideo();
                        } catch(e) {}
                    },
                }
            });
        } catch (err) {
            console.warn("Error initializing player for " + videoId, err);
        }
    }
};

const initializeAllPlayers = () => {
    if (!window.YT || !window.YT.Player) return;

    if (selectedLayout.value === 'focus') {
        if (primaryFocusedStream.value) {
            initializePlayer(primaryFocusedStream.value.video_id);
        }
        activePreviewVideoIds.value.forEach(id => {
            initializePlayer(id);
        });
    } else {
        if (!isDataSaverEnabled.value) {
            displayedGridStreams.value.forEach(stream => {
                initializePlayer(stream.video_id);
            });
        } else {
            activeGridVideoIds.value.forEach(id => {
                initializePlayer(id);
            });
            if (activeAudioVideoId.value) {
                initializePlayer(activeAudioVideoId.value);
            }
        }
    }
};

// Core Resilient Audio Controller (HTML5 postMessage + YT API fallback)
const controlPlayerAudio = (videoId, shouldUnmute) => {
    // 1. Direct IFrame HTML5 postMessage (Instant across all iframes)
    const iframes = document.querySelectorAll(`iframe[id="yt-bodycam-${videoId}"]`);
    iframes.forEach(iframe => {
        if (iframe && iframe.contentWindow) {
            try {
                const func = shouldUnmute ? 'unMute' : 'mute';
                iframe.contentWindow.postMessage(
                    JSON.stringify({ event: 'command', func: func, args: [] }),
                    '*'
                );
                if (shouldUnmute) {
                    iframe.contentWindow.postMessage(
                        JSON.stringify({ event: 'command', func: 'setVolume', args: [100] }),
                        '*'
                    );
                    iframe.contentWindow.postMessage(
                        JSON.stringify({ event: 'command', func: 'playVideo', args: [] }),
                        '*'
                    );
                }
            } catch (err) {
                console.warn("postMessage audio failed for " + videoId, err);
            }
        }
    });

    // 2. YT.Player API Companion Method
    if (players[videoId]) {
        try {
            if (shouldUnmute) {
                if (typeof players[videoId].unMute === 'function') {
                    players[videoId].unMute();
                    players[videoId].setVolume(100);
                }
            } else {
                if (typeof players[videoId].mute === 'function') {
                    players[videoId].mute();
                }
            }
        } catch (e) {
            console.warn("YT.Player instance call failed for " + videoId, e);
        }
    }
};

// Video Playback Quality Controller (Enforces 144p/360p for Data Saver, Auto-HD for Lead Focus)
const applyQualityToPlayer = (videoId, isHighQuality) => {
    const targetQuality = isHighQuality ? 'highres' : 'small';
    const qualityRange = isHighQuality ? ['hd1080', 'highres', 'default'] : ['small', 'medium'];

    // 1. YT.Player API
    if (players[videoId]) {
        try {
            if (typeof players[videoId].setPlaybackQualityRange === 'function') {
                players[videoId].setPlaybackQualityRange(qualityRange);
            }
            if (typeof players[videoId].setPlaybackQuality === 'function') {
                players[videoId].setPlaybackQuality(targetQuality);
            }
            if (typeof players[videoId].setSuggestedQuality === 'function') {
                players[videoId].setSuggestedQuality(targetQuality);
            }
        } catch (e) {}
    }

    // 2. Direct HTML5 postMessage (Instant across all iframes)
    const iframes = document.querySelectorAll(`iframe[id="yt-bodycam-${videoId}"]`);
    iframes.forEach(iframe => {
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage(
                    JSON.stringify({ event: 'command', func: 'setPlaybackQuality', args: [targetQuality] }),
                    '*'
                );
                iframe.contentWindow.postMessage(
                    JSON.stringify({ event: 'command', func: 'setPlaybackQualityRange', args: qualityRange }),
                    '*'
                );
                iframe.contentWindow.postMessage(
                    JSON.stringify({ event: 'command', func: 'setSuggestedQuality', args: [targetQuality] }),
                    '*'
                );
            } catch (err) {}
        }
    });
};

const updateAllStreamQualities = () => {
    if (selectedLayout.value === 'focus') {
        const leadId = primaryFocusedStream.value?.video_id;
        if (leadId) {
            applyQualityToPlayer(leadId, true);
        }
        activePreviewVideoIds.value.forEach(id => {
            if (id !== leadId) {
                applyQualityToPlayer(id, false);
            }
        });
    } else {
        displayedGridStreams.value.forEach(stream => {
            applyQualityToPlayer(stream.video_id, false);
        });
    }
};

const toggleGlobalDataSaver = () => {
    if (isDataSaverEnabled.value) {
        disableDataSaverAndPlayAll();
    } else {
        enableDataSaver();
    }
};

// Single-Audio Policy Enforcement
const toggleAudio = (videoId) => {
    if (activeAudioVideoId.value === videoId) {
        controlPlayerAudio(videoId, false);
        activeAudioVideoId.value = null;
        return;
    }

    // Mute ALL other players first (Strict Single Audio Rule)
    allActiveStreams.value.forEach(s => {
        controlPlayerAudio(s.video_id, false);
    });
    Object.keys(players).forEach(id => {
        controlPlayerAudio(id, false);
    });

    // Unmute requested stream
    controlPlayerAudio(videoId, true);
    activeAudioVideoId.value = videoId;
};

const muteAll = () => {
    allActiveStreams.value.forEach(s => {
        controlPlayerAudio(s.video_id, false);
    });
    Object.keys(players).forEach(id => {
        controlPlayerAudio(id, false);
    });
    activeAudioVideoId.value = null;
};

// Watch for layout changes to automatically route audio & high-quality to Lead Unit in Focus Mode
watch(selectedLayout, (newLayout) => {
    if (newLayout === 'focus' && primaryFocusedStream.value) {
        activeAudioVideoId.value = primaryFocusedStream.value.video_id;
        nextTick(() => {
            setTimeout(() => {
                controlPlayerAudio(primaryFocusedStream.value.video_id, true);
                applyQualityToPlayer(primaryFocusedStream.value.video_id, true);
            }, 350);
        });
    } else {
        nextTick(() => {
            setTimeout(() => {
                updateAllStreamQualities();
            }, 350);
        });
    }
});

// Watch for visible streams changes or layout changes
watch([visibleStreams, selectedLayout, isDataSaverEnabled], () => {
    nextTick(() => {
        setTimeout(() => {
            initializeAllPlayers();
            updateAllStreamQualities();
        }, 200);
    });
}, { deep: true });

onMounted(() => {
    loadYouTubeAPI();
});

// Layout Grid CSS Class Computation
const layoutGridClass = computed(() => {
    if (selectedLayout.value === 'grid-2x2') {
        return 'grid grid-cols-1 md:grid-cols-2 gap-3.5';
    }
    if (selectedLayout.value === 'grid-3x3') {
        return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5';
    }
    if (selectedLayout.value === 'grid-4x4') {
        return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3';
    }
    if (selectedLayout.value === 'focus') {
        return '';
    }
    
    // Auto mode
    const count = visibleStreams.value.length;
    if (count <= 1) return 'grid grid-cols-1 gap-4';
    if (count === 2) return 'grid grid-cols-1 md:grid-cols-2 gap-4';
    if (count <= 4) return 'grid grid-cols-1 md:grid-cols-2 gap-3.5';
    if (count <= 6) return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5';
    return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5';
});

// Prioritize Personal Streams in Focus Mode (Personal units appear first)
const sortedFocusStreams = computed(() => {
    const list = [...visibleStreams.value];
    return list.sort((a, b) => {
        const aIsPersonal = isPersonalStream(a.video_id);
        const bIsPersonal = isPersonalStream(b.video_id);
        if (aIsPersonal && !bIsPersonal) return -1;
        if (!aIsPersonal && bIsPersonal) return 1;
        if (aIsPersonal && bIsPersonal) {
            const aIdx = personalVideoIds.value.indexOf(a.video_id);
            const bIdx = personalVideoIds.value.indexOf(b.video_id);
            if (aIdx !== -1 && bIdx !== -1) return aIdx - bIdx;
            if (aIdx !== -1) return -1;
            if (bIdx !== -1) return 1;
        }
        return 0;
    });
});

// Focus mode stream selector
const primaryFocusedStream = computed(() => {
    if (!sortedFocusStreams.value.length) return null;
    if (focusedStreamId.value) {
        const found = sortedFocusStreams.value.find(s => s.video_id === focusedStreamId.value);
        if (found) return found;
    }
    return sortedFocusStreams.value[0];
});

const secondaryStreams = computed(() => {
    if (!primaryFocusedStream.value) return [];
    return sortedFocusStreams.value.filter(s => s.video_id !== primaryFocusedStream.value.video_id);
});

// Swap or set focus stream (Safe re-target with Auto-Audio Follow & Auto-HD)
const setFocusStream = (videoId) => {
    // 1. Mute all other streams and downgrade quality
    allActiveStreams.value.forEach(s => {
        if (s.video_id !== videoId) {
            controlPlayerAudio(s.video_id, false);
            applyQualityToPlayer(s.video_id, false);
        }
    });
    Object.keys(players).forEach(id => {
        if (id !== videoId) {
            controlPlayerAudio(id, false);
            applyQualityToPlayer(id, false);
        }
    });

    // 2. Automatically set the newly focused unit as the active audio channel!
    activeAudioVideoId.value = videoId;

    // 3. Clear out player JS handles safely
    delete players[videoId];
    if (primaryFocusedStream.value) {
        delete players[primaryFocusedStream.value.video_id];
    }

    // 4. Update the focused stream ID
    focusedStreamId.value = videoId;

    // 5. Remove from sidebar active previews if present
    const prevIdx = activePreviewVideoIds.value.indexOf(videoId);
    if (prevIdx !== -1) {
        activePreviewVideoIds.value.splice(prevIdx, 1);
    }

    // 6. Re-initialize player after Vue mounts the fresh iframe, auto-unmute & set HD quality
    nextTick(() => {
        setTimeout(() => {
            initializePlayer(videoId);
            controlPlayerAudio(videoId, true);
            applyQualityToPlayer(videoId, true);
        }, 200);

        // Secondary confirmation once iframe document completes handshake
        setTimeout(() => {
            if (activeAudioVideoId.value === videoId) {
                controlPlayerAudio(videoId, true);
                applyQualityToPlayer(videoId, true);
            }
        }, 650);
    });
};

// Quick Add Feed (Saves to local Personal list)
const handleQuickAddStream = () => {
    if (!quickAddInput.value.urlOrId) return;

    let videoId = quickAddInput.value.urlOrId.trim();
    const match = videoId.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
    if (match) {
        videoId = match[1];
    }

    if (videoId.length !== 11) {
        alert('Invalid YouTube Video ID atau URL. Masukkan 11-digit Video ID atau URL YouTube yang valid.');
        return;
    }

    if (activePersonalStreams.value.length >= MAX_PERSONAL_STREAMS && !customStreams.value.some(s => s.video_id === videoId)) {
        alert(`Maksimal ${MAX_PERSONAL_STREAMS} video aktif pada kategori Personal / Custom! Hapus salah satu video terlebih dahulu.`);
        return;
    }

    const newStream = {
        id: 'custom-' + Date.now(),
        video_id: videoId,
        title: quickAddInput.value.officerName + ' - Tactical Live Feed',
        thumbnail: `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
        status: 'LIVE',
        incident_code: '10-8 Tactical Add',
        viewers_count: 0,
        live_chat_url: `https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${window.location.hostname}`,
        officer: {
            id: 999,
            channel_id: 'custom-' + Date.now(),
            handle: '@TacticalFeed',
            streamer_name: 'Tactical Dispatch Feed',
            officer_name: quickAddInput.value.officerName,
            callsign: quickAddInput.value.callsign || 'TAC-01',
            badge_number: '#999',
            department: quickAddInput.value.department,
            rank: 'Tactical Unit',
            patrol_zone: quickAddInput.value.patrolZone || 'Tactical Sector',
            avatar_url: null,
        }
    };

    customStreams.value.push(newStream);
    if (!personalVideoIds.value.includes(videoId)) {
        personalVideoIds.value.push(videoId);
    }
    savePersonalStreamsToStorage();
    fetchFullStreamDetails(videoId);

    closeRightDrawer();
    quickAddInput.value = {
        urlOrId: '',
        officerName: 'External Unit',
        callsign: 'TAC-UNIT',
        department: 'LSPD',
        patrolZone: 'Incident Sector',
    };
};

// Live Hashtag Search Action
const handleSearchLiveStreams = async (searchOverride = null) => {
    const queryToSearch = (typeof searchOverride === 'string' ? searchOverride : liveSearchQuery.value).trim();
    if (!queryToSearch) return;

    if (typeof searchOverride === 'string') {
        liveSearchQuery.value = searchOverride;
    }

    isLiveSearching.value = true;
    liveSearchError.value = '';
    liveSearchResults.value = [];

    try {
        const response = await fetch('/api/v1/search-live', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ q: queryToSearch }),
        });

        const data = await response.json();
        if (data.status === 'success') {
            liveSearchResults.value = data.data || [];
            if (liveSearchResults.value.length === 0) {
                liveSearchError.value = `Tidak ada stream YouTube yang sedang LIVE untuk "${queryToSearch}". Coba kata kunci atau hashtag lain.`;
            }
        } else {
            liveSearchError.value = data.message || 'Gagal mencari live stream YouTube.';
        }
    } catch (err) {
        liveSearchError.value = 'Terjadi kendala jaringan saat mencari live stream.';
    } finally {
        isLiveSearching.value = false;
    }
};

const handleAddLiveStreamToPersonal = (streamItem) => {
    const videoId = streamItem.video_id;
    if (!videoId) return;

    if (activePersonalStreams.value.length >= MAX_PERSONAL_STREAMS && !isPersonalStream(videoId)) {
        alert(`Maksimal ${MAX_PERSONAL_STREAMS} video aktif untuk kategori Personal! Hapus salah satu video terlebih dahulu.`);
        return;
    }

    const existingCustomIdx = customStreams.value.findIndex(s => s.video_id === videoId);
    if (existingCustomIdx === -1) {
        const newStream = {
            id: 'search-' + Date.now() + '-' + Math.random().toString(36).substring(2, 6),
            video_id: videoId,
            title: streamItem.title,
            thumbnail: streamItem.thumbnail_url || `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
            status: 'LIVE',
            incident_code: 'Target / Gang Feed',
            description: streamItem.description || '',
            viewers_count: streamItem.viewers_count || 0,
            viewers: streamItem.viewers || 'Live',
            live_chat_url: `https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${chatEmbedDomain.value}`,
            officer: {
                id: 9999,
                channel_id: 'target-' + videoId,
                handle: streamItem.channel_name ? ('@' + streamItem.channel_name.replace(/\s+/g, '')) : '@Target',
                streamer_name: streamItem.channel_name || 'Gang / Target Streamer',
                officer_name: streamItem.channel_name || 'Target Unit',
                callsign: 'TARGET',
                badge_number: '#GANG',
                department: 'PERSONAL',
                rank: 'Opponent / Gang',
                patrol_zone: 'Target Sector',
                avatar_url: null,
            }
        };
        customStreams.value.push(newStream);
    } else {
        customStreams.value[existingCustomIdx].title = streamItem.title;
        customStreams.value[existingCustomIdx].description = streamItem.description || '';
        customStreams.value[existingCustomIdx].viewers = streamItem.viewers || 'Live';
        customStreams.value[existingCustomIdx].viewers_count = streamItem.viewers_count || 0;
    }

    if (!personalVideoIds.value.includes(videoId)) {
        personalVideoIds.value.push(videoId);
    }
    savePersonalStreamsToStorage();
    fetchFullStreamDetails(videoId);

    liveSearchSuccessNotice.value = `✓ "${streamItem.channel_name}" berhasil ditambahkan ke 📌 Personal!`;
    setTimeout(() => {
        liveSearchSuccessNotice.value = '';
    }, 3500);
};

// Officer Directory State & Filtering
const directorySearch = ref('');
const directoryDeptFilter = ref('ALL'); // 'ALL', 'LIVE_ONLY', 'LSPD', 'BCSO', 'SASP'
const directorySortBy = ref('status'); // 'status', 'subs_desc', 'subs_asc', 'name'

// All Registered Officers Combined (Deduplicated with real-time online status and subscriber data)
const allDirectoryOfficers = computed(() => {
    const list = [];
    const seenHandles = new Set();
    const seenIds = new Set();

    // 1. First add all currently live/active officers
    allActiveStreams.value.forEach(s => {
        if (!s.officer) return;
        const key = s.officer.handle || s.officer.channel_id || s.officer.officer_name;
        if (!seenHandles.has(key)) {
            seenHandles.add(key);
            if (s.officer.id) seenIds.add(s.officer.id);
            list.push({
                id: s.officer.id || `live-${s.video_id}`,
                channel_id: s.officer.channel_id,
                handle: s.officer.handle || '',
                streamer_name: s.officer.streamer_name || '',
                officer_name: s.officer.officer_name || '',
                callsign: s.officer.callsign || '',
                department: s.officer.department || 'LSPD',
                rank: s.officer.rank || 'Officer',
                badge_number: s.officer.badge_number || '#000',
                patrol_zone: s.officer.patrol_zone || '',
                avatar_url: s.officer.avatar_url || null,
                subscriber_count: s.officer.subscriber_count || 0,
                is_online: true,
                live_stream: s,
            });
        }
    });

    // 2. Then add all offline officers from props
    offlineOfficers.value.forEach(o => {
        const key = o.handle || o.channel_id || o.officer_name;
        if (!seenHandles.has(key) && (!o.id || !seenIds.has(o.id))) {
            seenHandles.add(key);
            list.push({
                id: o.id,
                channel_id: o.channel_id,
                handle: o.handle || '',
                streamer_name: o.streamer_name || '',
                officer_name: o.officer_name || '',
                callsign: o.callsign || '',
                department: o.department || 'LSPD',
                rank: o.rank || 'Officer',
                badge_number: o.badge_number || '#000',
                patrol_zone: o.patrol_zone || '',
                avatar_url: o.avatar_url || null,
                subscriber_count: o.subscriber_count || 0,
                is_online: false,
                live_stream: null,
            });
        }
    });

    // 3. Filter by department or online status
    let filtered = list;
    if (directoryDeptFilter.value === 'LIVE_ONLY') {
        filtered = filtered.filter(o => o.is_online);
    } else if (directoryDeptFilter.value !== 'ALL') {
        filtered = filtered.filter(o => o.department === directoryDeptFilter.value);
    }

    // 4. Filter by search term (name, callsign, handle, badge, streamer, rank, zone)
    if (directorySearch.value.trim()) {
        const q = directorySearch.value.toLowerCase().trim();
        filtered = filtered.filter(o => 
            (o.officer_name && o.officer_name.toLowerCase().includes(q)) ||
            (o.callsign && o.callsign.toLowerCase().includes(q)) ||
            (o.streamer_name && o.streamer_name.toLowerCase().includes(q)) ||
            (o.handle && o.handle.toLowerCase().includes(q)) ||
            (o.badge_number && o.badge_number.toLowerCase().includes(q)) ||
            (o.patrol_zone && o.patrol_zone.toLowerCase().includes(q)) ||
            (o.rank && o.rank.toLowerCase().includes(q))
        );
    }

    // 5. Sorting
    return filtered.sort((a, b) => {
        if (directorySortBy.value === 'status') {
            if (a.is_online && !b.is_online) return -1;
            if (!a.is_online && b.is_online) return 1;
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (directorySortBy.value === 'subs_desc') {
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (directorySortBy.value === 'subs_asc') {
            return (a.subscriber_count || 0) - (b.subscriber_count || 0);
        } else if (directorySortBy.value === 'name') {
            return (a.officer_name || '').localeCompare(b.officer_name || '');
        }
        return 0;
    });
});

// 10-Codes and Radio Operational Protocols State & Data
const radioCodesActiveTab = ref('CODES'); // 'CODES' or 'TAC'
const radioCodesSearch = ref('');

const standardPolice10Codes = [
    { code: '10-4', title: 'Message Received / Roger', meaning: 'Pesan atau instruksi telah diterima dengan jelas dan dipahami sepenuhnya.', category: 'Umum' },
    { code: '10-7', title: 'Out of Service / Off Duty', meaning: 'Petugas keluar dari dinas patroli atau unit dinonaktifkan.', category: 'Status' },
    { code: '10-8', title: 'In Service / On Duty', meaning: 'Petugas aktif bertugas, siap menerima panggilan dispatch dan penugasan.', category: 'Status' },
    { code: '10-20', title: 'Location / Coordinates', meaning: 'Permintaan atau konfirmasi posisi / lokasi spesifik unit saat ini.', category: 'Navigasi' },
    { code: '10-23', title: 'Arrived on Scene', meaning: 'Unit telah tiba di lokasi kejadian / Tempat Kejadian Perkara (TKP).', category: 'Taktis' },
    { code: '10-33', title: 'Officer in Distress / Emergency', meaning: 'Panggilan darurat kritis! Petugas berada di bawah ancaman dan butuh bantuan segera.', category: 'Darurat' },
    { code: '10-50', title: 'Motor Vehicle Accident', meaning: 'Terjadi tabrakan atau kecelakaan lalu lintas kendaraan bermotor.', category: 'Lantas' },
    { code: '10-70', title: 'Foot Pursuit', meaning: 'Pengejaran tersangka dengan berlari / berjalan kaki.', category: 'Pengejaran' },
    { code: '10-80', title: 'High Speed Pursuit', meaning: 'Pengejaran kendaraan berkecepatan tinggi yang berpotensi membahayakan publik.', category: 'Pengejaran' },
    { code: '10-90', title: 'Armed Robbery in Progress', meaning: 'Perampokan bersenjata sedang berlangsung (Toko, Bank, Perhiasan).', category: 'Darurat' },
    { code: '10-99', title: 'Situation Under Control / Code 4', meaning: 'Situasi telah sepenuhnya terkendali, area aman kembali.', category: 'Status' },
    { code: 'Code 1', title: 'Routine Response', meaning: 'Respon rutin normal tanpa sirine dan tanpa strobo, patuhi lalu lintas umum.', category: 'Respon' },
    { code: 'Code 2', title: 'Urgent Silent Run', meaning: 'Respon mendesak dengan lampu strobo tanpa sirine untuk mendekati TKP diam-diam.', category: 'Respon' },
    { code: 'Code 3', title: 'Emergency Full Lights & Sirens', meaning: 'Respon darurat penuh prioritas tertinggi, sirine dan strobo aktif.', category: 'Respon' },
    { code: 'Code 4', title: 'No Further Assistance Needed', meaning: 'Situasi aman, tidak diperlukan penambahan unit tambahan ke lokasi.', category: 'Respon' },
    { code: 'Signal 100', title: 'Radio Silence for High-Risk Event', meaning: 'Seluruh unit dilarang berbicara di radio selain unit komando penanganan situasi.', category: 'Protokol' },
];

const tacChannelGuides = [
    {
        code: 'TAC 1',
        title: 'Dispatch & General Patrol',
        badgeColor: 'blue',
        scope: 'Komunikasi lalu lintas patroli reguler, tilang harian, dan respon panggilan 911 standar.',
        protocol: 'Digunakan oleh seluruh unit patroli LSPD, BCSO, dan SASP saat tidak berada dalam situasi khusus. Pertahankan transmisi singkat dan jelas.'
    },
    {
        code: 'TAC 2',
        title: 'High Speed Vehicle Pursuit (10-80)',
        badgeColor: 'amber',
        scope: 'Pengejaran kendaraan tersangka dan koordinasi formasi interception di jalan raya.',
        protocol: 'Lead unit bertanggung jawab memberikan callout arah (heading, visual, nomor plat, kecepatan). Unit sekunder menyiapkan manuver PIT atau Spikestrip.'
    },
    {
        code: 'TAC 3',
        title: 'Major Robbery & Bank Heist (10-90)',
        badgeColor: 'red',
        scope: 'Penanganan perampokan toko bersenjata, Fleeca Bank, Paleto Bank, Pacific Standard.',
        protocol: 'Hanya unit yang ditugaskan di perimeter dalam dan negosiator yang berkomunikasi. Unit lain menjaga perimeter luar dan jalur pelarian.'
    },
    {
        code: 'TAC 4',
        title: 'Special Weapons & SWAT Tactical Ops',
        badgeColor: 'purple',
        scope: 'Operasi penggerebekan senjata berat, drug lab raid, dan hostile hostage rescue.',
        protocol: 'Di bawah komando langsung SWAT Commander / Tactical Supervisor. Disiplin radio penuh, gunakan formasi breaching standar.'
    },
    {
        code: 'TAC 5',
        title: 'Air Support & Inter-Agency Joint Command',
        badgeColor: 'indigo',
        scope: 'Koordinasi unit udara Air-1/Helikopter, Unit Maritim, dan komando gabungan lintas instansi.',
        protocol: 'Memberikan visual bird-eye view kepada ground units. Koordinasi gabungan LSPD, BCSO, SASP, dan EMS.'
    },
];

const filteredPolice10Codes = computed(() => {
    if (!radioCodesSearch.value.trim()) return standardPolice10Codes;
    const q = radioCodesSearch.value.toLowerCase().trim();
    return standardPolice10Codes.filter(c => 
        c.code.toLowerCase().includes(q) ||
        c.title.toLowerCase().includes(q) ||
        c.meaning.toLowerCase().includes(q) ||
        c.category.toLowerCase().includes(q)
    );
});

// Unified Right Slide-Over Drawer State ('QUICK_ADD', 'FEEDBACK', 'ABOUT', 'DIRECTORY', 'RADIO_CODES', 'ROSTER', or null)
const activeRightDrawer = ref(null);

const openRightDrawer = (drawerName, extra = null) => {
    activeRightDrawer.value = drawerName;
    if (drawerName === 'QUICK_ADD') {
        if (liveSearchResults.value.length === 0) {
            handleSearchLiveStreams('#imeroleplay');
        }
    } else if (drawerName === 'FEEDBACK') {
        feedbackForm.value = {
            type: extra || 'CHANNEL_REQUEST',
            sender_name: '',
            handle_or_url: '',
            officer_name: '',
            callsign: '',
            department: 'LSPD',
            message: '',
        };
        feedbackSuccessToast.value = '';
    } else if (drawerName === 'ROSTER') {
        fetchRosterOfficers();
    }
};

const closeRightDrawer = () => {
    activeRightDrawer.value = null;
    showOfficerFormModal.value = false;
};

// Admin Roster Management State & Methods (Protected for Admin)
const rosterOfficers = ref([]);
const isRosterLoading = ref(false);
const rosterSearch = ref('');
// Visitor Feedback & Channel Request State & Methods (Option 1 Discord Webhook)
const isSubmittingFeedback = ref(false);
const feedbackSuccessToast = ref('');
const feedbackForm = ref({
    type: 'CHANNEL_REQUEST', // 'CHANNEL_REQUEST', 'DATA_CORRECTION', 'BUG_REPORT', 'OTHER'
    sender_name: '',
    handle_or_url: '',
    officer_name: '',
    callsign: '',
    department: 'LSPD',
    message: '',
});

const submitFeedbackForm = async () => {
    isSubmittingFeedback.value = true;
    try {
        const res = await fetch('/api/v1/feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(feedbackForm.value),
        });
        const data = await res.json();
        if (res.ok) {
            feedbackSuccessToast.value = data.message || 'Laporan berhasil dikirim ke Command Center!';
            setTimeout(() => {
                closeRightDrawer();
                feedbackSuccessToast.value = '';
            }, 3000);
        } else {
            alert(data.message || 'Gagal mengirim masukan. Silakan coba lagi.');
        }
    } catch (e) {
        alert('Terjadi kesalahan jaringan saat mengirim laporan.');
    } finally {
        isSubmittingFeedback.value = false;
    }
};




</script>

<template>
    <Head title="IME RP - Police Command Center & Tactical Multiview" />

    <div class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased">
        
        <!-- Tactical Header Bar -->
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-4 py-2 flex items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Area: Branding & Standalone Page Navigation Links -->
            <div class="flex items-center space-x-3 shrink-0">
                <!-- Branding: IME Roleplay Police Division -->
                <div class="flex items-center space-x-2.5 shrink-0">
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 shadow-inner p-1 overflow-hidden">
                        <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-black tracking-wider text-blue-400 uppercase leading-tight">IME ROLEPLAY</span>
                        <span class="text-[10px] font-bold tracking-wide text-slate-300 uppercase leading-tight">POLICE DIVISION</span>
                    </div>
                </div>

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
                    
                    <!-- Perlu dilakukan penyesuaian tampilan untuk radio-codes, about, dan feedback -->
                    <!-- <Link 
                        href="/radio-codes"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="10-Codes & Tactical Radio Channels (TAC 1-10)"
                    >
                        10-Codes & Radio
                    </Link>

                    <Link 
                        href="/about"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="About Police Command Center"
                    >
                        About Platform
                    </Link> -->

                    <Link 
                        href="/feedback"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-sky-300 hover:bg-sky-950/40 transition"
                        title="Channel Requests & System Feedback"
                    >
                        Feedback & Reports
                    </Link>
                </nav>
            </div>

            <!-- Right Area: In-Page Player & Stream Actions (Toolbar Group) -->
            <div class="flex items-center space-x-2">
                
                <!-- Label Badge for Actions Group -->
                <span class="hidden xl:inline-block text-[10px] font-mono text-slate-500 uppercase tracking-wider font-semibold mr-1">Player Actions:</span>

                <!-- 1. Mode Switcher (Saver vs Play All) -->
                <div class="flex items-center bg-slate-950/90 rounded-lg p-0.5 border border-slate-800">
                    <button 
                        @click="enableDataSaver" 
                        :class="isDataSaverEnabled ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2 py-1 text-xs rounded transition flex items-center gap-1.5"
                        title="Mode Saver: Hold video playback to save bandwidth"
                    >
                        <img :src="iconSaver" class="w-3.5 h-3.5 invert" alt="Saver" />
                        <span class="hidden sm:inline">Saver</span>
                    </button>
                    <button 
                        @click="disableDataSaverAndPlayAll" 
                        :class="!isDataSaverEnabled ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2 py-1 text-xs rounded transition flex items-center gap-1.5"
                        title="Play All: Play all video feeds simultaneously"
                    >
                        <img :src="iconPlayAll" class="w-3 h-3 invert" alt="Play All" />
                        <span class="hidden sm:inline">Play All</span>
                    </button>
                </div>

                <!-- 2. Manual Sync Feeds Button -->
                <button 
                    @click="triggerManualSync" 
                    :disabled="isSyncingFeeds"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700/80 transition flex items-center gap-1.5 disabled:opacity-50"
                    title="Resynchronize live feeds from YouTube"
                >
                    <img :src="iconRefresh" class="w-3.5 h-3.5 invert opacity-90" :class="{ 'animate-spin': isSyncingFeeds }" alt="Sync" />
                    <span class="hidden sm:inline">{{ isSyncingFeeds ? 'Syncing...' : 'Sync' }}</span>
                </button>

                <!-- 3. Quick Feed Button (In-Page Drawer Action with Emerald Accent) -->
                <button 
                    @click="openRightDrawer('QUICK_ADD')"
                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-950/80 hover:bg-emerald-900/90 text-emerald-300 border border-emerald-500/40 transition flex items-center gap-1.5 shadow-sm shadow-emerald-950/50"
                    title="Add External YouTube URL to CCTV Wall (In-Page Modal)"
                >
                    <img :src="iconQuickAdd" class="w-3.5 h-3.5 invert opacity-90" alt="Quick Feed" />
                    <span class="hidden sm:inline">Quick Feed</span>
                </button>

                <!-- 4. Fullscreen Button -->
                <button 
                    @click="toggleBrowserFullscreen"
                    :class="isFullscreen ? 'bg-blue-600 text-white shadow-md shadow-blue-500/40 border-blue-400' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 border-slate-700/80'"
                    class="px-2.5 py-1 text-xs font-bold rounded-lg border transition flex items-center gap-1.5"
                    title="Toggle Mode Fullscreen CCTV Wall"
                >
                    <img :src="isFullscreen ? iconExitFullscreen : iconFullscreen" class="w-3.5 h-3.5 invert opacity-90" alt="Fullscreen" />
                    <span class="hidden md:inline">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
                </button>

                <!-- 5. Admin Hub & Logout (When Authenticated) -->
                <div v-if="$page.props.auth?.user" class="flex items-center space-x-1 bg-cyan-950/40 p-0.5 rounded-lg border border-cyan-500/50">
                    <Link 
                        href="/admin/officers"
                        class="px-2.5 py-1 text-xs font-bold rounded bg-cyan-600 hover:bg-cyan-500 text-white shadow-md shadow-cyan-600/30 transition flex items-center gap-1.5"
                        title="Dispatcher Admin Hub"
                    >
                        <span>Admin Hub</span>
                    </Link>
                    <button 
                        @click="handleAdminLogout"
                        class="px-2 py-1 text-xs font-semibold rounded bg-slate-900 hover:bg-red-900/50 text-red-400 hover:text-red-200 border border-slate-700 transition flex items-center gap-1"
                        title="Logout Admin"
                    >
                        <img :src="iconLogout" class="w-3.5 h-3.5 invert opacity-80" alt="Logout" />
                    </button>
                </div>

            </div>
        </header>

        <!-- Department Filter Toolbar & Search / Grid Controls -->
        <div class="bg-[#090f1a] border-b border-slate-800/80 px-4 py-2 flex flex-wrap items-center justify-between gap-2.5">
            
            <!-- Department Tabs -->
            <div class="flex items-center space-x-1.5 flex-wrap md:flex-nowrap py-0.5 max-w-full relative z-30">
                <button 
                    v-for="dept in departments" 
                    :key="dept.id"
                    @click="selectedDepartment = dept.id"
                    :class="[
                        'px-3 py-1.5 text-xs rounded-full border transition flex items-center space-x-1.5 whitespace-nowrap',
                        selectedDepartment === dept.id 
                            ? (dept.id === 'PERSONAL' 
                                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30 border-purple-400' 
                                : (dept.isTac 
                                    ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400 ring-1 ring-amber-400' 
                                    : 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400'))
                            : (dept.id === 'PERSONAL' 
                                ? 'bg-purple-950/40 text-purple-300 hover:bg-purple-900/50 border-purple-500/40' 
                                : (dept.isTac 
                                    ? (getTacUnitCount(dept.id) > 0 
                                        ? 'bg-amber-950/50 text-amber-300 hover:bg-amber-900/60 border-amber-500/50 shadow-sm shadow-amber-500/10 font-semibold' 
                                        : 'bg-slate-900/80 text-slate-400 hover:bg-slate-800 border-slate-800 opacity-70 hover:opacity-100')
                                    : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'))
                    ]"
                >
                    <img v-if="dept.isSvg" :src="dept.icon" class="w-4 h-4 inline-block object-contain brightness-0 invert opacity-90" alt="" />
                    <span v-else>{{ dept.icon }}</span>
                    <span>{{ dept.name }}</span>
                    
                    <!-- Personal count badge -->
                    <span v-if="dept.id === 'PERSONAL'" class="text-[10px] px-1.5 py-0.2 bg-black/50 rounded-full font-mono font-bold text-purple-200 border border-purple-400/30">
                        {{ totalPersonalCount }}/6
                    </span>
                    <!-- TAC count badge -->
                    <span v-else-if="dept.isTac" class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold"
                        :class="getTacUnitCount(dept.id) > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30' : 'bg-black/40 text-slate-500'"
                    >
                        {{ getTacUnitCount(dept.id) }}
                    </span>
                    <!-- Standard dept count -->
                    <span v-else-if="dept.id !== 'ALL'" class="text-[10px] px-1 py-0.2 bg-black/40 rounded-full font-mono">
                        {{ allActiveStreams.filter(s => s.officer?.department === dept.id || (dept.id === 'SAPR' && (s.officer?.department === 'SAPR' || s.officer?.department === 'PARK RANGER'))).length }}
                    </span>
                </button>

                <!-- More TAC Dropdown Selector (TAC 4 to TAC 10) -->
                <div class="relative inline-block text-left shrink-0">
                    <button
                        @click.stop="isMoreTacOpen = !isMoreTacOpen"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-full border transition flex items-center space-x-1.5 whitespace-nowrap font-medium',
                            dropdownTacDepartments.some(d => d.id === selectedDepartment)
                                ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400 ring-1 ring-amber-400'
                                : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'
                        ]"
                        :title="dropdownTacDepartments.some(d => d.id === selectedDepartment) ? `Kanal Terpilih: ${selectedDepartment.replace('_', ' ')}` : 'Kanal TAC Tambahan (TAC 4 - 10)'"
                    >
                        <img :src="iconRadio" class="w-4 h-4 inline-block object-contain brightness-0 invert opacity-90" alt="" />
                        <span>{{ dropdownTacDepartments.some(d => d.id === selectedDepartment) ? selectedDepartment.replace('_', ' ') : 'More TAC' }}</span>
                        <span class="text-[10px] text-amber-400/80">▾</span>
                    </button>

                    <!-- Dropdown Menu Popover -->
                    <div
                        v-if="isMoreTacOpen"
                        class="absolute left-0 top-full mt-1.5 z-50 bg-slate-950/95 border border-amber-500/50 rounded-xl p-2 shadow-2xl backdrop-blur-xl text-xs w-48 animate-in fade-in zoom-in-95 font-sans"
                        @click.stop
                    >
                        <div class="px-2 py-1 mb-1 border-b border-slate-800/80 flex items-center justify-between text-[11px] font-mono font-bold text-amber-400">
                            <span>RADIO TAC (4 – 10)</span>
                            <button @click="isMoreTacOpen = false" class="text-slate-400 hover:text-white text-[10px]">✕</button>
                        </div>
                        <div class="space-y-1 max-h-60 overflow-y-auto">
                            <button
                                v-for="tac in dropdownTacDepartments"
                                :key="tac.id"
                                @click="selectedDepartment = tac.id; isMoreTacOpen = false"
                                :class="[
                                    'w-full px-2.5 py-1.5 rounded-lg flex items-center justify-between transition text-xs font-mono font-bold',
                                    selectedDepartment === tac.id
                                        ? 'bg-amber-600 text-white shadow-md shadow-amber-600/30 border border-amber-400'
                                        : (getTacUnitCount(tac.id) > 0
                                            ? 'bg-amber-950/40 text-amber-300 hover:bg-amber-900/60 border border-amber-500/30'
                                            : 'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border border-slate-800/80')
                                ]"
                            >
                                <div class="flex items-center space-x-2">
                                    <img :src="iconRadio" class="w-3.5 h-3.5 brightness-0 invert opacity-90" alt="" />
                                    <span>{{ tac.name }}</span>
                                </div>
                                <span 
                                    class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold"
                                    :class="selectedDepartment === tac.id ? 'bg-black/40 text-white' : (getTacUnitCount(tac.id) > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30' : 'bg-black/40 text-slate-500')"
                                >
                                    {{ getTacUnitCount(tac.id) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Grid Layout Switcher (Side-by-side) -->
            <div class="flex items-center space-x-2 w-full sm:w-auto flex-wrap sm:flex-nowrap">
                <!-- Search Input -->
                <div class="relative flex-1 sm:w-60">
                    <input 
                        v-model="searchFilter" 
                        type="text" 
                        placeholder="Search callsign, badge, officer..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono"
                    />
                    <img :src="iconSearch" class="absolute left-2.5 top-2.5 w-3.5 h-3.5 opacity-50 invert pointer-events-none" alt="Search" />
                </div>

                <!-- Layout Selector (Only visible on Department / CCTV Grid Mode) -->
                <div v-if="selectedDepartment !== 'ALL'" class="flex items-center bg-slate-950/90 rounded-lg p-0.5 border border-slate-800 shrink-0">
                    <button 
                        @click="selectedLayout = 'auto'" 
                        :class="selectedLayout === 'auto' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition"
                        title="Tata Letak Otomatis (Auto-Fit Grid)"
                    >
                        Auto
                    </button>
                    <button 
                        @click="selectedLayout = 'grid-2x2'" 
                        :class="selectedLayout === 'grid-2x2' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition"
                        title="2x2 Quad Patrol Layout"
                    >
                        2x2
                    </button>
                    <button 
                        @click="selectedLayout = 'grid-3x3'" 
                        :class="selectedLayout === 'grid-3x3' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition"
                        title="3x3 Sector Command Layout"
                    >
                        3x3
                    </button>
                    <button 
                        @click="selectedLayout = 'grid-4x4'" 
                        :class="selectedLayout === 'grid-4x4' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition hidden md:inline-block"
                        title="4x4 Tactical Wall Layout"
                    >
                        4x4
                    </button>
                    <button 
                        @click="selectedLayout = 'focus'" 
                        :class="selectedLayout === 'focus' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition flex items-center gap-1.5"
                        title="Focus Priority Lead + Right Sidebar Units"
                    >
                        <img :src="iconFocus" class="w-3.5 h-3.5 invert opacity-90" alt="Focus" />
                        <span>Focus</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-3.5 md:p-4 overflow-y-auto">
            
            <!-- TAB 1: 10-8 ACTIVE LIVE BODYCAM FEEDS -->
            <div v-if="activeTab === '10-8'">
                
                <!-- ========================================================================= -->
                <!-- MODE A: ALL UNITS -> NETFLIX-STYLE POLICE CINEMA & DISCOVERY HUB         -->
                <!-- ========================================================================= -->
                <div v-if="selectedDepartment === 'ALL'" class="space-y-8 pb-12 animate-in fade-in duration-300">

                    <!-- Standby Banner (Only when all streams are off-duty / empty) -->
                    <div v-if="allActiveStreams.length === 0" class="relative rounded-2xl overflow-hidden border border-slate-800/80 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-8 text-center flex flex-col items-center justify-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-1">
                            <img :src="iconAllUnits" class="w-7 h-7 invert opacity-80" alt="" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-100">SELURUH KESATUAN SEDANG 10-7 (OFF-DUTY)</h3>
                        <p class="text-xs text-slate-400 max-w-md">
                            Belum ada siaran langsung patroli atau video rekaman yang termuat. Anda dapat menyinkronkan feed terbaru atau melihat daftar nama petugas di tab 10-7 Roster.
                        </p>
                        <div class="flex items-center gap-2 mt-2">
                            <button 
                                @click="triggerManualSync"
                                :disabled="isSyncingFeeds"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition flex items-center gap-2"
                            >
                                <img :src="iconRefresh" class="w-3.5 h-3.5 invert" :class="{ 'animate-spin': isSyncingFeeds }" />
                                <span>{{ isSyncingFeeds ? 'Menyinkronkan...' : 'Cek Live Sekarang' }}</span>
                            </button>
                            <button 
                                @click="activeTab = '10-7'"
                                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition"
                            >
                                Lihat 10-7 Roster
                            </button>
                        </div>
                    </div>

                    <!-- 2. SEARCH RESULTS SWIMLANE (Only displayed when actively searching) -->
                    <section v-if="searchFilter.trim() !== ''" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Hasil Pencarian: "{{ searchFilter }}"
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-search', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-search', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-search" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in allCatalogStreams" 
                                :key="`search-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'UNIT' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Officer' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 3. TRENDING PATROLS SWIMLANE (Active Live streams only) -->
                    <section v-if="trendingStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Trending Patrols
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-trending', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-trending', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-trending" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in trendingStreams" 
                                :key="`trend-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'UNIT' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Officer' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 3.5. SUPPORT 1K SUBS SWIMLANE (Road to 1,000 Subscribers Community Milestone) -->
                    <section v-if="support1kStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Support 1K Subs
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-support1k', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-support1k', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-support1k" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in support1kStreams" 
                                :key="`sub1k-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    
                                    <!-- Top Badges -->
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'UNIT' }}</span>
                                        </div>
                                    </div>

                                    <!-- Bottom Info: Officer Name & Action Buttons -->
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Officer' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Meta & 1K Milestone Progress -->
                                <div class="mt-2 px-1">
                                    <div class="flex items-center justify-between text-[11px] font-mono mb-1">
                                        <span class="text-slate-200 font-bold flex items-center gap-1.5">
                                            <img :src="iconTarget" class="w-3 h-3 invert opacity-85" alt="" />
                                            <span>{{ (stream.officer?.subscriber_count || 0).toLocaleString('id-ID') }} / 1.000 Subs</span>
                                        </span>
                                        <span class="text-slate-400 text-[10px]">{{ Math.round(((stream.officer?.subscriber_count || 0) / 1000) * 100) }}%</span>
                                    </div>
                                    
                                    <!-- Solid Red Progress Bar -->
                                    <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden border border-slate-800/80 mb-2">
                                        <div 
                                            class="bg-red-600 h-full rounded-full transition-all duration-500 shadow-sm shadow-red-600/30" 
                                            :style="{ width: Math.min(100, Math.max(5, ((stream.officer?.subscriber_count || 0) / 1000) * 100)) + '%' }"
                                        ></div>
                                    </div>

                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span v-if="stream.officer?.rank" class="text-[11px] text-slate-500 font-mono truncate">
                                            {{ stream.officer.rank }}
                                        </span>
                                        <button 
                                            v-if="stream.officer?.channel_id || stream.officer?.handle"
                                            @click.stop="openSubscribePopup(stream.officer?.channel_id || stream.officer?.handle, stream.officer?.officer_name)"
                                            class="px-2 py-0.5 bg-red-600/90 hover:bg-red-600 active:scale-95 text-white text-[10px] font-bold rounded shadow transition flex items-center gap-1 shrink-0 cursor-pointer"
                                            title="Subscribe Channel Petugas"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>Sub</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 4. RECENT PATROL REPLAYS & VODs SWIMLANE (Offline Latest Officer Videos) -->
                    <section v-if="recentReplayStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Rekaman Patroli
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-replays', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-replays', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-replays" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in recentReplayStreams" 
                                :key="`replay-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'POLICE' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Officer' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 5. PERSONAL WATCHLIST SWIMLANE -->
                    <section v-if="activePersonalStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Personal Watchlist
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-personal', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-personal', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-personal" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in activePersonalStreams" 
                                :key="`personal-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'UNIT' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Officer' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition text-purple-400 bg-purple-950/70" title="Hapus dari Personal"><img :src="iconPinMinus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 6. LSPD PATROLS SWIMLANE (Live + Replay VODs) -->
                    <section v-if="lspdCatalogStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Los Santos Police Department
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-lspd', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-lspd', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-lspd" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in lspdCatalogStreams" 
                                :key="`lspd-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'LSPD' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Officer' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 7. BCSO SHERIFFS SWIMLANE (Live + Replay VODs) -->
                    <section v-if="bcsoCatalogStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Blaine County Sheriff's Office
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-bcso', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-bcso', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-bcso" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in bcsoCatalogStreams" 
                                :key="`bcso-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'BCSO' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Deputy' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 8. SASP STATE TROOPERS SWIMLANE (Live + Replay VODs) -->
                    <section v-if="saspCatalogStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                San Andreas State Police
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-sasp', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-sasp', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-sasp" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in saspCatalogStreams" 
                                :key="`sasp-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'SASP' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Trooper' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 8.5. SAPR PARK RANGERS SWIMLANE (Live + Replay VODs) -->
                    <section v-if="saprCatalogStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                San Andreas Park Rangers
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-sapr', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-sapr', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-sapr" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in saprCatalogStreams" 
                                :key="`sapr-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || 'SAPR' }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Ranger' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 9. SPECIAL OPERATIONS SWIMLANE (Live + Replay VODs) -->
                    <section v-if="specialOpsCatalogStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Operasi Khusus
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-specops', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-specops', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-specops" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in specialOpsCatalogStreams" 
                                :key="`spec-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                            <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                            <span>{{ stream.streamed_at || 'REPLAY' }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department }}</span>
                                        </div>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Unit' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 10. TAC SITUATIONAL RADIO SWIMLANE -->
                    <section v-if="tacSituationalStreams.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                Situasi Radio Taktis
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-tac', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-tac', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-tac" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="stream in tacSituationalStreams" 
                                :key="`tac-${stream.video_id}`"
                                class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                                @click="playStreamInFocus(stream)"
                            >
                                <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                                    <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                                    <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>LIVE</span>
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department }}</span>
                                    </div>
                                    <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                        <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || 'Unit' }}</span>
                                        <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                            <button @click.stop="togglePersonalStream(stream.video_id)" class="p-1 rounded transition" :class="isPersonalStream(stream.video_id) ? 'text-purple-400 bg-purple-950/70' : 'text-slate-400 hover:text-white'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin'"><img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" /></button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 px-1">
                                    <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                                    <div v-if="stream.officer?.rank" class="text-[11px] text-slate-500 mt-1 font-mono truncate">
                                        {{ stream.officer.rank }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 10. 10-7 OFFLINE ROSTER SWIMLANE -->
                    <section v-if="filteredOfflineOfficers.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                                10-7 Officer Roster
                            </h3>
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <button 
                                    @click="scrollRow('row-offline', 'left')" 
                                    class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kiri"
                                >
                                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="scrollRow('row-offline', 'right')" 
                                    class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" 
                                    title="Geser Kanan"
                                >
                                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div id="row-offline" class="flex gap-3.5 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                            <div 
                                v-for="officer in filteredOfflineOfficers" 
                                :key="`off-${officer.channel_id}`"
                                class="w-[200px] sm:w-[220px] shrink-0 snap-start bg-[#0b121e]/90 hover:bg-[#0f1828] border border-slate-800/80 hover:border-slate-700 rounded-xl p-3 transition flex flex-col justify-between"
                            >
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span class="px-1.5 py-0.5 text-[9px] font-black rounded border" :class="getDeptBadgeClass(officer.department)">
                                            {{ officer.department }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-mono">10-7 OFFLINE</span>
                                    </div>
                                    <h5 class="text-xs font-bold text-slate-100 truncate">{{ officer.officer_name }}</h5>
                                    <div v-if="officer.rank" class="text-[11px] text-slate-400 font-mono truncate mt-0.5">{{ officer.rank }}</div>
                                </div>

                                <div class="mt-3 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                                    <span class="text-slate-500 font-mono text-[9px]">OFF-DUTY</span>
                                    <div class="flex items-center space-x-1">
                                        <button 
                                            @click="togglePersonalStream(officer.channel_id || officer.handle)"
                                            class="p-1 rounded transition"
                                            :class="isPersonalStream(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/70 border border-purple-500/50' : 'text-slate-400 hover:text-purple-300 hover:bg-slate-800'"
                                            :title="isPersonalStream(officer.channel_id || officer.handle) ? 'Hapus' : 'Pin ke Personal'"
                                        >
                                            <img :src="isPersonalStream(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus" class="w-2.5 h-2.5 invert" />
                                        </button>
                                        <button 
                                            @click="openSubscribePopup(officer.channel_id || officer.handle, officer.officer_name)"
                                            class="bg-red-600/90 hover:bg-red-600 text-white font-bold px-1.5 py-0.5 rounded text-[9px] transition"
                                        >
                                            Sub
                                        </button>
                                        <a :href="`https://www.youtube.com/${officer.handle}`" target="_blank" class="text-blue-400 hover:underline flex items-center gap-0.5">
                                            <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-70" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <!-- ========================================================================= -->
                <!-- MODE B: SPECIFIC DEPARTMENTS / TAC / PERSONAL -> TACTICAL CCTV GRID       -->
                <!-- ========================================================================= -->
                <template v-else>
                    <div v-if="isTacDepartment(selectedDepartment)" class="mb-4 space-y-2.5">
                        <div class="bg-gradient-to-r from-amber-950/70 via-slate-900/95 to-slate-950 border border-amber-500/40 rounded-xl p-3 shadow-xl backdrop-blur flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-lg bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0">
                                    <img :src="iconRadio" class="w-5 h-5 brightness-0 invert opacity-90" alt="" />
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-sm font-bold text-amber-300 font-mono tracking-wide uppercase">
                                            KANAL RADIO TAKTIS: {{ selectedDepartment.replace('_', ' ') }}
                                        </h3>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                            {{ visibleStreams.length }} UNIT TERHUBUNG
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-0.5 font-mono">
                                        Kanal radio taktis aktif untuk pemantauan POV bersama.
                                    </p>
                                </div>
                            </div>

                            <!-- Timer Countdown -->
                            <div class="flex items-center space-x-2">
                                <div class="flex items-center space-x-1.5 bg-black/60 border border-amber-500/30 px-3 py-1.5 rounded-lg font-mono">
                                    <img :src="iconClock" class="w-3.5 h-3.5 brightness-0 invert opacity-80" alt="" />
                                    <span class="text-[10px] text-slate-400 uppercase">Sisa Waktu:</span>
                                    <span class="text-xs font-bold" :class="getTacRemainingSeconds(selectedDepartment) <= 60 ? 'text-red-400 animate-pulse' : 'text-amber-300'">
                                        {{ formatRemainingTime(getTacRemainingSeconds(selectedDepartment)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Near Expiration Confirmation Prompt Alert (Active ONLY in last 1 minute / 60 seconds) -->
                        <div 
                            v-if="getTacRemainingSeconds(selectedDepartment) > 0 && getTacRemainingSeconds(selectedDepartment) <= 60"
                            class="bg-red-950/95 border-2 border-red-500 rounded-xl p-3.5 shadow-2xl backdrop-blur flex flex-wrap items-center justify-between gap-3 animate-pulse"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-red-500/30 border border-red-400 flex items-center justify-center text-red-300 text-base font-bold shrink-0">
                                    ⚠️
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-red-200 font-mono tracking-wide uppercase">
                                        KONFIRMASI SITUASI: WAKTU {{ selectedDepartment.replace('_', ' ') }} TERSISA {{ formatRemainingTime(getTacRemainingSeconds(selectedDepartment)) }}!
                                    </h4>
                                    <p class="text-[11px] text-red-300/80 mt-0.5">
                                        Apakah kanal radio ini masih aktif digunakan, atau telah selesai?
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="extendTacTimer(selectedDepartment, 20)"
                                    class="px-3.5 py-1.5 bg-red-600 hover:bg-red-500 text-white font-bold text-xs rounded-lg shadow-lg transition flex items-center gap-1.5 font-mono transform hover:scale-105"
                                >
                                    <span>🔥 Ya, Lanjutkan (+20 Menit)</span>
                                </button>
                                <button 
                                    @click="disbandTacChannel(selectedDepartment)"
                                    class="px-3 py-1.5 bg-black/60 hover:bg-black/90 text-slate-200 font-bold text-xs rounded-lg border border-slate-600 transition flex items-center gap-1.5 font-mono"
                                >
                                    <span>✓ Situasi Selesai (Bubarkan)</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- FOCUS MODE VIEW (Primary Large Video on Left + Right Support Column with Collapsible Live Chat) -->
                    <div v-if="selectedLayout === 'focus'" class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                        
                        <!-- LEFT COLUMN: PRIMARY LARGE FEATURED FEED (Takes 8/12 or 9/12 cols) -->
                        <div v-if="primaryFocusedStream" class="lg:col-span-8 xl:col-span-9 flex flex-col gap-2">
                            <div class="bg-slate-950 rounded-xl overflow-hidden border border-slate-800 shadow-2xl relative">
                            
                            <!-- Large Stream HUD Top Bar -->
                            <div class="bg-slate-900/95 px-4 py-2.5 flex items-center justify-between border-b border-slate-800">
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <span class="px-2.5 py-0.5 text-xs font-black rounded border tracking-wider shrink-0" :class="getDeptBadgeClass(primaryFocusedStream.officer?.department)">
                                        [{{ primaryFocusedStream.officer?.department }}] {{ primaryFocusedStream.officer?.callsign }}
                                    </span>
                                    <div class="truncate">
                                        <span class="text-sm font-bold text-slate-100 mr-2">{{ primaryFocusedStream.officer?.officer_name }}</span>
                                        <span class="text-xs text-slate-400 font-mono">({{ primaryFocusedStream.officer?.rank }})</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 shrink-0">
                                    <!-- Pin / Personal Toggle Button -->
                                    <button 
                                        @click="togglePersonalStream(primaryFocusedStream.video_id)" 
                                        :class="isPersonalStream(primaryFocusedStream.video_id) ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 border-purple-400' : 'bg-slate-800 text-slate-300 hover:text-purple-300 hover:bg-slate-700 border-slate-700'"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg transition flex items-center gap-1.5 font-mono border"
                                        :title="isPersonalStream(primaryFocusedStream.video_id) ? 'Hapus dari Personal' : 'Tambah ke Personal Watchlist (Maks 6)'"
                                    >
                                        <img :src="isPersonalStream(primaryFocusedStream.video_id) ? iconPinMinus : iconPinPlus" class="w-3.5 h-3.5 invert" alt="" />
                                        <span class="hidden sm:inline">Personal</span>
                                    </button>

                                    <!-- 1-Click TAC Radio Selector -->
                                    <div class="relative">
                                        <button 
                                            @click.stop="activeTacPopoverVideoId = activeTacPopoverVideoId === primaryFocusedStream.video_id ? null : primaryFocusedStream.video_id" 
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition flex items-center gap-1.5 font-mono border"
                                            :class="getStreamTac(primaryFocusedStream.video_id) ? 'text-amber-300 bg-amber-950/80 border-amber-500/60 shadow-sm shadow-amber-500/20' : 'text-slate-400 hover:text-amber-300 bg-slate-800 border-slate-700'"
                                            :title="getStreamTac(primaryFocusedStream.video_id) ? `Terhubung ke ${getStreamTac(primaryFocusedStream.video_id).replace('_', ' ')}` : 'Hubungkan ke Tactical Radio TAC 1–5'"
                                        >
                                            <img :src="iconRadio" class="w-3.5 h-3.5 brightness-0 invert opacity-90" alt="" />
                                            <span>{{ getStreamTac(primaryFocusedStream.video_id) ? getStreamTac(primaryFocusedStream.video_id).replace('_', ' ') : 'TAC' }}</span>
                                        </button>

                                        <!-- TAC Popover Menu (Opens downwards) -->
                                        <div 
                                            v-if="activeTacPopoverVideoId === primaryFocusedStream.video_id"
                                            class="absolute right-0 top-full mt-1.5 z-50 bg-slate-950/95 border border-amber-500/60 rounded-xl p-2.5 shadow-2xl backdrop-blur-xl text-xs w-52 animate-in fade-in zoom-in-95 font-sans"
                                            @click.stop
                                        >
                                            <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-800">
                                                <span class="flex items-center gap-1 text-[11px] font-mono font-bold text-amber-400">
                                                    <img :src="iconRadio" class="w-3.5 h-3.5 brightness-0 invert opacity-90" alt="" />
                                                    PILIH RADIO TAC:
                                                </span>
                                                <button 
                                                    @click.stop="activeTacPopoverVideoId = null" 
                                                    class="text-slate-400 hover:text-white p-0.5 rounded hover:bg-slate-800 text-[10px]"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                            
                                            <div class="grid grid-cols-5 gap-1.5 mb-2">
                                                <button 
                                                    v-for="t in [1,2,3,4,5,6,7,8,9,10]" 
                                                    :key="t"
                                                    @click.stop="assignStreamToTac(`TAC_${t}`, primaryFocusedStream.video_id); activeTacPopoverVideoId = null"
                                                    class="py-1.5 rounded-lg font-mono text-center font-bold text-xs transition border flex flex-col items-center justify-center gap-0.5"
                                                    :class="getStreamTac(primaryFocusedStream.video_id) === `TAC_${t}` 
                                                        ? 'bg-amber-500 text-black border-amber-300 shadow-lg shadow-amber-500/30' 
                                                        : 'bg-slate-900 text-slate-200 hover:bg-amber-950/60 hover:text-amber-300 hover:border-amber-500/50 border-slate-800'"
                                                    :title="`Pindah ke TAC ${t}`"
                                                >
                                                    <span class="text-[8px] text-slate-400 leading-none">TAC</span>
                                                    <span class="leading-none">{{ t }}</span>
                                                </button>
                                            </div>
                                            
                                            <div v-if="getStreamTac(primaryFocusedStream.video_id)" class="pt-1.5 border-t border-slate-800/80">
                                                <button 
                                                    @click.stop="removeStreamFromTac(primaryFocusedStream.video_id); activeTacPopoverVideoId = null"
                                                    class="w-full py-1.5 px-2 text-[10px] rounded-lg bg-red-950/60 text-red-300 hover:bg-red-900/80 border border-red-500/40 text-center transition flex items-center justify-center gap-1 font-mono"
                                                >
                                                    <span>✕ Lepas dari {{ getStreamTac(primaryFocusedStream.video_id).replace('_', ' ') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Audio Toggle Button -->
                                    <button 
                                        @click="toggleAudio(primaryFocusedStream.video_id)" 
                                        :class="activeAudioVideoId === primaryFocusedStream.video_id ? 'bg-emerald-600 text-white shadow-emerald-500/50' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                        class="px-3 py-1 text-xs font-bold rounded transition flex items-center gap-1.5"
                                    >
                                        <img :src="activeAudioVideoId === primaryFocusedStream.video_id ? iconUnmute : iconMute" class="w-3.5 h-3.5 invert" alt="" />
                                        <span>{{ activeAudioVideoId === primaryFocusedStream.video_id ? 'LIVE AUDIO' : 'MUTED' }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Big Video Player Container (With explicit unique key for reliable re-rendering) -->
                            <div class="relative w-full aspect-video bg-black">
                                <iframe
                                    :key="`primary-player-${primaryFocusedStream.video_id}`"
                                    :id="`yt-bodycam-${primaryFocusedStream.video_id}`"
                                    class="w-full h-full border-0 pointer-events-auto"
                                    :src="`https://www.youtube.com/embed/${primaryFocusedStream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=hd1080&origin=${originUrl}`"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                ></iframe>
                            </div>

                            <!-- Stream HUD Bottom Bar -->
                            <div class="bg-[#0b121e] px-4 py-2.5 flex items-center justify-between text-xs text-slate-400 border-t border-slate-800">
                                <div class="flex items-center space-x-3 truncate">
                                    <span class="font-mono text-blue-400 font-semibold truncate">📍 {{ primaryFocusedStream.officer?.patrol_zone || 'Mission Row Sector' }}</span>
                                    <span class="text-slate-700">|</span>
                                    <span class="font-mono text-slate-300 truncate">Badge: {{ primaryFocusedStream.officer?.badge_number || '#000' }}</span>
                                    <span class="text-slate-700 hidden sm:inline">|</span>
                                    <span class="font-mono text-slate-400 truncate hidden sm:inline">Streamer: {{ primaryFocusedStream.officer?.streamer_name }}</span>
                                </div>
                                <div class="flex items-center space-x-2.5 shrink-0">
                                    <!-- 1-Click YouTube Subscribe Popup Button -->
                                    <button 
                                        v-if="primaryFocusedStream.officer?.channel_id || primaryFocusedStream.officer?.handle"
                                        @click="openSubscribePopup(primaryFocusedStream.officer?.channel_id || primaryFocusedStream.officer?.handle, primaryFocusedStream.officer?.officer_name)"
                                        class="bg-red-600 hover:bg-red-500 text-white font-bold px-2.5 py-1 rounded-lg text-xs shadow-md shadow-red-600/30 flex items-center gap-1.5 transition transform hover:scale-105"
                                        title="Subscribe to this officer's channel without leaving page"
                                    >
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                                        </svg>
                                        <span>Subscribe</span>
                                    </button>

                                    <!-- Toggle Live Chat in Right Column -->
                                    <button 
                                        @click="isRightChatOpen = !isRightChatOpen" 
                                        :class="isRightChatOpen ? 'text-amber-400 font-bold bg-amber-950/40 border border-amber-500/30' : 'text-slate-400 hover:text-amber-300'"
                                        class="font-mono text-xs px-2 py-0.5 rounded flex items-center gap-1.5 transition"
                                        title="Toggle Live Chat in Support Column"
                                    >
                                        <img :src="isRightChatOpen ? iconChatRemove : iconChat" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                        <span>{{ isRightChatOpen ? 'Chat Open' : 'Live Chat' }}</span>
                                    </button>
                                    
                                    <a 
                                        :href="`https://www.youtube.com/watch?v=${primaryFocusedStream.video_id}`" 
                                        target="_blank" 
                                        class="p-1.5 hover:text-white text-slate-400 rounded hover:bg-slate-800 transition flex items-center justify-center"
                                        title="Open on YouTube"
                                    >
                                        <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="Open on YouTube" />
                                    </a>
                                </div>
                            </div>

                            <!-- Stream Lore & Description Section -->
                            <div class="bg-[#080d16] px-4 py-2 border-t border-slate-800/80 flex flex-col gap-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-xs font-bold text-slate-200 truncate flex items-center gap-1.5 min-w-0">
                                        <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-70 shrink-0" alt="" />
                                        <span class="truncate" :title="primaryFocusedStream.title">{{ primaryFocusedStream.title }}</span>
                                    </div>
                                    <button 
                                        @click="toggleStreamInfo(primaryFocusedStream.video_id)" 
                                        class="text-[10px] text-slate-300 hover:text-white font-mono bg-slate-900 hover:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-700 flex items-center gap-1 shrink-0 transition"
                                    >
                                        <span>{{ isStreamInfoOpen(primaryFocusedStream.video_id) ? '▲ Tutup Deskripsi' : '▼ Lihat Deskripsi' }}</span>
                                    </button>
                                </div>
                                <div v-if="isStreamInfoOpen(primaryFocusedStream.video_id)" class="bg-slate-950/90 p-3 rounded-xl border border-slate-800 mt-1.5 animate-in fade-in duration-150 shadow-inner">
                                    <div v-if="primaryFocusedStream.description" class="text-[11px] text-slate-300 font-mono whitespace-pre-wrap break-words leading-relaxed max-h-56 overflow-y-auto scrollbar-thin selection:bg-purple-600" v-html="formatDescriptionWithLinks(primaryFocusedStream.description)"></div>
                                    <div v-else class="text-[11px] text-slate-500 font-mono italic">
                                        {{ isFetchingStreamDetail[primaryFocusedStream.video_id] ? 'Memuat deskripsi lengkap...' : 'Tidak ada deskripsi tambahan dari streamer.' }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT COLUMN: COLLAPSIBLE LIVE CHAT AT TOP + SUPPORT UNITS BELOW -->
                    <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-3">
                        
                        <!-- Supporting Units & Chat Header Bar -->
                        <div class="bg-slate-900/90 px-3.5 py-2 rounded-xl border border-slate-800 flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center space-x-2">
                                <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <h3 class="text-xs font-bold text-slate-200 uppercase tracking-wider">
                                    SUPPORT UNITS ({{ secondaryStreams.length }})
                                </h3>
                            </div>
                            
                            <div class="flex items-center space-x-1.5">
                                <!-- Collapsible Live Chat Toggle Button -->
                                <button 
                                    @click="isRightChatOpen = !isRightChatOpen" 
                                    :class="isRightChatOpen ? 'bg-amber-600 text-white shadow-md shadow-amber-500/40 border-amber-400' : 'bg-slate-800 text-slate-300 hover:text-amber-300 hover:bg-slate-700 border-slate-700'"
                                    class="text-[10px] font-mono font-bold px-2 py-0.5 rounded border flex items-center gap-1 transition"
                                    title="Toggle YouTube Live Chat for Lead Stream"
                                >
                                    <img :src="isRightChatOpen ? iconChatRemove : iconChat" class="w-3 h-3 invert opacity-90" alt="" />
                                    <span>{{ isRightChatOpen ? 'HIDE CHAT' : 'LIVE CHAT' }}</span>
                                </button>

                                <!-- Mode Toggle Button -->
                                <button 
                                    @click="toggleGlobalDataSaver" 
                                    :class="isDataSaverEnabled ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/50' : 'bg-blue-950/80 text-blue-300 border-blue-500/50'"
                                    class="text-[10px] font-mono px-2 py-0.5 rounded border flex items-center gap-1 transition font-bold"
                                    :title="isDataSaverEnabled ? 'Click to Play All support feeds' : 'Click to enable Saver Mode'"
                                >
                                    <img :src="isDataSaverEnabled ? iconSaver : iconPlayAll" class="w-3 h-3 invert" alt="" />
                                    <span>{{ isDataSaverEnabled ? 'SAVER' : 'PLAY ALL' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- 1. COLLAPSIBLE LIVE CHAT CONTAINER (With user preferred 520px height) -->
                        <div v-if="isRightChatOpen && primaryFocusedStream" class="bg-slate-950 rounded-xl overflow-hidden border border-amber-500/50 shadow-2xl flex flex-col animate-in fade-in zoom-in-95 duration-200">
                            <div class="bg-slate-900/95 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 text-xs">
                                <div class="flex items-center space-x-1.5 text-amber-300 font-bold truncate">
                                    <img :src="iconChat" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Live Chat: {{ primaryFocusedStream.officer?.officer_name }}</span>
                                </div>
                                <button 
                                    @click="isRightChatOpen = false" 
                                    class="text-slate-400 hover:text-white text-[11px] font-mono bg-slate-800 hover:bg-slate-700 px-1.5 py-0.2 rounded"
                                    title="Close Live Chat"
                                >
                                    ✕ Close
                                </button>
                            </div>
                            <div class="w-full h-[520px] bg-slate-900 relative">
                                <iframe 
                                    :key="`chat-lead-${primaryFocusedStream.video_id}`"
                                    :src="`https://www.youtube.com/live_chat?v=${primaryFocusedStream.video_id}&embed_domain=${chatEmbedDomain}`" 
                                    class="w-full h-full border-0"
                                    allow="autoplay"
                                ></iframe>
                            </div>
                        </div>

                        <!-- 2. SCROLLABLE SUPPORTING UNITS CONTAINER (Positioned Below Chat) -->
                        <div class="space-y-3 max-h-[calc(100vh-210px)] overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                            
                            <div 
                                v-for="stream in secondaryStreams" 
                                :key="`support-card-${stream.video_id}`"
                                :class="activeAudioVideoId === stream.video_id ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-slate-800 hover:border-blue-500/60'"
                                class="bg-slate-950 rounded-xl overflow-hidden border transition shadow-lg relative group flex flex-col"
                            >
                                <!-- Secondary Stream Header -->
                                <div class="bg-slate-900/95 px-2.5 py-1.5 flex items-center justify-between border-b border-slate-800/80 text-xs">
                                    <div class="flex items-center space-x-1.5 truncate">
                                        <span class="px-1.5 py-0.2 text-[10px] font-black rounded border shrink-0" :class="getDeptBadgeClass(stream.officer?.department)">
                                            {{ stream.officer?.department }}
                                        </span>
                                        <span class="font-mono text-[11px] font-bold text-slate-200 truncate">
                                            {{ stream.officer?.callsign }} | {{ stream.officer?.officer_name }}
                                        </span>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex items-center space-x-1 shrink-0">
                                        <!-- TAC Radio Button -->
                                        <div class="relative">
                                            <button 
                                                @click.stop="activeTacPopoverVideoId = activeTacPopoverVideoId === stream.video_id ? null : stream.video_id" 
                                                class="p-1 rounded transition font-mono border"
                                                :class="getStreamTac(stream.video_id) ? 'text-amber-300 bg-amber-950/80 border-amber-500/60' : 'text-slate-400 hover:text-amber-300 bg-slate-800 border-slate-700'"
                                                :title="getStreamTac(stream.video_id) ? `Terhubung ke ${getStreamTac(stream.video_id).replace('_', ' ')}` : 'Hubungkan ke TAC Radio 1–5'"
                                            >
                                                <img :src="iconRadio" class="w-3 h-3 brightness-0 invert opacity-90" alt="" />
                                            </button>

                                            <!-- TAC Popover Menu -->
                                            <div 
                                                v-if="activeTacPopoverVideoId === stream.video_id"
                                                class="absolute right-0 top-full mt-1.5 z-50 bg-slate-950/95 border border-amber-500/60 rounded-xl p-2.5 shadow-2xl backdrop-blur-xl text-xs w-48 animate-in fade-in zoom-in-95 font-sans"
                                                @click.stop
                                            >
                                                <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-800">
                                                    <span class="flex items-center gap-1 text-[11px] font-mono font-bold text-amber-400">
                                                        <img :src="iconRadio" class="w-3.5 h-3.5 brightness-0 invert opacity-90" alt="" />
                                                        RADIO TAC:
                                                    </span>
                                                    <button 
                                                        @click.stop="activeTacPopoverVideoId = null" 
                                                        class="text-slate-400 hover:text-white p-0.5 rounded hover:bg-slate-800 text-[10px]"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                                
                                                <div class="grid grid-cols-5 gap-1 mb-2">
                                                    <button 
                                                        v-for="t in [1,2,3,4,5]" 
                                                        :key="t"
                                                        @click.stop="assignStreamToTac(`TAC_${t}`, stream.video_id); activeTacPopoverVideoId = null"
                                                        class="py-1 rounded font-mono text-center font-bold text-xs transition border"
                                                        :class="getStreamTac(stream.video_id) === `TAC_${t}` 
                                                            ? 'bg-amber-500 text-black border-amber-300 shadow-md' 
                                                            : 'bg-slate-900 text-slate-200 hover:bg-amber-950/60 hover:text-amber-300 border-slate-800'"
                                                        :title="`Pindah ke TAC ${t}`"
                                                    >
                                                        {{ t }}
                                                    </button>
                                                </div>
                                                
                                                <div v-if="getStreamTac(stream.video_id)" class="pt-1.5 border-t border-slate-800/80">
                                                    <button 
                                                        @click.stop="removeStreamFromTac(stream.video_id); activeTacPopoverVideoId = null"
                                                        class="w-full py-1 px-1.5 text-[10px] rounded bg-red-950/60 text-red-300 hover:bg-red-900/80 border border-red-500/40 text-center transition font-mono"
                                                    >
                                                        ✕ Lepas TAC
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Personal Pin Toggle -->
                                        <button 
                                            @click="togglePersonalStream(stream.video_id)" 
                                            :class="isPersonalStream(stream.video_id) ? 'text-purple-300 bg-purple-950/70 border border-purple-500/50' : 'text-slate-400 hover:text-purple-300 bg-slate-800'"
                                            class="p-1 rounded transition font-mono"
                                            :title="isPersonalStream(stream.video_id) ? 'Hapus dari Personal' : 'Tambah ke Personal Watchlist (Maks 6)'"
                                        >
                                            <img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" alt="" />
                                        </button>
                                        <button 
                                            v-if="activePreviewVideoIds.includes(stream.video_id) || !isDataSaverEnabled"
                                            @click="toggleAudio(stream.video_id)" 
                                            :class="activeAudioVideoId === stream.video_id ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                            class="p-1 rounded transition font-mono"
                                            title="Audio Switch"
                                        >
                                            <img :src="activeAudioVideoId === stream.video_id ? iconUnmute : iconMute" class="w-3 h-3 invert" alt="" />
                                        </button>
                                        <button 
                                            @click="setFocusStream(stream.video_id)" 
                                            class="bg-blue-600 hover:bg-blue-500 text-white px-2 py-0.5 rounded text-[10px] font-bold shadow transition flex items-center gap-1"
                                            title="Set as Main Large Focus Video"
                                        >
                                            <img :src="iconFocus" class="w-2.5 h-2.5 invert" alt="" />
                                            <span>Focus</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- VIDEO DISPLAY: Live IFrame OR Lightweight Poster Thumbnail -->
                                <div class="relative w-full aspect-video bg-black overflow-hidden group/thumb">
                                    
                                    <!-- ACTIVE IFRAME -->
                                    <template v-if="activePreviewVideoIds.includes(stream.video_id) || !isDataSaverEnabled">
                                        <iframe
                                            :key="`support-iframe-${stream.video_id}`"
                                            :id="`yt-bodycam-${stream.video_id}`"
                                            class="w-full h-full border-0 pointer-events-auto"
                                            :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                        ></iframe>

                                        <!-- Close Preview Button -->
                                        <button 
                                            v-if="isDataSaverEnabled"
                                            @click="toggleSidebarPreview(stream.video_id)"
                                            class="absolute top-2 right-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-1.5 py-0.5 rounded border border-white/20 z-20"
                                            title="Stop live video and return to thumbnail to save bandwidth"
                                        >
                                            ✕ Close Preview
                                        </button>
                                    </template>

                                    <!-- LIGHTWEIGHT POSTER THUMBNAIL -->
                                    <template v-else>
                                        <img 
                                            :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" 
                                            :alt="stream.title"
                                            class="w-full h-full object-cover opacity-80 group-hover/thumb:opacity-100 transition duration-300"
                                            loading="lazy"
                                        />

                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/60 flex flex-col items-center justify-center p-3">
                                            
                                            <div class="absolute top-2 left-2 flex items-center space-x-1 font-mono text-[9px] text-red-400 bg-red-950/70 px-1.5 py-0.5 rounded border border-red-500/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
                                                <span>10-8 LIVE</span>
                                            </div>

                                            <button 
                                                @click="toggleSidebarPreview(stream.video_id)"
                                                class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow transition flex items-center gap-1 transform hover:scale-105"
                                                title="Play stream"
                                            >
                                                <img :src="iconPlayAll" class="w-3 h-3 invert" alt="" />
                                                <span>Play</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <!-- Mini Footer -->
                                <div class="bg-[#0b121e] px-2.5 py-1 flex items-center justify-between text-[10px] text-slate-400 border-t border-slate-800/80 font-mono">
                                    <span class="truncate text-blue-400">📍 {{ stream.officer?.patrol_zone || 'Patrol' }}</span>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button 
                                            v-if="stream.officer?.channel_id || stream.officer?.handle"
                                            @click="openSubscribePopup(stream.officer?.channel_id || stream.officer?.handle, stream.officer?.officer_name)"
                                            class="text-red-400 hover:text-red-300 hover:bg-red-950/60 px-1 py-0.2 rounded transition flex items-center gap-0.5 font-bold"
                                            title="Subscribe to channel without leaving page"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-red-500 mr-0.5"></span>
                                            <span>Sub</span>
                                        </button>
                                        <a 
                                            :href="`https://www.youtube.com/watch?v=${stream.video_id}`" 
                                            target="_blank" 
                                            class="p-1 hover:text-white text-slate-400 hover:bg-slate-800 rounded transition flex items-center justify-center"
                                            title="Open on YouTube"
                                        >
                                            <img :src="iconExternal" class="w-3 h-3 invert opacity-70 hover:opacity-100" alt="Open on YouTube" />
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- STANDARD GRID VIEWS (Auto, 2x2, 3x3, 4x4) -->
                <div v-else class="space-y-3">
                    
                    <!-- DATA SAVER HELPER BAR (When in Saver Mode) -->
                    <div v-if="isDataSaverEnabled" class="bg-slate-900/90 border border-slate-800/90 px-3.5 py-2.5 rounded-xl flex items-center justify-between flex-wrap gap-2.5 shadow-lg">
                        <div class="flex items-center space-x-2 text-xs font-mono">
                            <span class="text-emerald-400 font-bold flex items-center gap-1.5">
                                <img :src="iconSaver" class="w-3.5 h-3.5 invert" alt="" />
                                <span>SAVER MODE ACTIVE:</span>
                            </span>
                            <span class="text-slate-300">Feeds are in standby. Click Play to view any feed, or switch to Play All.</span>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <button 
                                @click="disableDataSaverAndPlayAll" 
                                class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow"
                            >
                                <img :src="iconPlayAll" class="w-3 h-3 invert" alt="" />
                                <span>Play All Videos</span>
                            </button>
                            <button 
                                v-if="activeGridVideoIds.length > 0" 
                                @click="enableDataSaver" 
                                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg text-xs font-mono transition flex items-center gap-1"
                            >
                                <img :src="iconReset" class="w-3 h-3 invert opacity-70" alt="" />
                                <span>Reset to Saver</span>
                            </button>
                        </div>
                    </div>

                    <!-- GRID CONTAINER -->
                    <div :class="layoutGridClass">
                        <div 
                            v-for="stream in displayedGridStreams" 
                            :key="`grid-card-${stream.video_id}`"
                            :class="activeAudioVideoId === stream.video_id ? 'ring-2 ring-emerald-500 border-emerald-500 shadow-xl shadow-emerald-950/50' : 'border-slate-800 hover:border-blue-500/40'"
                            class="bg-slate-950 rounded-xl overflow-hidden border transition flex flex-col relative group"
                        >
                            <!-- BODYCAM HEADER HUD OVERLAY -->
                            <div class="bg-slate-900/90 backdrop-blur-sm px-3 py-1.5 flex items-center justify-between border-b border-slate-800/80 z-10">
                                <!-- Officer Badge & Callsign -->
                                <div class="flex items-center space-x-2 min-w-0">
                                    <span class="px-1.5 py-0.5 text-[11px] font-black rounded border tracking-wider shrink-0" :class="getDeptBadgeClass(stream.officer?.department)">
                                        {{ stream.officer?.department }} {{ stream.officer?.callsign }}
                                    </span>
                                    <div class="truncate">
                                        <span class="text-xs font-bold text-slate-200 block truncate">{{ stream.officer?.officer_name }}</span>
                                    </div>
                                </div>

                                <!-- Bodycam Actions: Audio Button & Top Personal Pin Button -->
                                <div class="flex items-center space-x-1.5 shrink-0">
                                    <!-- Audio Button (In Live / Active Mode) -->
                                    <button 
                                        v-if="!isDataSaverEnabled || activeGridVideoIds.includes(stream.video_id) || activeAudioVideoId === stream.video_id"
                                        @click="toggleAudio(stream.video_id)" 
                                        :class="activeAudioVideoId === stream.video_id ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/40' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                        class="px-2 py-0.5 text-[11px] rounded transition flex items-center gap-1 font-mono"
                                        :title="activeAudioVideoId === stream.video_id ? 'Mute audio' : 'Unmute audio (auto-mutes all others)'"
                                    >
                                        <img :src="activeAudioVideoId === stream.video_id ? iconUnmute : iconMute" class="w-3 h-3 invert" alt="" />
                                        <span>{{ activeAudioVideoId === stream.video_id ? 'ON' : 'MUTED' }}</span>
                                    </button>

                                    <!-- 1-Click TAC Radio Selector -->
                                    <div class="relative">
                                        <button 
                                            @click.stop="activeTacPopoverVideoId = activeTacPopoverVideoId === stream.video_id ? null : stream.video_id" 
                                            class="px-2 py-0.5 text-[11px] rounded transition flex items-center gap-1 font-mono border"
                                            :class="getStreamTac(stream.video_id) ? 'text-amber-300 bg-amber-950/80 border-amber-500/60 shadow-sm shadow-amber-500/20 font-bold' : 'text-slate-400 hover:text-amber-300 bg-slate-800 border-slate-700'"
                                            :title="getStreamTac(stream.video_id) ? `Terhubung ke ${getStreamTac(stream.video_id).replace('_', ' ')}` : 'Hubungkan ke Tactical Radio TAC 1–5'"
                                        >
                                            <img :src="iconRadio" class="w-3 h-3 brightness-0 invert opacity-90" alt="" />
                                            <span class="text-[10px]">{{ getStreamTac(stream.video_id) ? getStreamTac(stream.video_id).replace('_', ' ') : 'TAC' }}</span>
                                        </button>

                                        <!-- TAC Popover Menu -->
                                        <div 
                                            v-if="activeTacPopoverVideoId === stream.video_id"
                                            class="absolute right-0 top-full mt-1.5 z-50 bg-slate-950/95 border border-amber-500/60 rounded-xl p-2.5 shadow-2xl backdrop-blur-xl text-xs w-52 animate-in fade-in zoom-in-95 font-sans"
                                            @click.stop
                                        >
                                            <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-800">
                                                <span class="flex items-center gap-1 text-[11px] font-mono font-bold text-amber-400">
                                                    <img :src="iconRadio" class="w-3.5 h-3.5 brightness-0 invert opacity-90" alt="" />
                                                    PILIH RADIO TAC:
                                                </span>
                                                <button 
                                                    @click.stop="activeTacPopoverVideoId = null" 
                                                    class="text-slate-400 hover:text-white p-0.5 rounded hover:bg-slate-800 text-[10px]"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                            
                                            <div class="grid grid-cols-5 gap-1.5 mb-2">
                                                <button 
                                                    v-for="t in [1,2,3,4,5,6,7,8,9,10]" 
                                                    :key="t"
                                                    @click.stop="assignStreamToTac(`TAC_${t}`, stream.video_id); activeTacPopoverVideoId = null"
                                                    class="py-1.5 rounded-lg font-mono text-center font-bold text-xs transition border flex flex-col items-center justify-center gap-0.5"
                                                    :class="getStreamTac(stream.video_id) === `TAC_${t}` 
                                                        ? 'bg-amber-500 text-black border-amber-300 shadow-lg shadow-amber-500/30' 
                                                        : 'bg-slate-900 text-slate-200 hover:bg-amber-950/60 hover:text-amber-300 hover:border-amber-500/50 border-slate-800'"
                                                    :title="`Pindah ke TAC ${t}`"
                                                >
                                                    <span class="text-[8px] text-slate-400 leading-none">TAC</span>
                                                    <span class="leading-none">{{ t }}</span>
                                                </button>
                                            </div>
                                            
                                            <div v-if="getStreamTac(stream.video_id)" class="pt-1.5 border-t border-slate-800/80">
                                                <button 
                                                    @click.stop="removeStreamFromTac(stream.video_id); activeTacPopoverVideoId = null"
                                                    class="w-full py-1.5 px-2 text-[10px] rounded-lg bg-red-950/60 text-red-300 hover:bg-red-900/80 border border-red-500/40 text-center transition flex items-center justify-center gap-1 font-mono"
                                                >
                                                    <span>✕ Lepas dari {{ getStreamTac(stream.video_id).replace('_', ' ') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Top Personal Pin Button -->
                                    <button 
                                        @click="togglePersonalStream(stream.video_id)" 
                                        class="px-2 py-0.5 text-[11px] rounded transition flex items-center gap-1 font-mono border"
                                        :class="isPersonalStream(stream.video_id) ? 'text-purple-300 bg-purple-950/70 border-purple-500/50' : 'text-slate-400 hover:text-purple-300 bg-slate-800 border-slate-700'"
                                        :title="isPersonalStream(stream.video_id) ? 'Hapus dari Personal' : 'Tambah ke Personal Watchlist (Maks 6)'"
                                    >
                                        <img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" alt="" />
                                        <span class="text-[10px] hidden sm:inline">Personal</span>
                                    </button>
                                </div>
                            </div>

                            <!-- VIDEO DISPLAY: Live IFrame OR Lightweight Poster Thumbnail -->
                            <div class="relative w-full aspect-video bg-black overflow-hidden flex-1 group/thumb">
                                <template v-if="!isDataSaverEnabled || activeGridVideoIds.includes(stream.video_id) || activeAudioVideoId === stream.video_id">
                                    <iframe
                                        :key="`grid-iframe-${stream.video_id}`"
                                        :id="`yt-bodycam-${stream.video_id}`"
                                        class="w-full h-full border-0 pointer-events-auto"
                                        :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                    ></iframe>

                                    <!-- Close / Stop Feed Button when in Data Saver Mode -->
                                    <button 
                                        v-if="isDataSaverEnabled"
                                        @click="toggleGridStreamPlay(stream.video_id)"
                                        class="absolute top-2 left-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-2 py-0.5 rounded border border-white/20 z-20 font-mono"
                                        title="Close video to save bandwidth"
                                    >
                                        ✕ Stop Feed
                                    </button>
                                </template>

                                <template v-else>
                                    <img 
                                        :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" 
                                        :alt="stream.title"
                                        class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 transition duration-300"
                                        loading="lazy"
                                    />

                                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/70 flex flex-col items-center justify-center p-3">
                                        <div class="absolute top-2 left-2 flex items-center space-x-1 font-mono text-[9px] text-red-400 bg-red-950/80 px-1.5 py-0.5 rounded border border-red-500/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
                                            <span>10-8 LIVE</span>
                                        </div>

                                        <div class="flex items-center space-x-2">
                                            <button 
                                                @click="selectedLayout = 'focus'; setFocusStream(stream.video_id)"
                                                class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg shadow-blue-600/40 transition flex items-center gap-1.5 transform hover:scale-105"
                                                title="Focus as Main Screen"
                                            >
                                                <img :src="iconFocus" class="w-3.5 h-3.5 invert" alt="" />
                                                <span>Focus</span>
                                            </button>
                                            
                                            <button 
                                                @click="toggleGridStreamPlay(stream.video_id)"
                                                class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow transition flex items-center gap-1.5 hover:scale-105"
                                                title="Play this stream"
                                            >
                                                <img :src="iconPlayAll" class="w-3.5 h-3.5 invert" alt="" />
                                                <span>Play</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- BODYCAM FOOTER HUD -->
                            <div class="bg-[#0b121e] px-3 py-1.5 flex items-center justify-between border-t border-slate-800 text-[11px] text-slate-400">
                                <!-- Patrol Sector / Rank -->
                                <div class="flex items-center space-x-2 truncate">
                                    <span class="font-mono text-blue-400 truncate">📍 {{ stream.officer?.patrol_zone || 'Los Santos Sector' }}</span>
                                    <span class="text-slate-600">|</span>
                                    <span class="font-mono text-slate-400 truncate">{{ stream.officer?.badge_number || '#000' }}</span>
                                </div>

                                <!-- Action Icons (Subscribe, Focus, Chat, YT Link) -->
                                <div class="flex items-center space-x-1.5 shrink-0">
                                    <button 
                                        v-if="stream.officer?.channel_id || stream.officer?.handle"
                                        @click="openSubscribePopup(stream.officer?.channel_id || stream.officer?.handle, stream.officer?.officer_name)"
                                        class="p-1 hover:text-red-400 text-slate-400 hover:bg-red-950/40 rounded transition flex items-center gap-0.5 text-[10px] font-bold"
                                        title="Subscribe without leaving page"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-red-500 mr-0.5"></span>
                                        <span class="hidden sm:inline">Sub</span>
                                    </button>
                                    <button 
                                        @click="selectedLayout = 'focus'; setFocusStream(stream.video_id)"
                                        class="p-1.5 hover:text-blue-400 text-slate-400 rounded hover:bg-slate-800 transition"
                                        title="Focus This Stream as Tactical Lead"
                                    >
                                        <img :src="iconFocus" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="Focus" />
                                    </button>
                                    <button 
                                        @click="activeChatVideoId = activeChatVideoId === stream.video_id ? null : stream.video_id"
                                        class="p-1.5 hover:text-amber-400 text-slate-400 rounded hover:bg-slate-800 transition"
                                        title="Toggle YouTube Live Chat Drawer"
                                    >
                                        <img :src="activeChatVideoId === stream.video_id ? iconChatRemove : iconChat" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="Chat" />
                                    </button>
                                    <a 
                                        :href="`https://www.youtube.com/watch?v=${stream.video_id}`" 
                                        target="_blank" 
                                        class="p-1.5 hover:text-white text-slate-400 rounded hover:bg-slate-800 transition"
                                        title="Open on YouTube"
                                    >
                                        <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="External" />
                                    </a>
                                </div>
                            </div>

                            <!-- Optional Embedded YouTube Live Chat Drawer -->
                            <div v-if="activeChatVideoId === stream.video_id" class="w-full h-48 bg-slate-900 border-t border-slate-800 relative">
                                <iframe 
                                    :src="stream.live_chat_url" 
                                    class="w-full h-full border-0"
                                ></iframe>
                            </div>
                        </div>
                    </div>

                </div>

            </template>

        </div>

            <!-- TAB 2: 10-7 OFFLINE POLICE ROSTER -->
            <div v-else-if="activeTab === '10-7'">
                
                <div class="max-w-6xl mx-auto bg-slate-950 rounded-2xl border border-slate-800/80 p-5 shadow-2xl">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-4 mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-100 flex items-center gap-2">
                                <img :src="iconRoster" class="w-4 h-4 invert opacity-90" alt="" />
                                <span>IME ROLEPLAY POLICE DEPARTMENT ROSTER (10-7 OFFLINE)</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Showing registered law enforcement officers currently off-duty or not broadcasting.
                            </p>
                        </div>
                        <div class="text-xs font-mono text-slate-400 bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800">
                            Total Registered: {{ offlineOfficers.length + allActiveStreams.length }} Units
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        <div 
                            v-for="officer in filteredOfflineOfficers" 
                            :key="officer.channel_id"
                            class="bg-[#0b121e] rounded-xl p-3.5 border border-slate-800 hover:border-slate-700 transition flex items-start space-x-3"
                        >
                            <!-- Avatar / Badge -->
                            <div class="w-11 h-11 rounded-lg bg-slate-900 border border-slate-800 overflow-hidden shrink-0 flex items-center justify-center p-1.5">
                                <img v-if="officer.avatar_url" :src="officer.avatar_url" class="w-full h-full object-cover rounded" />
                                <img v-else :src="getDeptIcon(officer.department)" class="w-full h-full object-contain" alt="" />
                            </div>

                            <!-- Officer Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-1.5">
                                    <span class="px-1.5 py-0.2 text-[10px] font-black rounded border" :class="getDeptBadgeClass(officer.department)">
                                        {{ officer.department }}
                                    </span>
                                    <span class="font-mono text-xs font-bold text-slate-300">{{ officer.callsign }}</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-100 truncate mt-0.5">{{ officer.officer_name }}</h3>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span>{{ officer.rank }}</span>
                                    <span>•</span>
                                    <span class="font-mono">{{ officer.badge_number }}</span>
                                </div>
                                <div class="text-[11px] font-mono text-blue-400/80 truncate mt-1">
                                    📍 {{ officer.patrol_zone || 'Los Santos' }}
                                </div>
                                <div class="mt-2.5 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px] text-slate-500">
                                    <div class="truncate mr-2">Streamer: <span class="text-slate-300 font-semibold">{{ officer.streamer_name }}</span></div>
                                    <div class="flex items-center space-x-1.5 shrink-0">
                                        <button 
                                            @click="togglePersonalStream(officer.channel_id || officer.handle)"
                                            class="p-1 rounded transition flex items-center gap-0.5 text-[10px]"
                                            :class="isPersonalStream(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/70 border border-purple-500/50' : 'text-slate-400 hover:text-purple-300 hover:bg-slate-800'"
                                            :title="isPersonalStream(officer.channel_id || officer.handle) ? 'Hapus dari Personal' : 'Tambah ke Personal Watchlist (Maks 6)'"
                                        >
                                            <img :src="isPersonalStream(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert" alt="" />
                                        </button>
                                        <button 
                                            @click="openSubscribePopup(officer.channel_id || officer.handle, officer.officer_name)"
                                            class="bg-red-600/90 hover:bg-red-600 text-white font-bold px-2 py-0.5 rounded text-[10px] transition flex items-center gap-1 shadow-sm shadow-red-600/30"
                                            title="Subscribe to channel without leaving page"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-white mr-0.5"></span>
                                            <span>Sub</span>
                                        </button>
                                        <a :href="`https://www.youtube.com/${officer.handle}`" target="_blank" class="text-blue-400 hover:underline flex items-center gap-0.5">
                                            <span>{{ officer.handle }}</span>
                                            <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-70" alt="" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TACTICAL COMMAND CENTER FOOTER (4 COLUMNS) -->
                <footer class="mt-16 bg-[#080d18]/95 border-t border-blue-900/40 text-slate-400 text-xs backdrop-blur-md">
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
                                <div class="flex items-center space-x-2 pt-1 font-mono text-[11px]">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>Multi-Sync Live</span>
                                    </span>
                                    <span class="text-slate-500">v2.4 Pro</span>
                                </div>
                            </div>

                            <!-- Column 2: Quick Tactical Navigation -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    <span>Navigasi Taktis</span>
                                </h4>
                                <ul class="space-y-2 text-[11px]">
                                    <li>
                                        <button 
                                            @click="selectedDepartment = 'ALL'; window.scrollTo({ top: 0, behavior: 'smooth' })" 
                                            class="hover:text-blue-300 transition flex items-center gap-1.5 text-slate-300"
                                        >
                                            <span>›</span>
                                            <span>CCTV Multiview & Cinema Hub</span>
                                        </button>
                                    </li>
                                    <li>
                                        <Link 
                                            href="/officers" 
                                            class="hover:text-blue-300 transition flex items-center gap-1.5 text-slate-300"
                                        >
                                            <span>›</span>
                                            <span>Direktori Petugas ({{ allDirectoryOfficers.length }} Personil)</span>
                                        </Link>
                                    </li>
                                    <li>
                                        <Link 
                                            href="/radio-codes" 
                                            class="hover:text-amber-300 transition flex items-center gap-1.5 text-slate-300"
                                        >
                                            <span>›</span>
                                            <span>Panduan Kode 10 & Radio TAC 1–5</span>
                                        </Link>
                                    </li>
                                    <li>
                                        <button 
                                            @click="openRightDrawer('QUICK_ADD')" 
                                            class="hover:text-emerald-300 transition flex items-center gap-1.5 text-slate-300"
                                        >
                                            <span>›</span>
                                            <span>Quick Add External YouTube Live Feed</span>
                                        </button>
                                    </li>
                                    <li>
                                        <Link 
                                            href="/about" 
                                            class="hover:text-indigo-300 transition flex items-center gap-1.5 text-slate-300"
                                        >
                                            <span>›</span>
                                            <span>Tentang Command Center Platform</span>
                                        </Link>
                                    </li>
                                </ul>
                            </div>

                            <!-- Column 3: Police Departments & Milestone -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    <span>Kesatuan Wilayah</span>
                                </h4>
                                <ul class="space-y-2 text-[11px]">
                                    <li>
                                        <button 
                                            @click="selectedDepartment = 'LSPD'; window.scrollTo({ top: 0, behavior: 'smooth' })" 
                                            class="hover:text-blue-300 transition flex items-center justify-between w-full text-slate-300"
                                        >
                                            <span class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                <span>LSPD - Mission Row HQ</span>
                                            </span>
                                            <span class="text-[10px] font-mono text-slate-500">{{ allActiveStreams.filter(s => s.officer?.department === 'LSPD').length }} Live</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button 
                                            @click="selectedDepartment = 'BCSO'; window.scrollTo({ top: 0, behavior: 'smooth' })" 
                                            class="hover:text-amber-300 transition flex items-center justify-between w-full text-slate-300"
                                        >
                                            <span class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                <span>BCSO - Paleto & Sandy Shores</span>
                                            </span>
                                            <span class="text-[10px] font-mono text-slate-500">{{ allActiveStreams.filter(s => s.officer?.department === 'BCSO').length }} Live</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button 
                                            @click="selectedDepartment = 'SASP'; window.scrollTo({ top: 0, behavior: 'smooth' })" 
                                            class="hover:text-teal-300 transition flex items-center justify-between w-full text-slate-300"
                                        >
                                            <span class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                                <span>SASP - State Troopers</span>
                                            </span>
                                            <span class="text-[10px] font-mono text-slate-500">{{ allActiveStreams.filter(s => s.officer?.department === 'SASP').length }} Live</span>
                                        </button>
                                    </li>
                                    <li class="pt-1">
                                        <div class="bg-red-950/40 border border-red-500/30 rounded-lg p-2 flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-red-300 font-semibold text-[10px]">
                                                <img :src="iconTarget" class="w-3.5 h-3.5 invert" alt="" />
                                                <span>Support 1K Subs</span>
                                            </div>
                                            <Link 
                                                href="/officers" 
                                                class="text-[10px] bg-red-600 hover:bg-red-500 text-white font-bold px-2 py-0.5 rounded transition"
                                            >
                                                Dukung Petugas
                                            </Link>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Column 4: Community & Dispatcher Support -->
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
                                    <a 
                                        href="https://discord.gg/imeroleplay" 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        class="w-full py-2 px-3 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-xs rounded-xl border border-slate-800 transition flex items-center justify-center gap-2"
                                    >
                                        <span>Discord IME Roleplay</span>
                                        <img :src="iconExternal" class="w-3 h-3 invert opacity-70" alt="" />
                                    </a>
                                </div>
                            </div>

                        </div>

                        <!-- Bottom Copyright & Fair Use Disclaimer -->
                        <div class="mt-10 pt-6 border-t border-slate-800/80 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-slate-500 font-mono">
                            <div class="flex items-center gap-2 text-center md:text-left">
                                <span>© 2026 IME Roleplay Police Command Center.</span>
                                <span class="hidden sm:inline">•</span>
                                <span class="hidden sm:inline text-slate-400">Tactical Multiview System</span>
                            </div>
                            <div class="text-center md:text-right text-[10px] text-slate-500">
                                GTA V / FiveM community fan project. All video feeds and streams belong to their respective YouTube creators.
                            </div>
                        </div>
                    </div>
                </footer>

            </div>

        </main>

        <!-- UNIFIED RIGHT SLIDE-OVER SIDEBAR / DRAWER -->
        <!-- 1. Backdrop Overlay -->
        <div 
            v-if="activeRightDrawer" 
            @click="closeRightDrawer"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 transition-opacity duration-300"
        ></div>

        <!-- 2. Sliding Drawer Panel -->
        <aside 
            class="fixed inset-y-0 right-0 z-50 bg-[#080d17]/98 border-l border-slate-800/90 shadow-2xl backdrop-blur-2xl flex flex-col transition-all duration-300 ease-in-out"
            :class="[
                activeRightDrawer ? 'translate-x-0' : 'translate-x-full pointer-events-none',
                activeRightDrawer === 'DIRECTORY' ? 'w-full sm:w-[580px] md:w-[740px] lg:w-[860px]' : (activeRightDrawer === 'RADIO_CODES' ? 'w-full sm:w-[540px] md:w-[680px] lg:w-[780px]' : 'w-full sm:w-[440px] md:w-[480px]')
            ]"
        >
            <!-- Drawer Top Header Bar -->
            <div class="bg-slate-900/95 px-4 py-3.5 border-b border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div 
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-base shrink-0 border"
                        :class="{
                            'bg-blue-950/80 border-blue-500/60 text-blue-400': activeRightDrawer === 'DIRECTORY',
                            'bg-amber-950/80 border-amber-500/60 text-amber-400': activeRightDrawer === 'RADIO_CODES',
                            'bg-emerald-950/80 border-emerald-500/60 text-emerald-400': activeRightDrawer === 'QUICK_ADD',
                            'bg-sky-950/80 border-sky-500/60 text-sky-400': activeRightDrawer === 'FEEDBACK',
                            'bg-indigo-950/80 border-indigo-500/60 text-indigo-400': activeRightDrawer === 'ABOUT',
                        }"
                    >
                        <img v-if="activeRightDrawer === 'DIRECTORY'" :src="iconUser" class="w-4 h-4 invert" alt="" />
                        <img v-else-if="activeRightDrawer === 'RADIO_CODES'" :src="iconRadio" class="w-4 h-4 invert" alt="" />
                        <img v-else-if="activeRightDrawer === 'QUICK_ADD'" :src="iconQuickAdd" class="w-4 h-4 invert" alt="" />
                        <img v-else-if="activeRightDrawer === 'FEEDBACK'" :src="iconFeedback" class="w-4 h-4 invert" alt="" />
                        <svg v-else-if="activeRightDrawer === 'ABOUT'" class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M12 16v-4m0-4h.01"/></svg>
                    </div>
                    <div class="truncate">
                        <div class="flex items-center space-x-2">
                            <h2 
                                class="text-xs font-black tracking-wider uppercase font-mono truncate"
                                :class="{
                                    'text-blue-300': activeRightDrawer === 'DIRECTORY',
                                    'text-amber-300': activeRightDrawer === 'RADIO_CODES',
                                    'text-emerald-300': activeRightDrawer === 'QUICK_ADD',
                                    'text-sky-300': activeRightDrawer === 'FEEDBACK',
                                    'text-indigo-300': activeRightDrawer === 'ABOUT',
                                }"
                            >
                                <span v-if="activeRightDrawer === 'DIRECTORY'">DIREKTORI PETUGAS & STREAMER</span>
                                <span v-else-if="activeRightDrawer === 'RADIO_CODES'">PANDUAN KODE 10 & PROTOKOL RADIO</span>
                                <span v-else-if="activeRightDrawer === 'QUICK_ADD'">QUICK ADD LIVE FEED</span>
                                <span v-else-if="activeRightDrawer === 'FEEDBACK'">LAPOR & USULAN STREAMER</span>
                                <span v-else-if="activeRightDrawer === 'ABOUT'">ABOUT COMMAND CENTER</span>
                            </h2>
                            <span 
                                class="text-[9px] px-1.5 py-0.2 rounded-full border font-mono uppercase"
                                :class="{
                                    'bg-blue-500/20 text-blue-300 border-blue-500/40': activeRightDrawer === 'DIRECTORY',
                                    'bg-amber-500/20 text-amber-300 border-amber-500/40': activeRightDrawer === 'RADIO_CODES',
                                    'bg-emerald-500/20 text-emerald-300 border-emerald-500/40': activeRightDrawer === 'QUICK_ADD',
                                    'bg-sky-500/20 text-sky-300 border-sky-500/40': activeRightDrawer === 'FEEDBACK',
                                    'bg-indigo-500/20 text-indigo-300 border-indigo-500/40': activeRightDrawer === 'ABOUT',
                                }"
                            >
                                {{ activeRightDrawer === 'DIRECTORY' ? 'Roster' : (activeRightDrawer === 'RADIO_CODES' ? 'Guide' : (activeRightDrawer === 'FEEDBACK' ? 'Discord' : (activeRightDrawer === 'ABOUT' ? 'Overview' : 'Temporary'))) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 truncate">
                            <span v-if="activeRightDrawer === 'DIRECTORY'">Daftar seluruh personil terdaftar LSPD, BCSO, SASP & status siaran</span>
                            <span v-else-if="activeRightDrawer === 'RADIO_CODES'">Referensi cepat kode radio 10-Codes & penugasan kanal TAC 1–5</span>
                            <span v-else-if="activeRightDrawer === 'QUICK_ADD'">Inject external YouTube live patrol feed into multifeed</span>
                            <span v-else-if="activeRightDrawer === 'FEEDBACK'">Kirim usul streamer atau perbaikan data ke Discord tim</span>
                            <span v-else-if="activeRightDrawer === 'ABOUT'">IME Roleplay Police Command Center & Tactical Multiview</span>
                        </p>
                    </div>
                </div>

                <!-- Header Actions: Close Button -->
                <div class="flex items-center space-x-1.5 shrink-0 ml-2">
                    <button 
                        @click="closeRightDrawer" 
                        class="p-1.5 text-slate-400 hover:text-white rounded-lg bg-slate-800/80 hover:bg-slate-700 transition text-xs font-bold"
                        title="Tutup Panel (Esc)"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- DRAWER CONTENT BODY -->
            <div class="flex-1 overflow-y-auto scrollbar-thin flex flex-col min-h-0 bg-[#060a12]">
                
                <!-- 1. QUICK ADD STREAM PANEL (Hashtag Search + Manual URL Input) -->
                <div v-if="activeRightDrawer === 'QUICK_ADD'" class="p-4 flex flex-col gap-3.5">
                    
                    <!-- Notification Toast -->
                    <div v-if="liveSearchSuccessNotice" class="bg-emerald-950/90 border border-emerald-500/60 rounded-xl px-3.5 py-2 text-xs text-emerald-300 font-mono flex items-center gap-2 shadow-lg animate-in fade-in duration-200">
                        <span>✓</span>
                        <span>{{ liveSearchSuccessNotice }}</span>
                    </div>

                    <!-- Personal Info Banner -->
                    <div class="bg-purple-950/30 border border-purple-500/40 rounded-xl p-3 text-xs text-purple-200/90 leading-relaxed">
                        <div class="font-bold flex items-center justify-between mb-1 text-purple-300">
                            <span class="flex items-center gap-1.5">
                                <img :src="iconPersonal" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                <span>Quick Watchlist & Personal</span>
                            </span>
                            <span class="text-[10px] bg-purple-900/60 px-2 py-0.5 rounded-full font-mono font-bold text-purple-200 border border-purple-400/30">
                                {{ totalPersonalCount }}/6 Video
                            </span>
                        </div>
                        Cari lawan/gang atau masukkan live stream YouTube. Stream yang dipilih akan langsung masuk ke tab <strong>PERSONAL</strong> browser lokal Anda.
                    </div>

                    <!-- Mode Toggle: Cari Live Hashtag vs Input Manual -->
                    <div class="grid grid-cols-2 bg-slate-950 p-1 rounded-xl border border-slate-800 gap-1">
                        <button 
                            type="button" 
                            @click="quickAddMode = 'SEARCH'"
                            :class="quickAddMode === 'SEARCH' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-400 hover:text-slate-200'"
                            class="py-1.5 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5"
                        >
                            <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                            <span>Cari Live Hashtag</span>
                        </button>
                        <button 
                            type="button" 
                            @click="quickAddMode = 'MANUAL'"
                            :class="quickAddMode === 'MANUAL' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-400 hover:text-slate-200'"
                            class="py-1.5 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5"
                        >
                            <img :src="iconUrl" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                            <span>Input Manual</span>
                        </button>
                    </div>

                    <!-- TAB 1: HASHTAG / KEYWORD LIVE SEARCH -->
                    <div v-if="quickAddMode === 'SEARCH'" class="flex flex-col gap-3">
                        
                        <!-- Search Form -->
                        <form @submit.prevent="handleSearchLiveStreams()" class="flex flex-col gap-2">
                            <div class="relative">
                                <input 
                                    v-model="liveSearchQuery" 
                                    type="text" 
                                    required
                                    placeholder="Ketik hashtag misal #imeroleplay #burgenk..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-20 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-purple-500 font-mono placeholder-slate-500"
                                />
                                <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-3" alt="" />
                                <button 
                                    type="submit" 
                                    :disabled="isLiveSearching"
                                    class="absolute right-1.5 top-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1"
                                >
                                    <img v-if="isLiveSearching" :src="iconRefresh" class="w-3 h-3 animate-spin invert" alt="" />
                                    <span>{{ isLiveSearching ? 'Mencari...' : 'Cari' }}</span>
                                </button>
                            </div>
                        </form>

                        <!-- Quick Hashtag Preset Chips -->
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Hashtag / Gang Populer:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button 
                                    v-for="preset in popularHashtagPresets" 
                                    :key="preset.label"
                                    type="button" 
                                    @click="handleSearchLiveStreams(preset.query)"
                                    class="text-[11px] px-2.5 py-1 rounded-lg border font-mono transition"
                                    :class="liveSearchQuery === preset.query ? 'bg-purple-600 text-white font-bold border-purple-400' : 'bg-slate-900 text-purple-300 hover:bg-purple-950/60 border-purple-500/30 hover:border-purple-400/50'"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Search Error / Empty State -->
                        <div v-if="liveSearchError" class="p-3 bg-red-950/30 border border-red-500/40 rounded-xl text-xs text-red-300">
                            {{ liveSearchError }}
                        </div>

                        <!-- Live Searching Loading Spinner -->
                        <div v-if="isLiveSearching" class="py-8 flex flex-col items-center justify-center text-slate-400 gap-2">
                            <div class="w-7 h-7 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-xs font-mono">Sedang mencari stream YouTube live aktif...</span>
                        </div>

                        <!-- Live Search Results List -->
                        <div v-else-if="liveSearchResults.length > 0" class="flex flex-col gap-2.5">
                            <div class="flex items-center justify-between text-xs text-slate-400 font-mono px-0.5">
                                <span>Ditemukan: <strong class="text-slate-200">{{ liveSearchResults.length }}</strong> Live Stream</span>
                                <span class="text-[10px] text-emerald-400 font-bold">● 10-8 LIVE</span>
                            </div>

                            <div class="space-y-2.5">
                                <div 
                                    v-for="item in liveSearchResults" 
                                    :key="item.video_id"
                                    class="bg-slate-900/90 border border-slate-800 hover:border-purple-500/60 rounded-xl p-2.5 transition flex gap-3 items-start"
                                >
                                    <!-- Thumbnail -->
                                    <div class="w-28 aspect-video bg-black rounded-lg overflow-hidden shrink-0 relative">
                                        <img :src="item.thumbnail_url" class="w-full h-full object-cover" loading="lazy" />
                                        <div class="absolute bottom-1 right-1 bg-black/80 px-1 py-0.2 rounded text-[9px] font-mono text-red-400 font-bold">
                                            LIVE
                                        </div>
                                    </div>

                                    <!-- Stream Info -->
                                    <div class="flex-1 min-w-0 flex flex-col justify-between h-full">
                                        <div>
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="text-xs font-bold text-slate-100 line-clamp-2 leading-snug flex-1" :title="item.title">
                                                    {{ item.title }}
                                                </h4>
                                            </div>
                                            <div class="text-[11px] text-slate-400 truncate mt-1 flex items-center gap-1.5">
                                                <img :src="iconUser" class="w-3 h-3 invert opacity-60" alt="" />
                                                <span class="text-purple-300 font-semibold truncate">{{ item.channel_name }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between gap-2">
                                            <button 
                                                type="button" 
                                                @click="handleAddLiveStreamToPersonal(item)"
                                                :class="isPersonalStream(item.video_id) ? 'bg-purple-950 text-purple-300 border-purple-500/50' : 'bg-purple-600 hover:bg-purple-500 text-white shadow-md shadow-purple-600/30'"
                                                class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center justify-center font-mono border border-transparent"
                                            >
                                                <span>{{ isPersonalStream(item.video_id) ? '✓ Pinned' : 'Pin ke Personal' }}</span>
                                            </button>

                                            <a 
                                                :href="`https://www.youtube.com/watch?v=${item.video_id}`" 
                                                target="_blank" 
                                                class="p-1 hover:text-white text-slate-400 hover:bg-slate-800 rounded transition flex items-center justify-center"
                                                title="Open on YouTube"
                                            >
                                                <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="Open on YouTube" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- TAB 2: MANUAL URL / VIDEO ID INJECTION -->
                    <div v-else-if="quickAddMode === 'MANUAL'">
                        <form @submit.prevent="handleQuickAddStream" class="flex flex-col gap-3.5">
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">YouTube Live URL or 11-char Video ID *</label>
                                <input 
                                    v-model="quickAddInput.urlOrId" 
                                    type="text" 
                                    required
                                    placeholder="https://youtube.com/watch?v=xxxxxxxxxxx or dQw4w9WgXcQ"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-mono placeholder-slate-600"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Officer / Unit Name</label>
                                    <input 
                                        v-model="quickAddInput.officerName" 
                                        type="text" 
                                        placeholder="Ofc. Raymond"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 placeholder-slate-600"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Callsign</label>
                                    <input 
                                        v-model="quickAddInput.callsign" 
                                        type="text" 
                                        placeholder="1-ADAM-99"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-mono placeholder-slate-600"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Department</label>
                                    <select 
                                        v-model="quickAddInput.department" 
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                                    >
                                        <option value="LSPD">LSPD (Police)</option>
                                        <option value="BCSO">BCSO (Sheriff)</option>
                                        <option value="SASP">SASP (State Police)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Patrol Zone</label>
                                    <input 
                                        v-model="quickAddInput.patrolZone" 
                                        type="text" 
                                        placeholder="Mission Row"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 placeholder-slate-600"
                                    />
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                                <button 
                                    type="button" 
                                    @click="closeRightDrawer" 
                                    class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg transition"
                                >
                                    Batal
                                </button>
                                <button 
                                    type="submit" 
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-600/30 transition flex items-center gap-1.5"
                                >
                                    <img :src="iconQuickAdd" class="w-3.5 h-3.5 invert" alt="" />
                                    <span>Inject Live Feed</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 3 / SECTION: SAVED PERSONAL WATCHLIST MANAGER -->
                    <div class="mt-2 pt-3 border-t border-slate-800/80 flex flex-col gap-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img :src="iconPersonal" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <span class="text-xs font-bold text-slate-200 uppercase font-mono tracking-wide">
                                    Watchlist Tersimpan
                                </span>
                                <span class="text-[10px] bg-purple-950/80 px-2 py-0.5 rounded-full font-mono text-purple-300 border border-purple-500/40 font-bold">
                                    {{ totalPersonalCount }}/6 Online ({{ totalSavedPersonalCount }} Total)
                                </span>
                            </div>
                            <button 
                                v-if="totalSavedPersonalCount > 0"
                                type="button" 
                                @click="clearAllPersonalStreams"
                                class="text-[11px] text-red-400 hover:text-red-300 font-mono flex items-center gap-1 hover:underline"
                                title="Kosongkan seluruh pin personal"
                            >
                                <img :src="iconReset" class="w-3 h-3 invert opacity-80" alt="" />
                                <span>Reset Semua</span>
                            </button>
                        </div>

                        <!-- Empty saved list -->
                        <div v-if="totalSavedPersonalCount === 0" class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center text-xs text-slate-500 font-mono">
                            Belum ada video atau channel yang disimpan di Personal.
                        </div>

                        <!-- Saved List items -->
                        <div v-else class="space-y-2 max-h-60 overflow-y-auto scrollbar-thin pr-0.5">
                            <div 
                                v-for="savedItem in savedPersonalList" 
                                :key="savedItem.id"
                                class="bg-slate-900/80 border border-slate-800 rounded-xl p-2.5 flex items-center justify-between gap-2.5 transition hover:border-slate-700"
                            >
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <img 
                                        v-if="savedItem.thumbnail" 
                                        :src="savedItem.thumbnail" 
                                        class="w-12 h-8 rounded object-cover bg-black shrink-0 border border-slate-800"
                                    />
                                    <div v-else class="w-12 h-8 rounded bg-slate-950 border border-slate-800 flex items-center justify-center text-xs text-slate-500 shrink-0">
                                        <img :src="iconPersonal" class="w-4 h-4 invert opacity-40" alt="" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span 
                                                class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold shrink-0"
                                                :class="savedItem.isOnline ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/40' : 'bg-slate-950 text-slate-500 border border-slate-800'"
                                            >
                                                {{ savedItem.isOnline ? '● 10-8 LIVE' : '○ 10-7 OFFLINE' }}
                                            </span>
                                            <span class="text-[11px] font-bold text-slate-200 truncate" :title="savedItem.name">{{ savedItem.name }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5">
                                            {{ savedItem.subtext }}
                                        </div>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    @click="removePersonalStream(savedItem.rawId)"
                                    class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-950 hover:bg-red-950/50 border border-slate-800 hover:border-red-500/40 rounded-lg text-xs transition shrink-0 flex items-center justify-center"
                                    title="Hapus dari daftar personal"
                                >
                                    <img :src="iconDelete" class="w-3.5 h-3.5 invert opacity-80 group-hover:opacity-100" alt="Delete" />
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 2. VISITOR FEEDBACK & CHANNEL REQUEST PANEL (DISCORD WEBHOOK) -->
                <div v-if="activeRightDrawer === 'FEEDBACK'" class="p-4 flex flex-col gap-4">
                    
                    <!-- Success Notification Toast -->
                    <div v-if="feedbackSuccessToast" class="bg-emerald-950/90 border border-emerald-500/50 rounded-xl px-4 py-2.5 text-xs text-emerald-300 font-mono flex items-center gap-2 shadow-lg">
                        <span class="text-base">✓</span>
                        <span>{{ feedbackSuccessToast }}</span>
                    </div>

                    <div class="bg-sky-950/20 border border-sky-500/30 rounded-xl p-3 text-xs text-sky-200/90 leading-relaxed">
                        <div class="font-bold flex items-center gap-1.5 mb-1 text-sky-300">
                            <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                            <span>Direct Dispatcher Line</span>
                        </div>
                        Formulir ini akan otomatis mengirim pesan langsung ke channel Discord Dispatcher IME Roleplay.
                    </div>

                    <form @submit.prevent="submitFeedbackForm" class="flex flex-col gap-3.5">
                        
                        <!-- Feedback Type Selector -->
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1.5 font-mono">Kategori Masukan *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    type="button" 
                                    @click="feedbackForm.type = 'CHANNEL_REQUEST'"
                                    :class="feedbackForm.type === 'CHANNEL_REQUEST' ? 'bg-sky-600 text-white font-bold border-sky-400 shadow-md shadow-sky-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconQuickAdd" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Usul Streamer</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="feedbackForm.type = 'DATA_CORRECTION'"
                                    :class="feedbackForm.type === 'DATA_CORRECTION' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconEdit" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Koreksi Data</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="feedbackForm.type = 'BUG_REPORT'"
                                    :class="feedbackForm.type === 'BUG_REPORT' ? 'bg-red-600 text-white font-bold border-red-400 shadow-md shadow-red-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconBug" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Lapor Bug</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="feedbackForm.type = 'OTHER'"
                                    :class="feedbackForm.type === 'OTHER' ? 'bg-purple-600 text-white font-bold border-purple-400 shadow-md shadow-purple-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconFeedback" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Lainnya</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Pengirim (Opsional)</label>
                                <input 
                                    v-model="feedbackForm.sender_name" 
                                    type="text" 
                                    placeholder="Warga / Nama Anda"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500 placeholder-slate-600"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Handle / Link YouTube</label>
                                <input 
                                    v-model="feedbackForm.handle_or_url" 
                                    type="text" 
                                    placeholder="@NamaStreamer atau URL"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500 font-mono placeholder-slate-600"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" v-if="feedbackForm.type === 'CHANNEL_REQUEST' || feedbackForm.type === 'DATA_CORRECTION'">
                            <div class="sm:col-span-2">
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Karakter / Callsign</label>
                                <input 
                                    v-model="feedbackForm.officer_name" 
                                    type="text" 
                                    placeholder="Ofc. Budi / 1-ADAM-12"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500 placeholder-slate-600"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Departemen</label>
                                <select 
                                    v-model="feedbackForm.department" 
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                                >
                                    <option value="LSPD">LSPD (Police)</option>
                                    <option value="BCSO">BCSO (Sheriff)</option>
                                    <option value="SASP">SASP (State Police)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Pesan / Catatan Detail *</label>
                            <textarea 
                                v-model="feedbackForm.message" 
                                required 
                                rows="4" 
                                :placeholder="feedbackForm.type === 'CHANNEL_REQUEST' ? 'Jelaskan jadwal live rutin streamer atau link channel YouTube resminya...' : (feedbackForm.type === 'DATA_CORRECTION' ? 'Jelaskan data apa yang perlu dikoreksi (misal pangkat naik jadi Sergeant, ganti callsign)...' : 'Tuliskan detail masukan atau kendala Anda...')"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg p-3 text-xs text-slate-200 focus:outline-none focus:border-sky-500 placeholder-slate-600"
                            ></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                            <button 
                                type="button" 
                                @click="closeRightDrawer" 
                                class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isSubmittingFeedback" 
                                class="px-4 py-2 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow-lg shadow-sky-600/30 flex items-center gap-1.5 transition"
                            >
                                <img v-if="isSubmittingFeedback" :src="iconRefresh" class="w-3.5 h-3.5 animate-spin invert" alt="" />
                                <img v-else :src="iconSend" class="w-3.5 h-3.5 invert" alt="" />
                                <span>{{ isSubmittingFeedback ? 'Mengirim...' : 'Kirim ke Discord' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2.5 ABOUT COMMAND CENTER PANEL -->
                <div v-if="activeRightDrawer === 'ABOUT'" class="p-4 sm:p-5 flex flex-col gap-4 text-xs">
                    
                    <!-- Branding Hero Card -->
                    <div class="bg-gradient-to-br from-blue-950/60 via-slate-900 to-slate-950 border border-blue-500/30 rounded-2xl p-4 shadow-xl flex items-center gap-3.5">
                        <div class="w-14 h-14 rounded-xl bg-slate-900/90 border border-blue-400/40 p-1 shrink-0 flex items-center justify-center shadow-inner">
                            <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="text-xs font-black tracking-wider text-blue-400 uppercase font-mono">IME ROLEPLAY</span>
                                <span class="text-[9px] bg-blue-500/20 text-blue-300 px-1.5 py-0.2 rounded font-mono font-bold border border-blue-500/40">v2.4</span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-100 leading-snug">Police Command Center & Tactical Multiview</h3>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                Sistem pengawasan dan monitoring siaran langsung terpadu bagi seluruh unit penegak hukum (LSPD, BCSO, SASP) di server GTA V IME Roleplay.
                            </p>
                        </div>
                    </div>

                    <!-- Core Features Section -->
                    <div class="space-y-2.5">
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Fitur Utama Platform</span>
                        </h4>

                        <div class="grid grid-cols-1 gap-2">
                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-blue-950/70 border border-blue-500/30 text-blue-400 shrink-0">
                                    <img :src="iconFocus" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Tactical CCTV Grid & Focus Mode</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Pantau kamera bodycam banyak petugas secara simultan dengan tata letak adaptif (Auto, 2x2, 3x3, 4x4) atau Focus Mode berukuran besar.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-indigo-950/70 border border-indigo-500/30 text-indigo-400 shrink-0">
                                    <img :src="iconAllUnits" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Cinema Hub & Swimlanes</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Katalog visual ala Netflix dengan Top Spotlight dan swimlane per kesatuan untuk siaran langsung dan rekaman patroli VOD terkini.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-amber-950/70 border border-amber-500/30 text-amber-400 shrink-0">
                                    <img :src="iconRadio" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Radio Taktis TAC 1–5</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Kelompokkan petugas ke dalam kanal radio darurat (TAC 1 s.d. TAC 5) dengan timer situasi otomatis dan popover 1-klik.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-red-950/70 border border-red-500/30 text-red-400 shrink-0">
                                    <img :src="iconTarget" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Support 1K Subs Community Milestone</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Apresiasi komunitas untuk mendukung petugas patroli mencapai target 1.000 subscriber YouTube pertama mereka.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-emerald-950/70 border border-emerald-500/30 text-emerald-400 shrink-0">
                                    <img :src="iconSaver" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Data Saver & Smart Bandwidth</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Hemat bandwidth dan cegah lag dengan mode standby ringan sebelum memutar stream YouTube.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supported Agencies -->
                    <div class="space-y-2">
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Kesatuan Kepolisian</span>
                        </h4>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-slate-900/80 border border-blue-500/30 rounded-xl p-2.5 text-center flex flex-col items-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded bg-blue-600 text-white mb-1">LSPD</span>
                                <span class="text-[10px] text-slate-300 font-bold">Los Santos Police</span>
                            </div>
                            <div class="bg-slate-900/80 border border-amber-500/30 rounded-xl p-2.5 text-center flex flex-col items-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded bg-amber-600 text-white mb-1">BCSO</span>
                                <span class="text-[10px] text-slate-300 font-bold">Sheriff's Office</span>
                            </div>
                            <div class="bg-slate-900/80 border border-indigo-500/30 rounded-xl p-2.5 text-center flex flex-col items-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded bg-indigo-600 text-white mb-1">SASP</span>
                                <span class="text-[10px] text-slate-300 font-bold">State Police</span>
                            </div>
                        </div>
                    </div>

                    <!-- Attribution & Credits -->
                    <div class="mt-2 pt-3 border-t border-slate-800/80 space-y-2 text-[11px] text-slate-400 leading-relaxed font-mono">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Server Komunitas:</span>
                            <span class="text-blue-400 font-bold">IME Roleplay Indonesia</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">API Provider:</span>
                            <span class="text-slate-300">YouTube Data API & Scraper</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Engine:</span>
                            <span class="text-slate-300">Laravel + Inertia Vue 3</span>
                        </div>
                    </div>

                    <!-- Close Action Button -->
                    <div class="mt-2 pt-2 border-t border-slate-800">
                        <button 
                            type="button" 
                            @click="closeRightDrawer" 
                            class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-bold rounded-xl border border-slate-800 transition"
                        >
                            Tutup Informasi
                        </button>
                    </div>

                </div>

                <!-- 2.6 OFFICER DIRECTORY & STREAMER ROSTER PANEL -->
                <div v-if="activeRightDrawer === 'DIRECTORY'" class="flex-1 flex flex-col min-h-0">
                    
                    <!-- Search & Filters Toolbar -->
                    <div class="bg-slate-950/95 px-4 py-3 border-b border-slate-800 flex flex-col gap-2.5 shrink-0">
                        
                        <!-- Search & Sort Row -->
                        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                            <div class="relative flex-1 min-w-[200px]">
                                <input 
                                    v-model="directorySearch" 
                                    type="text" 
                                    placeholder="Cari nama polisi, callsign, handle, badge, sektor..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono"
                                />
                                <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-3" alt="" />
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-[10px] font-mono text-slate-500 hidden sm:inline">Urutkan:</span>
                                <select 
                                    v-model="directorySortBy" 
                                    class="bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-slate-300 focus:outline-none focus:border-blue-500 font-mono"
                                >
                                    <option value="status">10-8 Live Teratas</option>
                                    <option value="subs_desc">Subscribers Terbanyak</option>
                                    <option value="subs_asc">Target 1K Milestone</option>
                                    <option value="name">Nama Petugas (A-Z)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Department Filter Pills -->
                        <div class="flex items-center justify-between gap-2 overflow-x-auto scrollbar-none pt-0.5">
                            <div class="flex items-center space-x-1.5">
                                <button 
                                    @click="directoryDeptFilter = 'ALL'"
                                    :class="directoryDeptFilter === 'ALL' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    Semua ({{ offlineOfficers.length + streams.length }})
                                </button>
                                <button 
                                    @click="directoryDeptFilter = 'LIVE_ONLY'"
                                    :class="directoryDeptFilter === 'LIVE_ONLY' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30 border-emerald-400' : 'bg-slate-900 text-emerald-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition flex items-center gap-1.5 whitespace-nowrap"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>10-8 Live ({{ streams.length }})</span>
                                </button>
                                <button 
                                    @click="directoryDeptFilter = 'LSPD'"
                                    :class="directoryDeptFilter === 'LSPD' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-900 text-blue-300 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    LSPD
                                </button>
                                <button 
                                    @click="directoryDeptFilter = 'BCSO'"
                                    :class="directoryDeptFilter === 'BCSO' ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400' : 'bg-slate-900 text-amber-300 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    BCSO
                                </button>
                                <button 
                                    @click="directoryDeptFilter = 'SASP'"
                                    :class="directoryDeptFilter === 'SASP' ? 'bg-teal-600 text-white font-bold shadow-md shadow-teal-600/30 border-teal-400' : 'bg-slate-900 text-teal-300 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    SASP
                                </button>
                            </div>

                            <span class="text-[11px] font-mono text-slate-500 whitespace-nowrap hidden sm:inline">
                                {{ allDirectoryOfficers.length }} Personil
                            </span>
                        </div>
                    </div>

                    <!-- Officers List Grid -->
                    <div class="flex-1 overflow-y-auto p-4 scrollbar-thin">
                        <div v-if="allDirectoryOfficers.length === 0" class="py-12 text-center text-slate-500 flex flex-col items-center">
                            <img :src="iconSearch" class="w-8 h-8 invert opacity-30 mb-2" alt="" />
                            <p class="text-xs">Tidak ada petugas yang cocok dengan filter pencarian.</p>
                            <button 
                                @click="directorySearch = ''; directoryDeptFilter = 'ALL'"
                                class="mt-2 text-xs text-blue-400 hover:underline font-mono"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div 
                                v-for="officer in allDirectoryOfficers" 
                                :key="officer.id"
                                class="bg-slate-900/90 border rounded-xl p-3.5 flex flex-col justify-between transition-all duration-200 hover:border-slate-700"
                                :class="officer.is_online ? 'border-emerald-500/40 shadow-sm shadow-emerald-500/5' : 'border-slate-800'"
                            >
                                <div>
                                    <!-- Top Row: Department Badge, Callsign, Status -->
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center space-x-1.5 min-w-0">
                                            <span 
                                                class="px-1.5 py-0.5 text-[9px] font-black rounded border font-mono"
                                                :class="getDeptBadgeClass(officer.department)"
                                            >
                                                {{ officer.department }}
                                            </span>
                                            <span class="font-mono text-xs font-bold text-slate-300 truncate">{{ officer.callsign }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono">{{ officer.badge_number }}</span>
                                        </div>

                                        <!-- Online / Offline Badge -->
                                        <div class="shrink-0">
                                            <span 
                                                v-if="officer.is_online" 
                                                class="inline-flex items-center gap-1 text-[9px] px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 font-mono font-bold"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                <span>10-8 ON DUTY</span>
                                            </span>
                                            <span 
                                                v-else 
                                                class="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-950 text-slate-500 border border-slate-800 font-mono"
                                            >
                                                10-7 OFF DUTY
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Officer Info -->
                                    <h4 class="text-sm font-bold text-slate-100 truncate" :title="officer.officer_name">
                                        {{ officer.officer_name }}
                                    </h4>
                                    
                                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 truncate">
                                        <span>{{ officer.rank }}</span>
                                        <span>•</span>
                                        <span class="text-blue-400 font-mono">{{ officer.handle }}</span>
                                        <span v-if="officer.streamer_name" class="text-slate-500 truncate">({{ officer.streamer_name }})</span>
                                    </div>

                                    <div class="text-[10px] font-mono text-slate-500 truncate mt-1">
                                        📍 {{ officer.patrol_zone || 'Los Santos Sector' }}
                                    </div>

                                    <!-- Milestone Progress (Towards 1K Subs) -->
                                    <div class="mt-2.5 bg-slate-950/80 border border-slate-800/80 rounded-lg p-2">
                                        <div class="flex items-center justify-between text-[10px] font-mono mb-1">
                                            <span class="text-slate-400 flex items-center gap-1">
                                                <img :src="iconTarget" class="w-3 h-3 invert opacity-80" alt="" />
                                                <span>{{ (officer.subscriber_count || 0) < 1000 ? 'Road to 1K' : 'Subscriber Count' }}:</span>
                                            </span>
                                            <span class="font-bold text-slate-200">
                                                {{ officer.subscriber_count ? Number(officer.subscriber_count).toLocaleString('id-ID') : '0' }} / 1.000
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="(officer.subscriber_count || 0) >= 1000 ? 'bg-emerald-500' : 'bg-red-500'"
                                                :style="{ width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Footer: Actions -->
                                <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between gap-1.5">
                                    <div class="flex items-center space-x-1.5">
                                        <!-- Pin to Personal Watchlist -->
                                        <button 
                                            @click="togglePersonalStream(officer.channel_id || officer.handle)"
                                            class="p-1.5 rounded-lg border transition text-xs flex items-center gap-1"
                                            :class="isPersonalStream(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/80 border-purple-500/50' : 'text-slate-400 hover:text-purple-300 bg-slate-950 hover:bg-slate-800 border-slate-800'"
                                            :title="isPersonalStream(officer.channel_id || officer.handle) ? 'Hapus dari Personal' : 'Pin ke Personal Tab'"
                                        >
                                            <img :src="isPersonalStream(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus" class="w-3.5 h-3.5 invert" alt="" />
                                        </button>

                                        <!-- 1-Click YouTube Subscribe Modal -->
                                        <button 
                                            @click="openSubscribePopup(officer.channel_id || officer.handle, officer.officer_name)"
                                            class="px-2.5 py-1 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-[10px] shadow-sm shadow-red-600/30 transition flex items-center gap-1"
                                            title="Subscribe ke YouTube channel"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            <span>Subscribe</span>
                                        </button>
                                    </div>

                                    <!-- Live Watch Action or Channel Link -->
                                    <div>
                                        <button 
                                            v-if="officer.is_online && officer.live_stream"
                                            @click="setFocusStream(officer.live_stream.video_id); closeRightDrawer(); selectedDepartment = 'ALL';"
                                            class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-[10px] shadow transition flex items-center gap-1"
                                            title="Buka siaran live di CCTV Multiview"
                                        >
                                            <img :src="iconFocus" class="w-3 h-3 invert" alt="" />
                                            <span>Tonton Live</span>
                                        </button>
                                        <a 
                                            v-else
                                            :href="`https://www.youtube.com/${officer.handle}`" 
                                            target="_blank" 
                                            class="text-[10px] text-blue-400 hover:underline flex items-center gap-1 font-mono px-2 py-1 rounded bg-slate-950 border border-slate-800 hover:border-blue-500/40"
                                        >
                                            <span>Channel</span>
                                            <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-70" alt="" />
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Directory Panel Footer -->
                    <div class="bg-slate-950 px-4 py-2.5 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between font-mono shrink-0">
                        <span>Total: {{ allDirectoryOfficers.length }} Personil</span>
                        <span class="text-emerald-400 font-bold">{{ streams.length }} Unit 10-8 Live</span>
                    </div>

                </div>

                <!-- 2.7 10-CODES & TAC RADIO GUIDE PANEL -->
                <div v-if="activeRightDrawer === 'RADIO_CODES'" class="flex-1 flex flex-col min-h-0">
                    
                    <!-- Sub-tabs: Kode 10 vs TAC Protocols -->
                    <div class="bg-slate-950/95 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center space-x-1.5">
                            <button 
                                @click="radioCodesActiveTab = 'CODES'"
                                :class="radioCodesActiveTab === 'CODES' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-3 py-1.5 text-xs rounded-lg border transition font-mono"
                            >
                                Kode 10 Kepolisian
                            </button>
                            <button 
                                @click="radioCodesActiveTab = 'TAC'"
                                :class="radioCodesActiveTab === 'TAC' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-3 py-1.5 text-xs rounded-lg border transition font-mono flex items-center gap-1.5"
                            >
                                <img :src="iconRadio" class="w-3.5 h-3.5 invert" alt="" />
                                <span>Kanal Radio TAC 1–5</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 1: 10-CODES CHEATSHEET -->
                    <div v-if="radioCodesActiveTab === 'CODES'" class="flex-1 flex flex-col min-h-0">
                        <!-- Search Box -->
                        <div class="p-3.5 bg-slate-950 border-b border-slate-800 shrink-0">
                            <div class="relative">
                                <input 
                                    v-model="radioCodesSearch" 
                                    type="text" 
                                    placeholder="Cari kode misal 10-80, 10-33, pursuit, darurat..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-500 font-mono"
                                />
                                <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-3" alt="" />
                            </div>
                        </div>

                        <!-- Codes Grid -->
                        <div class="flex-1 overflow-y-auto p-4 scrollbar-thin space-y-2.5">
                            <div 
                                v-for="item in filteredPolice10Codes" 
                                :key="item.code"
                                class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 hover:border-amber-500/40 transition"
                            >
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/40 font-mono font-bold text-xs">
                                            {{ item.code }}
                                        </span>
                                        <h4 class="text-xs font-bold text-slate-200">{{ item.title }}</h4>
                                    </div>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-950 text-slate-400 border border-slate-800 font-mono">
                                        {{ item.category }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                    {{ item.meaning }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: TAC RADIO GUIDELINES -->
                    <div v-else-if="radioCodesActiveTab === 'TAC'" class="flex-1 overflow-y-auto p-4 scrollbar-thin space-y-3">
                        <div class="bg-amber-950/20 border border-amber-500/30 rounded-xl p-3 text-xs text-amber-200/90 leading-relaxed">
                            <div class="font-bold flex items-center gap-1.5 mb-1 text-amber-300 font-mono">
                                <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                <span>Protokol Komando Tactical Radio TAC 1 s.d TAC 5</span>
                            </div>
                            Kanal TAC digunakan untuk mengkoordinasikan unit saat merespon kejadian darurat berskala besar agar radio dispatch utama tetap steril.
                        </div>

                        <div 
                            v-for="tac in tacChannelGuides" 
                            :key="tac.code"
                            class="bg-slate-900/90 border border-slate-800 rounded-xl p-3.5 hover:border-blue-500/40 transition space-y-2"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 rounded-lg bg-blue-600 text-white font-mono font-black text-xs shadow-sm">
                                        {{ tac.code }}
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-100">{{ tac.title }}</h4>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                                    :class="getTacUnitCount(tac.code.replace(' ', '_')) > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-slate-950 text-slate-500 border border-slate-800'"
                                >
                                    {{ getTacUnitCount(tac.code.replace(' ', '_')) }} Unit Aktif
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-300 leading-relaxed">
                                <span class="text-blue-400 font-semibold font-mono">Ruang Lingkup:</span> {{ tac.scope }}
                            </div>

                            <div class="text-[11px] text-slate-400 bg-slate-950/80 p-2.5 rounded-lg border border-slate-800/80 leading-relaxed">
                                <span class="text-amber-400 font-semibold font-mono">SOP & Protokol:</span> {{ tac.protocol }}
                            </div>

                            <div class="pt-1 flex items-center justify-end">
                                <button 
                                    @click="selectedDepartment = tac.code.replace(' ', '_'); closeRightDrawer();"
                                    class="px-3 py-1.5 rounded-lg bg-slate-850 hover:bg-blue-600 text-slate-200 hover:text-white text-xs font-semibold border border-slate-700 hover:border-blue-500 transition flex items-center gap-1.5 font-mono"
                                >
                                    <span>Buka Multiview {{ tac.code }}</span>
                                    <span>›</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Radio Codes Panel Footer -->
                    <div class="bg-slate-950 px-4 py-2.5 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between font-mono shrink-0">
                        <span>Standar Komunikasi LSPD / BCSO / SASP</span>
                        <span class="text-amber-400">Radio Dispatch SOP</span>
                    </div>

                </div>

            </div>

        </aside>

        <!-- Global Expiring TAC Channel Alert Prompt (When viewing other tabs) -->
        <div 
            v-if="expiringTacChannel" 
            class="fixed bottom-4 left-4 z-50 bg-slate-950/95 border-2 border-amber-500/80 rounded-xl p-3 shadow-2xl backdrop-blur-xl flex items-center space-x-3 text-xs animate-in slide-in-from-bottom duration-300 max-w-lg"
        >
            <div class="w-8 h-8 rounded-full bg-amber-500/20 border border-amber-400 flex items-center justify-center text-amber-300 shrink-0 font-mono font-bold text-sm">
                📻
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-amber-300 font-mono text-[11px] truncate">
                    {{ expiringTacChannel.name }} ({{ expiringTacChannel.video_ids.length }} Units) tersisa {{ formatRemainingTime(expiringTacChannel.remaining_seconds) }}
                </div>
                <div class="text-[10px] text-slate-400 truncate">Apakah situasi masih berlangsung?</div>
            </div>
            <div class="flex items-center space-x-1.5 shrink-0">
                <button 
                    @click="extendTacTimer(expiringTacChannel.code, 20)"
                    class="px-2 py-1 bg-amber-600 hover:bg-amber-500 text-black font-bold text-[10px] rounded font-mono shadow"
                >
                    +20m
                </button>
                <button 
                    @click="disbandTacChannel(expiringTacChannel.code)"
                    class="px-2 py-1 bg-red-950 hover:bg-red-900 text-red-300 text-[10px] rounded border border-red-500/40 font-mono"
                >
                    Bubarkan
                </button>
                <button 
                    @click="selectedDepartment = expiringTacChannel.code"
                    class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] rounded border border-slate-700 font-mono"
                >
                    Buka
                </button>
            </div>
        </div>

        <!-- Floating Tactical Action Toast -->
        <div 
            v-if="tacticalToast"
            class="fixed bottom-4 right-4 z-50 bg-slate-950/95 border border-amber-500/60 rounded-xl px-4 py-2.5 shadow-2xl backdrop-blur-xl flex items-center space-x-2.5 text-xs font-mono text-amber-300 animate-in slide-in-from-bottom duration-200"
        >
            <img :src="iconRadio" class="w-4 h-4 brightness-0 invert opacity-90 shrink-0" alt="" />
            <span>{{ tacticalToast.message }}</span>
        </div>

    </div>
</template>

<style scoped>
/* Tactical Command Center Aesthetics */
iframe {
    width: 100%;
    height: 100%;
}

/* Hide horizontal scrollbars across all browsers for swimlanes */
.scrollbar-none {
    -ms-overflow-style: none !important;
    scrollbar-width: none !important;
}
.scrollbar-none::-webkit-scrollbar {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
}

/* Custom Scrollbar for Right Supporting Column */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.6);
    border-radius: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.4);
    border-radius: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.7);
}
</style>
