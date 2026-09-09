<script setup>
import { ref, onMounted, computed, watch, onUnmounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    initialStreams: {
        type: Array,
        required: true,
    },
    initialOfflineOfficers: {
        type: Array,
        required: true,
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
const selectedDepartment = ref('ALL');
const activeTab = ref('10-8'); // '10-8' (online feeds) or '10-7' (offline roster)
const isSidebarOpen = ref(true);
const selectedLayout = ref('auto'); // 'auto', 'grid-2x2', 'grid-3x3', 'grid-4x4', 'focus'
const activeAudioVideoId = ref(null);
const searchFilter = ref('');
const focusedStreamId = ref(null);
const isSyncing = ref(false);
const syncFeedback = ref('');

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

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
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

// Custom Ad-hoc Streams
const customStreams = ref([]);
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
onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
});
onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});

// All combined streams (Scraped DB streams + Ad-hoc custom added streams)
const allActiveStreams = computed(() => {
    return [...streams.value, ...customStreams.value];
});

// Department List & Color Definitions
const departments = [
    { id: 'ALL', name: 'ALL UNITS', icon: '🛡️', color: 'border-slate-600 text-slate-300' },
    { id: 'LSPD', name: 'LSPD', icon: '👮', color: 'border-blue-500 text-blue-400 bg-blue-950/40' },
    { id: 'BCSO', name: 'BCSO', icon: '⭐', color: 'border-amber-500 text-amber-400 bg-amber-950/40' },
    { id: 'SASP', name: 'SASP', icon: '🦅', color: 'border-teal-500 text-teal-400 bg-teal-950/40' },
    { id: 'SWAT', name: 'SWAT', icon: '🎯', color: 'border-red-500 text-red-400 bg-red-950/40' },
    { id: 'AIR_SUPPORT', name: 'AIR-1', icon: '🚁', color: 'border-sky-500 text-sky-400 bg-sky-950/40' },
    { id: 'TRAFFIC', name: 'TRAFFIC', icon: '🏍️', color: 'border-orange-500 text-orange-400 bg-orange-950/40' },
    { id: 'K9', name: 'K9 UNIT', icon: '🐕', color: 'border-emerald-500 text-emerald-400 bg-emerald-950/40' },
];

// Department styling helper
const getDeptBadgeClass = (dept) => {
    switch (dept) {
        case 'LSPD': return 'bg-blue-600/30 text-blue-300 border-blue-500/50';
        case 'BCSO': return 'bg-amber-600/30 text-amber-300 border-amber-500/50';
        case 'SASP': return 'bg-teal-600/30 text-teal-300 border-teal-500/50';
        case 'SWAT': return 'bg-red-600/30 text-red-300 border-red-500/50';
        case 'AIR_SUPPORT': return 'bg-sky-600/30 text-sky-300 border-sky-500/50';
        case 'TRAFFIC': return 'bg-orange-600/30 text-orange-300 border-orange-500/50';
        case 'K9': return 'bg-emerald-600/30 text-emerald-300 border-emerald-500/50';
        default: return 'bg-slate-700/40 text-slate-300 border-slate-600';
    }
};

// Filtered Streams (Online 10-8)
const filteredStreams = computed(() => {
    let result = allActiveStreams.value;

    if (selectedDepartment.value !== 'ALL') {
        result = result.filter(s => s.officer && s.officer.department === selectedDepartment.value);
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
    let result = offlineOfficers.value;

    if (selectedDepartment.value !== 'ALL') {
        result = result.filter(o => o.department === selectedDepartment.value);
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

// Focus mode stream selector
const primaryFocusedStream = computed(() => {
    if (!visibleStreams.value.length) return null;
    if (focusedStreamId.value) {
        const found = visibleStreams.value.find(s => s.video_id === focusedStreamId.value);
        if (found) return found;
    }
    return visibleStreams.value[0];
});

const secondaryStreams = computed(() => {
    if (!primaryFocusedStream.value) return [];
    return visibleStreams.value.filter(s => s.video_id !== primaryFocusedStream.value.video_id);
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

// Manual Sync Action
const triggerSync = async () => {
    isSyncing.value = true;
    syncFeedback.value = 'Syncing police patrol feeds...';
    try {
        const response = await fetch('/api/v1/sync', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json();
        if (data.status === 'success') {
            syncFeedback.value = `✓ Sync complete: ${data.active_units} units 10-8 online`;
            setTimeout(() => {
                router.reload({ preserveScroll: true });
            }, 800);
        }
    } catch (e) {
        syncFeedback.value = 'Sync failed. Rechecking in 60s.';
    } finally {
        setTimeout(() => {
            isSyncing.value = false;
            syncFeedback.value = '';
        }, 3000);
    }
};

// Quick Add Feed
const handleQuickAddStream = () => {
    if (!quickAddInput.value.urlOrId) return;

    let videoId = quickAddInput.value.urlOrId.trim();
    const match = videoId.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
    if (match) {
        videoId = match[1];
    }

    if (videoId.length !== 11) {
        alert('Invalid YouTube Video ID or URL. Please enter an 11-character video ID or valid YouTube watch URL.');
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
    isQuickAddModalOpen.value = false;
    quickAddInput.value = {
        urlOrId: '',
        officerName: 'External Unit',
        callsign: 'TAC-UNIT',
        department: 'LSPD',
        patrolZone: 'Incident Sector',
    };
};

// Admin Roster Management State & Methods (Protected for Admin)
const isRosterModalOpen = ref(false);
const rosterOfficers = ref([]);
const isRosterLoading = ref(false);
const rosterSearch = ref('');
const rosterDept = ref('ALL');
const showOfficerFormModal = ref(false);
const isEditingOfficer = ref(false);
const rosterFeedback = ref('');
const officerForm = ref({
    id: null,
    channel_id: '',
    handle: '',
    streamer_name: '',
    officer_name: '',
    callsign: '',
    department: 'LSPD',
    rank: 'Officer',
    badge_number: '#000',
    patrol_zone: 'Mission Row / Downtown',
    is_active: true,
    avatar_url: '',
});

const openRosterManager = async () => {
    isRosterModalOpen.value = true;
    await fetchRosterOfficers();
};

const fetchRosterOfficers = async () => {
    isRosterLoading.value = true;
    try {
        let url = `/api/v1/officers?dept=${rosterDept.value}`;
        if (rosterSearch.value.trim()) {
            url += `&search=${encodeURIComponent(rosterSearch.value.trim())}`;
        }
        const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        if (res.ok) {
            const data = await res.json();
            rosterOfficers.value = data.data || [];
        }
    } catch (e) {
        console.error('Failed to load roster:', e);
    } finally {
        isRosterLoading.value = false;
    }
};

const openAddOfficerModal = () => {
    isEditingOfficer.value = false;
    officerForm.value = {
        id: null,
        channel_id: '',
        handle: '',
        streamer_name: '',
        officer_name: '',
        callsign: '',
        department: 'LSPD',
        rank: 'Officer',
        badge_number: '#000',
        patrol_zone: 'Mission Row / Downtown',
        is_active: true,
        avatar_url: '',
    };
    showOfficerFormModal.value = true;
};

const openEditOfficerModal = (officer) => {
    isEditingOfficer.value = true;
    officerForm.value = {
        id: officer.id,
        channel_id: officer.channel_id || '',
        handle: officer.handle || '',
        streamer_name: officer.streamer_name || '',
        officer_name: officer.officer_name || '',
        callsign: officer.callsign || '',
        department: officer.department || 'LSPD',
        rank: officer.rank || 'Officer',
        badge_number: officer.badge_number || '#000',
        patrol_zone: officer.patrol_zone || '',
        is_active: Boolean(officer.is_active),
        avatar_url: officer.avatar_url || '',
    };
    showOfficerFormModal.value = true;
};

const getCsrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

const submitOfficerForm = async () => {
    rosterFeedback.value = 'Saving officer to MySQL database...';
    try {
        const isEdit = isEditingOfficer.value && officerForm.value.id;
        const url = isEdit ? `/api/v1/officers/${officerForm.value.id}` : '/api/v1/officers';
        const method = isEdit ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(officerForm.value),
        });

        const data = await res.json();
        if (res.ok) {
            rosterFeedback.value = `✓ ${data.message || 'Saved successfully!'}`;
            showOfficerFormModal.value = false;
            await fetchRosterOfficers();
            router.reload({ preserveScroll: true });
        } else {
            rosterFeedback.value = `Error: ${data.message || 'Failed to save officer'}`;
        }
    } catch (e) {
        rosterFeedback.value = 'Network error while saving officer.';
    } finally {
        setTimeout(() => {
            rosterFeedback.value = '';
        }, 4000);
    }
};

const toggleOfficerActive = async (officer) => {
    try {
        const res = await fetch(`/api/v1/officers/${officer.id}/toggle`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });
        if (res.ok) {
            const data = await res.json();
            officer.is_active = data.is_active;
            router.reload({ preserveScroll: true });
        }
    } catch (e) {
        console.error('Toggle failed:', e);
    }
};

const deleteOfficerConfirm = async (officer) => {
    if (!confirm(`Are you sure you want to PERMANENTLY delete ${officer.officer_name} (${officer.callsign}) from MySQL?`)) {
        return;
    }
    try {
        const res = await fetch(`/api/v1/officers/${officer.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });
        if (res.ok) {
            rosterOfficers.value = rosterOfficers.value.filter(o => o.id !== officer.id);
            router.reload({ preserveScroll: true });
        }
    } catch (e) {
        console.error('Delete failed:', e);
    }
};

const handleAdminLogout = () => {
    router.post('/logout');
};

// Visitor Feedback & Channel Request State & Methods (Option 1 Discord Webhook)
const isFeedbackModalOpen = ref(false);
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

const openFeedbackModal = (type = 'CHANNEL_REQUEST') => {
    feedbackForm.value = {
        type: type,
        sender_name: '',
        handle_or_url: '',
        officer_name: '',
        callsign: '',
        department: 'LSPD',
        message: '',
    };
    feedbackSuccessToast.value = '';
    isFeedbackModalOpen.value = true;
};

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
                isFeedbackModalOpen.value = false;
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
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Branding: IME Roleplay Police Command -->
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-900 to-slate-900 border border-blue-500/40 shadow-inner">
                    <span class="text-xl">🚔</span>
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-black tracking-wider text-blue-400 uppercase">IME ROLEPLAY</span>
                        <span class="text-xs font-semibold px-1.5 py-0.2 bg-blue-500/20 text-blue-300 border border-blue-500/30 rounded">POLICE DIVISION</span>
                    </div>
                    <h1 class="text-xs text-slate-400 font-medium tracking-tight flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                        <span>Tactical CCTV & Bodycam Command Center</span>
                    </h1>
                </div>
            </div>

            <!-- Center: Tactical Telemetry & Active Units Stats -->
            <div class="hidden lg:flex items-center space-x-4 bg-slate-950/80 px-4 py-1.5 rounded-lg border border-slate-800/80 shadow-inner">
                <!-- Clock -->
                <div class="flex items-center space-x-2 border-r border-slate-800 pr-3">
                    <span class="text-slate-500 text-xs">🕒</span>
                    <span class="font-mono text-sm font-bold text-slate-200 tracking-wider">{{ currentTime }}</span>
                    <span class="text-[10px] text-blue-400 font-mono font-semibold uppercase">WIB (UTC+7)</span>
                </div>

                <!-- 10-8 Live Units Counter -->
                <div class="flex items-center space-x-2 border-r border-slate-800 pr-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-950/60 text-emerald-400 border border-emerald-500/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-ping"></span>
                        10-8 ON-DUTY: {{ allActiveStreams.length }}
                    </span>
                </div>

                <!-- 10-7 Offline Roster Counter -->
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-900 text-slate-400 border border-slate-700">
                        10-7 OFF-DUTY: {{ offlineOfficers.length }}
                    </span>
                </div>
            </div>

            <!-- Right Controls: Layout Picker, Audio, RP Tools & Fullscreen -->
            <div class="flex items-center space-x-2 flex-wrap">
                
                <!-- Layout Selector -->
                <div class="flex items-center bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
                    <button 
                        @click="selectedLayout = 'auto'" 
                        :class="selectedLayout === 'auto' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs font-medium rounded transition"
                        title="Auto-Fit Grid"
                    >
                        Auto
                    </button>
                    <button 
                        @click="selectedLayout = 'grid-2x2'" 
                        :class="selectedLayout === 'grid-2x2' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs font-medium rounded transition"
                        title="2x2 Quad Patrol Layout"
                    >
                        2x2
                    </button>
                    <button 
                        @click="selectedLayout = 'grid-3x3'" 
                        :class="selectedLayout === 'grid-3x3' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs font-medium rounded transition"
                        title="3x3 Sector Command Layout"
                    >
                        3x3
                    </button>
                    <button 
                        @click="selectedLayout = 'grid-4x4'" 
                        :class="selectedLayout === 'grid-4x4' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs font-medium rounded transition hidden md:inline-block"
                        title="4x4 Tactical Wall Layout"
                    >
                        4x4
                    </button>
                    <button 
                        @click="selectedLayout = 'focus'" 
                        :class="selectedLayout === 'focus' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs font-medium rounded transition"
                        title="Focus Priority Lead + Right Sidebar Units"
                    >
                        🎯 Focus
                    </button>
                </div>

                <!-- Global Mode Switcher: Saver vs Play All -->
                <div class="flex items-center bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
                    <button 
                        @click="enableDataSaver" 
                        :class="isDataSaverEnabled ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition flex items-center gap-1.5"
                        title="Saver Mode: Keeps feeds in standby until clicked to save bandwidth and prevent lag"
                    >
                        <span>⚡</span>
                        <span class="hidden sm:inline">Saver</span>
                    </button>
                    <button 
                        @click="disableDataSaverAndPlayAll" 
                        :class="!isDataSaverEnabled ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition flex items-center gap-1.5"
                        title="Play All: Streams all video feeds simultaneously"
                    >
                        <span>▶</span>
                        <span class="hidden sm:inline">Play All</span>
                    </button>
                </div>

                <!-- Global Audio Actions -->
                <div class="flex items-center space-x-1 bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
                    <button 
                        @click="muteAll" 
                        class="px-2.5 py-1 text-xs rounded text-slate-300 hover:text-amber-400 hover:bg-slate-800 transition flex items-center gap-1 font-semibold"
                        title="Mute All Feeds"
                    >
                        <span>🔇</span>
                        <span class="hidden sm:inline">Mute All</span>
                    </button>
                </div>

                <!-- Action Buttons: Feedback, Quick Add, Sync, Fullscreen -->
                <div class="flex items-center space-x-1.5">
                    <!-- Visitor Feedback / Channel Request Button -->
                    <button 
                        @click="openFeedbackModal('CHANNEL_REQUEST')"
                        class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-900 hover:bg-sky-900/40 text-sky-300 border border-sky-500/30 transition flex items-center gap-1.5 shadow-sm"
                        title="Usul Streamer Baru, Koreksi Pangkat/Callsign, atau Lapor Kendala"
                    >
                        <span>💬</span>
                        <span class="hidden sm:inline">Lapor / Usul</span>
                    </button>

                    <button 
                        @click="isQuickAddModalOpen = true"
                        class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-900 hover:bg-emerald-900/40 text-emerald-300 border border-emerald-500/30 transition flex items-center gap-1.5"
                        title="Add Custom YouTube Stream / Video ID"
                    >
                        <span>➕</span>
                        <span class="hidden sm:inline">Quick Feed</span>
                    </button>

                    <button 
                        @click="triggerSync" 
                        :disabled="isSyncing"
                        class="p-1.5 text-xs rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 transition"
                        :title="syncFeedback || 'Re-sync live streams'"
                    >
                        <span :class="{'inline-block animate-spin': isSyncing}">🔄</span>
                    </button>

                    <!-- Fullscreen Browser Button -->
                    <button 
                        @click="toggleBrowserFullscreen"
                        :class="isFullscreen ? 'bg-blue-600 text-white shadow-md shadow-blue-500/40 border-blue-400' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 border-slate-700'"
                        class="px-2.5 py-1 text-xs font-bold rounded border transition flex items-center gap-1.5"
                        :title="isFullscreen ? 'Exit Fullscreen Mode (Esc)' : 'Enter Fullscreen CCTV Wall Mode'"
                    >
                        <span>{{ isFullscreen ? '🗗' : '⛶' }}</span>
                        <span class="hidden md:inline">{{ isFullscreen ? 'Exit Fullscreen' : 'Fullscreen' }}</span>
                    </button>

                    <!-- Admin Only Controls (Visible only after logging in via /login or /admin) -->
                    <div v-if="$page.props.auth?.user" class="flex items-center space-x-1 bg-amber-950/40 p-0.5 rounded-lg border border-amber-500/50">
                        <button 
                            @click="openRosterManager"
                            class="px-2.5 py-1 text-xs font-bold rounded bg-amber-600 hover:bg-amber-500 text-white shadow-md shadow-amber-600/30 transition flex items-center gap-1.5"
                            title="Manage Officer Database (MySQL)"
                        >
                            <span>⚙️</span>
                            <span class="hidden sm:inline">Roster Manager</span>
                        </button>
                        <button 
                            @click="handleAdminLogout"
                            class="px-2 py-1 text-xs font-semibold rounded bg-slate-900 hover:bg-red-900/50 text-red-400 hover:text-red-200 border border-slate-700 transition flex items-center gap-1"
                            title="Logout Admin Session"
                        >
                            <span>🚪</span>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </div>
                </div>

            </div>
        </header>

        <!-- Department Filter Toolbar & Search -->
        <div class="bg-[#090f1a] border-b border-slate-800/80 px-4 py-2 flex flex-wrap items-center justify-between gap-2.5">
            
            <!-- Department Tabs -->
            <div class="flex items-center space-x-1.5 overflow-x-auto py-0.5 max-w-full scrollbar-none">
                <button 
                    v-for="dept in departments" 
                    :key="dept.id"
                    @click="selectedDepartment = dept.id"
                    :class="selectedDepartment === dept.id ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                    class="px-3 py-1 text-xs rounded-full border transition flex items-center space-x-1.5 whitespace-nowrap"
                >
                    <span>{{ dept.icon }}</span>
                    <span>{{ dept.name }}</span>
                    <span v-if="dept.id !== 'ALL'" class="text-[10px] px-1 py-0.2 bg-black/40 rounded-full font-mono">
                        {{ allActiveStreams.filter(s => s.officer?.department === dept.id).length }}
                    </span>
                </button>
            </div>

            <!-- Search & 10-8 / 10-7 Tab Switcher -->
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <!-- Search Input -->
                <div class="relative flex-1 sm:w-64">
                    <input 
                        v-model="searchFilter" 
                        type="text" 
                        placeholder="Search callsign, badge, officer..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-3 py-1 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    />
                    <span class="absolute left-2.5 top-1.5 text-xs text-slate-500">🔍</span>
                </div>

                <!-- Tab Toggle: 10-8 Feeds vs 10-7 Roster -->
                <div class="flex bg-slate-950 rounded-lg p-0.5 border border-slate-800">
                    <button 
                        @click="activeTab = '10-8'"
                        :class="activeTab === '10-8' ? 'bg-emerald-600 text-white font-semibold' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition flex items-center gap-1"
                    >
                        <span>🔴 10-8 Feeds</span>
                        <span class="text-[10px] bg-black/40 px-1 rounded">{{ visibleStreams.length }}</span>
                    </button>
                    <button 
                        @click="activeTab = '10-7'"
                        :class="activeTab === '10-7' ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 text-xs rounded transition flex items-center gap-1"
                    >
                        <span>⚪ 10-7 Roster</span>
                        <span class="text-[10px] bg-black/40 px-1 rounded">{{ filteredOfflineOfficers.length }}</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-3.5 md:p-4 overflow-y-auto">
            
            <!-- TAB 1: 10-8 ACTIVE LIVE BODYCAM FEEDS -->
            <div v-if="activeTab === '10-8'">
                
                <!-- Zero Feeds Fallback -->
                <div v-if="visibleStreams.length === 0" class="min-h-[60vh] flex flex-col items-center justify-center text-center p-8 bg-slate-950/40 rounded-2xl border border-slate-800/80">
                    <div class="w-16 h-16 rounded-full bg-blue-950/60 border border-blue-500/30 flex items-center justify-center text-3xl mb-4">
                        📡
                    </div>
                    <h2 class="text-lg font-bold text-slate-200 tracking-wide">NO ACTIVE 10-8 PATROL UNITS ONLINE</h2>
                    <p class="text-xs text-slate-400 max-w-md mt-1 mb-6">
                        No registered IME Roleplay police streamers are currently broadcasting in the selected department filter.
                    </p>
                    <div class="flex items-center space-x-3">
                        <button 
                            @click="isQuickAddModalOpen = true" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg shadow-lg shadow-blue-600/30 transition flex items-center gap-2"
                        >
                            <span>➕ Add Temporary Live Stream</span>
                        </button>
                        <button 
                            @click="triggerSync" 
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold rounded-lg border border-slate-700 transition flex items-center gap-2"
                        >
                            <span>🔄 Refresh Roster</span>
                        </button>
                    </div>
                </div>

                <!-- FOCUS MODE VIEW (Primary Large Video on Left + Right Support Column with Collapsible Live Chat) -->
                <div v-else-if="selectedLayout === 'focus'" class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    
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
                                
                                <div class="flex items-center space-x-2.5 shrink-0">
                                    <span class="text-xs text-red-400 font-mono font-bold flex items-center gap-1.5 bg-red-950/40 px-2 py-0.5 rounded border border-red-500/40">
                                        <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                        PRIMARY LEAD
                                    </span>
                                    <button 
                                        @click="toggleAudio(primaryFocusedStream.video_id)" 
                                        :class="activeAudioVideoId === primaryFocusedStream.video_id ? 'bg-emerald-600 text-white shadow-emerald-500/50' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                        class="px-3 py-1 text-xs font-bold rounded transition flex items-center gap-1.5"
                                    >
                                        <span>{{ activeAudioVideoId === primaryFocusedStream.video_id ? '🔊 LIVE AUDIO' : '🔇 MUTED' }}</span>
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

                                <!-- Tactical Overlay Badge -->
                                <div class="absolute top-3 right-3 pointer-events-none opacity-90 font-mono text-[10px] text-white/80 bg-black/70 px-2 py-1 rounded border border-white/10">
                                    AXON BODY 3 • TACTICAL LEAD
                                </div>
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
                                        class="font-mono text-xs px-2 py-0.5 rounded flex items-center gap-1 transition"
                                        title="Toggle Live Chat in Support Column"
                                    >
                                        <span>💬</span>
                                        <span>{{ isRightChatOpen ? 'Chat Open' : 'Live Chat' }}</span>
                                    </button>
                                    
                                    <a 
                                        :href="`https://www.youtube.com/watch?v=${primaryFocusedStream.video_id}`" 
                                        target="_blank" 
                                        class="text-blue-400 hover:text-blue-300 underline font-mono text-xs flex items-center gap-1"
                                    >
                                        <span>Open YT ↗</span>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT COLUMN: COLLAPSIBLE LIVE CHAT AT TOP + SUPPORT UNITS BELOW -->
                    <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-3">
                        
                        <!-- Supporting Units & Chat Header Bar -->
                        <div class="bg-slate-900/90 px-3.5 py-2 rounded-xl border border-slate-800 flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm">📡</span>
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
                                    <span>💬</span>
                                    <span>{{ isRightChatOpen ? 'HIDE CHAT' : 'LIVE CHAT' }}</span>
                                </button>

                                <!-- Mode Toggle Button -->
                                <button 
                                    @click="toggleGlobalDataSaver" 
                                    :class="isDataSaverEnabled ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/50' : 'bg-blue-950/80 text-blue-300 border-blue-500/50'"
                                    class="text-[10px] font-mono px-2 py-0.5 rounded border flex items-center gap-1 transition font-bold"
                                    :title="isDataSaverEnabled ? 'Click to Play All support feeds' : 'Click to enable Saver Mode'"
                                >
                                    <span>{{ isDataSaverEnabled ? '⚡ SAVER' : '▶ PLAY ALL' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- 1. COLLAPSIBLE LIVE CHAT CONTAINER (With user preferred 520px height) -->
                        <div v-if="isRightChatOpen && primaryFocusedStream" class="bg-slate-950 rounded-xl overflow-hidden border border-amber-500/50 shadow-2xl flex flex-col animate-in fade-in zoom-in-95 duration-200">
                            <div class="bg-slate-900/95 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 text-xs">
                                <div class="flex items-center space-x-1.5 text-amber-300 font-bold truncate">
                                    <span>💬</span>
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
                                        <button 
                                            v-if="activePreviewVideoIds.includes(stream.video_id) || !isDataSaverEnabled"
                                            @click="toggleAudio(stream.video_id)" 
                                            :class="activeAudioVideoId === stream.video_id ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                            class="px-1.5 py-0.5 text-[10px] rounded transition font-mono"
                                            title="Audio Switch"
                                        >
                                            {{ activeAudioVideoId === stream.video_id ? '🔊' : '🔇' }}
                                        </button>
                                        <button 
                                            @click="setFocusStream(stream.video_id)" 
                                            class="bg-blue-600 hover:bg-blue-500 text-white px-2 py-0.5 rounded text-[10px] font-bold shadow transition flex items-center gap-0.5"
                                            title="Set as Main Large Focus Video"
                                        >
                                            <span>🎯</span>
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

                                            <div class="flex items-center space-x-2">
                                                <button 
                                                    @click="setFocusStream(stream.video_id)"
                                                    class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg shadow-blue-600/40 transition flex items-center gap-1.5 transform hover:scale-105"
                                                    title="Play on Main Focus Screen"
                                                >
                                                    <span>🎯 Set Lead Screen</span>
                                                </button>
                                                
                                                <button 
                                                    @click="toggleSidebarPreview(stream.video_id)"
                                                    class="bg-emerald-600 hover:bg-emerald-500 text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold shadow transition flex items-center gap-1"
                                                    title="Play stream"
                                                >
                                                    <span>▶ Play</span>
                                                </button>
                                            </div>

                                            <span class="text-[10px] text-slate-400 font-mono mt-2">
                                                ⚡ Saver Standby
                                            </span>
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
                                            <span>🔴</span>
                                            <span>Sub</span>
                                        </button>
                                        <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="hover:text-white underline">
                                            YT ↗
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
                            <span class="text-emerald-400 font-bold flex items-center gap-1">
                                <span>⚡</span>
                                <span>SAVER MODE ACTIVE:</span>
                            </span>
                            <span class="text-slate-300">Feeds are in standby. Click ▶ to play any feed, or switch to Play All.</span>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <button 
                                @click="disableDataSaverAndPlayAll" 
                                class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow"
                            >
                                <span>▶</span>
                                <span>Play All Videos</span>
                            </button>
                            <button 
                                v-if="activeGridVideoIds.length > 0" 
                                @click="enableDataSaver" 
                                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg text-xs font-mono transition"
                            >
                                <span>✕ Reset to Saver</span>
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

                                <!-- Bodycam REC & Live Audio State -->
                                <div class="flex items-center space-x-2 shrink-0">
                                    <div class="flex items-center space-x-1 font-mono text-[10px] text-red-400 bg-red-950/40 px-1.5 py-0.5 rounded border border-red-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
                                        <span>REC ●</span>
                                    </div>
                                    
                                    <!-- Audio Button -->
                                    <button 
                                        v-if="!isDataSaverEnabled || activeGridVideoIds.includes(stream.video_id)"
                                        @click="toggleAudio(stream.video_id)" 
                                        :class="activeAudioVideoId === stream.video_id ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/40' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                        class="px-2 py-0.5 text-[11px] rounded transition flex items-center gap-1 font-mono"
                                        :title="activeAudioVideoId === stream.video_id ? 'Mute audio' : 'Unmute audio (auto-mutes all others)'"
                                    >
                                        <span>{{ activeAudioVideoId === stream.video_id ? '🔊 ON' : '🔇' }}</span>
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

                                    <!-- Tactical Bodycam Watermark HUD -->
                                    <div class="absolute top-2 right-2 pointer-events-none opacity-80 font-mono text-[9px] text-white/70 bg-black/60 px-1.5 py-0.5 rounded border border-white/10 hidden sm:block">
                                        AXON BODY 3 • LIVE
                                    </div>
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
                                                <span>🎯 Focus</span>
                                            </button>
                                            
                                            <button 
                                                @click="toggleGridStreamPlay(stream.video_id)"
                                                class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow transition flex items-center gap-1.5 hover:scale-105"
                                                title="Play this stream"
                                            >
                                                <span>▶ Play</span>
                                            </button>
                                        </div>

                                        <span class="text-[10px] text-slate-400 font-mono mt-2">
                                            ⚡ Saver Standby
                                        </span>
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
                                        <span>🔴</span>
                                        <span class="hidden sm:inline">Sub</span>
                                    </button>
                                    <button 
                                        @click="selectedLayout = 'focus'; setFocusStream(stream.video_id)"
                                        class="p-1 hover:text-blue-400 text-slate-400 rounded hover:bg-slate-800 transition"
                                        title="Focus This Stream as Tactical Lead"
                                    >
                                        🎯
                                    </button>
                                    <button 
                                        @click="activeChatVideoId = activeChatVideoId === stream.video_id ? null : stream.video_id"
                                        class="p-1 hover:text-amber-400 text-slate-400 rounded hover:bg-slate-800 transition"
                                        title="Toggle YouTube Live Chat Drawer"
                                    >
                                        💬
                                    </button>
                                    <a 
                                        :href="`https://www.youtube.com/watch?v=${stream.video_id}`" 
                                        target="_blank" 
                                        class="p-1 hover:text-white text-slate-400 rounded hover:bg-slate-800 transition"
                                        title="Open on YouTube"
                                    >
                                        ↗
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

            </div>

            <!-- TAB 2: 10-7 OFFLINE POLICE ROSTER -->
            <div v-else-if="activeTab === '10-7'">
                
                <div class="max-w-6xl mx-auto bg-slate-950 rounded-2xl border border-slate-800/80 p-5 shadow-2xl">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-4 mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-100 flex items-center gap-2">
                                <span>📋</span>
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
                            <div class="w-11 h-11 rounded-lg bg-slate-900 border border-slate-800 overflow-hidden shrink-0 flex items-center justify-center text-xl">
                                <img v-if="officer.avatar_url" :src="officer.avatar_url" class="w-full h-full object-cover" />
                                <span v-else>👮</span>
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
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button 
                                            @click="openSubscribePopup(officer.channel_id || officer.handle, officer.officer_name)"
                                            class="bg-red-600/90 hover:bg-red-600 text-white font-bold px-2 py-0.5 rounded text-[10px] transition flex items-center gap-1 shadow-sm shadow-red-600/30"
                                            title="Subscribe to channel without leaving page"
                                        >
                                            <span>🔴</span>
                                            <span>Sub</span>
                                        </button>
                                        <a :href="`https://www.youtube.com/${officer.handle}`" target="_blank" class="text-blue-400 hover:underline">
                                            {{ officer.handle }} ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>

        <!-- QUICK ADD CUSTOM STREAM MODAL -->
        <div v-if="isQuickAddModalOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-[#0b1320] border border-emerald-500/40 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
                
                <div class="bg-slate-900/90 px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-lg">➕</span>
                        <h2 class="text-sm font-bold text-emerald-300 tracking-wide uppercase">QUICK ADD PATROL STREAM</h2>
                    </div>
                    <button @click="isQuickAddModalOpen = false" class="text-slate-400 hover:text-white text-sm font-bold px-2 py-1 rounded bg-slate-800">
                        ✕ Close
                    </button>
                </div>

                <form @submit.prevent="handleQuickAddStream" class="p-4 bg-slate-950 flex flex-col gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300 block mb-1">YouTube Live URL or 11-char Video ID *</label>
                        <input 
                            v-model="quickAddInput.urlOrId" 
                            type="text" 
                            required
                            placeholder="https://youtube.com/watch?v=xxxxxxxxxxx or dQw4w9WgXcQ"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Officer / Unit Name</label>
                            <input 
                                v-model="quickAddInput.officerName" 
                                type="text" 
                                placeholder="Ofc. Raymond"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Callsign</label>
                            <input 
                                v-model="quickAddInput.callsign" 
                                type="text" 
                                placeholder="1-ADAM-99"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Department</label>
                            <select 
                                v-model="quickAddInput.department"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="LSPD">LSPD</option>
                                <option value="BCSO">BCSO</option>
                                <option value="SASP">SASP</option>
                                <option value="SWAT">SWAT</option>
                                <option value="AIR_SUPPORT">AIR-1</option>
                                <option value="TRAFFIC">TRAFFIC</option>
                                <option value="K9">K9</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Patrol Zone</label>
                            <input 
                                v-model="quickAddInput.patrolZone" 
                                type="text" 
                                placeholder="Mission Row"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end space-x-2">
                        <button 
                            type="button" 
                            @click="isQuickAddModalOpen = false" 
                            class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-600/30"
                        >
                            ➕ Inject Live Feed
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- ADMIN ONLY: TACTICAL ROSTER MANAGER MODAL (MYSQL DATABASE CRUD) -->
        <div v-if="isRosterModalOpen" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-5">
            <div class="bg-[#090f1a] border border-amber-500/50 rounded-2xl w-full max-w-5xl shadow-2xl shadow-amber-950/40 overflow-hidden max-h-[92vh] flex flex-col">
                
                <!-- Modal Header -->
                <div class="bg-slate-900 px-4 py-3 border-b border-amber-500/30 flex items-center justify-between flex-wrap gap-2 shrink-0">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-950/70 border border-amber-500/60 flex items-center justify-center text-base">
                            ⚙️
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-amber-400 tracking-wider uppercase font-mono flex items-center gap-2">
                                <span>TACTICAL ROSTER DATABASE MANAGER</span>
                                <span class="text-[10px] px-2 py-0.2 bg-amber-500/20 text-amber-300 rounded-full border border-amber-500/40 font-normal">MySQL Master</span>
                            </h2>
                            <p class="text-[11px] text-slate-400">Add, edit, delete, or toggle live stream monitoring for all police units</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Add Officer Button -->
                        <button 
                            @click="openAddOfficerModal"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-md shadow-emerald-600/30"
                        >
                            <span>➕</span>
                            <span>Add Officer / Streamer</span>
                        </button>
                        
                        <button 
                            @click="isRosterModalOpen = false" 
                            class="text-slate-400 hover:text-white text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 transition"
                        >
                            ✕ Close
                        </button>
                    </div>
                </div>

                <!-- Feedback Toast -->
                <div v-if="rosterFeedback" class="bg-blue-950/80 border-b border-blue-500/40 px-4 py-1.5 text-xs text-blue-300 font-mono text-center shrink-0 flex items-center justify-center gap-2">
                    <span>⚡</span>
                    <span>{{ rosterFeedback }}</span>
                </div>

                <!-- Filter & Search Toolbar -->
                <div class="bg-slate-950 px-4 py-2.5 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <!-- Dept Filter Tabs -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto max-w-full scrollbar-none py-0.5">
                        <button 
                            v-for="dept in departments" 
                            :key="dept.id"
                            @click="rosterDept = dept.id; fetchRosterOfficers();"
                            :class="rosterDept === dept.id ? 'bg-amber-600 text-white font-bold border-amber-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="px-2.5 py-1 text-xs rounded-full border transition flex items-center space-x-1 whitespace-nowrap"
                        >
                            <span>{{ dept.icon }}</span>
                            <span>{{ dept.name }}</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input 
                            v-model="rosterSearch" 
                            @input="fetchRosterOfficers"
                            type="text" 
                            placeholder="Filter by name, callsign, handle..."
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-500 font-mono"
                        />
                        <span class="absolute left-2.5 top-2 text-slate-500 text-xs">🔍</span>
                    </div>
                </div>

                <!-- Officers Table List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2 scrollbar-thin">
                    <div v-if="isRosterLoading" class="py-12 text-center text-slate-400 font-mono text-xs flex items-center justify-center gap-2">
                        <span class="animate-spin text-lg">🔄</span>
                        <span>Loading officer records from MySQL...</span>
                    </div>

                    <div v-else-if="rosterOfficers.length === 0" class="py-12 text-center text-slate-500 text-xs font-mono">
                        No officers found in database matching criteria.
                    </div>

                    <div v-else class="space-y-2">
                        <div 
                            v-for="officer in rosterOfficers" 
                            :key="officer.id"
                            class="bg-slate-900/80 hover:bg-slate-900 border rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 transition"
                            :class="officer.is_active ? 'border-slate-800' : 'border-red-900/40 bg-red-950/10 opacity-70'"
                        >
                            <!-- Officer Card Left -->
                            <div class="flex items-center space-x-3 min-w-0">
                                <img 
                                    :src="officer.avatar_url || `https://api.dicebear.com/7.x/bottts/svg?seed=${officer.handle}`" 
                                    :alt="officer.officer_name"
                                    class="w-10 h-10 rounded-full border border-slate-700 bg-slate-950 shrink-0"
                                />
                                <div class="min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-1.5 py-0.2 text-[10px] font-black rounded border font-mono" :class="getDeptBadgeClass(officer.department)">
                                            {{ officer.department }}
                                        </span>
                                        <span class="font-mono text-xs font-bold text-amber-400">{{ officer.callsign }}</span>
                                        <span class="text-xs font-mono text-slate-400">{{ officer.badge_number }}</span>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-100 truncate mt-0.5">{{ officer.officer_name }}</h3>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span>{{ officer.rank }}</span>
                                        <span>•</span>
                                        <span class="text-blue-400 font-mono">{{ officer.handle }}</span>
                                        <span v-if="officer.streamer_name" class="text-slate-500 truncate">({{ officer.streamer_name }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Officer Card Right: Zone, Status, Actions -->
                            <div class="flex items-center space-x-3 shrink-0 ml-auto">
                                <div class="text-[11px] font-mono text-slate-400 hidden md:block">
                                    📍 {{ officer.patrol_zone || 'Los Santos' }}
                                </div>

                                <!-- Toggle Active Switch -->
                                <button 
                                    @click="toggleOfficerActive(officer)"
                                    :class="officer.is_active ? 'bg-emerald-950 border-emerald-500/50 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-500'"
                                    class="px-2.5 py-1 rounded-lg border text-[11px] font-mono font-bold transition flex items-center gap-1.5"
                                    title="Toggle Monitoring Active/Disabled"
                                >
                                    <span :class="officer.is_active ? 'text-emerald-400' : 'text-slate-600'">●</span>
                                    <span>{{ officer.is_active ? 'ACTIVE' : 'DISABLED' }}</span>
                                </button>

                                <!-- Action Buttons: Edit & Delete -->
                                <div class="flex items-center space-x-1">
                                    <button 
                                        @click="openEditOfficerModal(officer)"
                                        class="p-1.5 rounded-lg bg-blue-950 hover:bg-blue-900 border border-blue-500/40 text-blue-300 text-xs transition"
                                        title="Edit Officer Record"
                                    >
                                        ✏️ Edit
                                    </button>
                                    <button 
                                        @click="deleteOfficerConfirm(officer)"
                                        class="p-1.5 rounded-lg bg-red-950 hover:bg-red-900 border border-red-500/40 text-red-300 text-xs transition"
                                        title="Delete from MySQL"
                                    >
                                        🗑️ Delete
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Modal Footer Info -->
                <div class="bg-slate-950 px-4 py-2 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between font-mono shrink-0">
                    <span>Total Database Units: {{ rosterOfficers.length }}</span>
                    <span>Direct MySQL Sync Enabled</span>
                </div>

            </div>
        </div>

        <!-- SUB-MODAL: ADD / EDIT OFFICER FORM -->
        <div v-if="showOfficerFormModal" class="fixed inset-0 bg-black/90 backdrop-blur-md z-[60] flex items-center justify-center p-3 sm:p-4">
            <div class="bg-[#0b1320] border border-amber-500/60 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
                
                <div class="bg-slate-900 px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">{{ isEditingOfficer ? '✏️' : '➕' }}</span>
                        <h3 class="text-sm font-bold text-amber-400 tracking-wide uppercase font-mono">
                            {{ isEditingOfficer ? 'EDIT OFFICER RECORD (MYSQL)' : 'ADD NEW OFFICER / STREAMER (MYSQL)' }}
                        </h3>
                    </div>
                    <button @click="showOfficerFormModal = false" class="text-slate-400 hover:text-white text-xs font-bold px-2 py-1 rounded bg-slate-800">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitOfficerForm" class="p-4 bg-slate-950 flex flex-col gap-3 max-h-[80vh] overflow-y-auto scrollbar-thin">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">YouTube Handle *</label>
                            <input 
                                v-model="officerForm.handle" 
                                type="text" 
                                required
                                placeholder="@StreamerHandle"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500 font-mono"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Streamer Name *</label>
                            <input 
                                v-model="officerForm.streamer_name" 
                                type="text" 
                                required
                                placeholder="Windah Basudara"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Officer In-Game Name *</label>
                            <input 
                                v-model="officerForm.officer_name" 
                                type="text" 
                                required
                                placeholder="Ofc. Budi Santoso"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Callsign *</label>
                            <input 
                                v-model="officerForm.callsign" 
                                type="text" 
                                required
                                placeholder="1-ADAM-12"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Department *</label>
                            <select 
                                v-model="officerForm.department"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                            >
                                <option value="LSPD">LSPD (Police)</option>
                                <option value="BCSO">BCSO (Sheriff)</option>
                                <option value="SASP">SASP (State Police)</option>
                                <option value="SWAT">SWAT (Tactical)</option>
                                <option value="AIR_SUPPORT">AIR-1 (Aviation)</option>
                                <option value="TRAFFIC">TRAFFIC (Highway)</option>
                                <option value="K9">K9 (Canine)</option>
                                <option value="DISPATCH">DISPATCH (Central)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Rank</label>
                            <input 
                                v-model="officerForm.rank" 
                                type="text" 
                                placeholder="Officer, Sergeant, Cadet..."
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Badge Number</label>
                            <input 
                                v-model="officerForm.badge_number" 
                                type="text" 
                                placeholder="#101"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500 font-mono"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Patrol Zone</label>
                            <input 
                                v-model="officerForm.patrol_zone" 
                                type="text" 
                                placeholder="Mission Row / Downtown"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-1">
                        <input 
                            type="checkbox" 
                            id="is_active_checkbox"
                            v-model="officerForm.is_active"
                            class="rounded bg-slate-900 border-slate-800 text-amber-500 focus:ring-amber-500"
                        />
                        <label for="is_active_checkbox" class="text-xs text-slate-300">
                            Actively monitor this streamer for live streams
                        </label>
                    </div>

                    <div class="mt-3 flex items-center justify-end space-x-2 pt-2 border-t border-slate-800">
                        <button 
                            type="button" 
                            @click="showOfficerFormModal = false" 
                            class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-amber-600/30 font-mono"
                        >
                            💾 Save to MySQL
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- VISITOR FEEDBACK & CHANNEL REQUEST MODAL (DISCORD NOTIFICATION) -->
        <div v-if="isFeedbackModalOpen" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-4">
            <div class="bg-[#090f1a] border border-sky-500/50 rounded-2xl w-full max-w-lg shadow-2xl shadow-sky-950/40 overflow-hidden flex flex-col">
                
                <!-- Modal Header -->
                <div class="bg-slate-900 px-4 py-3 border-b border-sky-500/30 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-sky-950/80 border border-sky-500/50 flex items-center justify-center text-base">
                            💬
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-sky-400 tracking-wider uppercase font-mono">
                                MASUKAN & USULAN STREAMER
                            </h3>
                            <p class="text-[11px] text-slate-400">Pesan akan diteruskan langsung ke Discord Tim Dispatch</p>
                        </div>
                    </div>
                    <button @click="isFeedbackModalOpen = false" class="text-slate-400 hover:text-white text-xs font-bold px-2 py-1 rounded bg-slate-800">
                        ✕
                    </button>
                </div>

                <!-- Success Toast -->
                <div v-if="feedbackSuccessToast" class="bg-emerald-950/90 border-b border-emerald-500/40 px-4 py-2 text-xs text-emerald-300 font-mono text-center flex items-center justify-center gap-2">
                    <span>✓</span>
                    <span>{{ feedbackSuccessToast }}</span>
                </div>

                <form @submit.prevent="submitFeedbackForm" class="p-4 bg-slate-950 flex flex-col gap-3.5 max-h-[80vh] overflow-y-auto scrollbar-thin">
                    
                    <!-- Feedback Type Selector -->
                    <div>
                        <label class="text-xs font-semibold text-slate-300 block mb-1.5 font-mono">Kategori Masukan *</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button 
                                type="button" 
                                @click="feedbackForm.type = 'CHANNEL_REQUEST'"
                                :class="feedbackForm.type === 'CHANNEL_REQUEST' ? 'bg-sky-600 text-white font-bold border-sky-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-2.5 py-1.5 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                            >
                                <span>➕</span>
                                <span class="truncate">Usul Channel Baru</span>
                            </button>
                            <button 
                                type="button" 
                                @click="feedbackForm.type = 'DATA_CORRECTION'"
                                :class="feedbackForm.type === 'DATA_CORRECTION' ? 'bg-amber-600 text-white font-bold border-amber-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-2.5 py-1.5 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                            >
                                <span>✏️</span>
                                <span class="truncate">Koreksi Data / Pangkat</span>
                            </button>
                            <button 
                                type="button" 
                                @click="feedbackForm.type = 'BUG_REPORT'"
                                :class="feedbackForm.type === 'BUG_REPORT' ? 'bg-red-600 text-white font-bold border-red-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-2.5 py-1.5 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                            >
                                <span>🐞</span>
                                <span class="truncate">Lapor Bug / Kendala</span>
                            </button>
                            <button 
                                type="button" 
                                @click="feedbackForm.type = 'OTHER'"
                                :class="feedbackForm.type === 'OTHER' ? 'bg-purple-600 text-white font-bold border-purple-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-2.5 py-1.5 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                            >
                                <span>💬</span>
                                <span class="truncate">Lainnya</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Pengirim (Opsional)</label>
                            <input 
                                v-model="feedbackForm.sender_name" 
                                type="text" 
                                placeholder="Warga / Nama Anda"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Handle / Link YouTube</label>
                            <input 
                                v-model="feedbackForm.handle_or_url" 
                                type="text" 
                                placeholder="@NamaStreamer atau URL"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2" v-if="feedbackForm.type === 'CHANNEL_REQUEST' || feedbackForm.type === 'DATA_CORRECTION'">
                        <div class="col-span-2">
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Karakter / Callsign</label>
                            <input 
                                v-model="feedbackForm.officer_name" 
                                type="text" 
                                placeholder="Ofc. Budi / 1-ADAM-12"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Departemen</label>
                            <select 
                                v-model="feedbackForm.department"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                            >
                                <option value="LSPD">LSPD</option>
                                <option value="BCSO">BCSO</option>
                                <option value="SASP">SASP</option>
                                <option value="SWAT">SWAT</option>
                                <option value="AIR_SUPPORT">AIR-1</option>
                                <option value="TRAFFIC">TRAFFIC</option>
                                <option value="K9">K9</option>
                                <option value="DISPATCH">DISPATCH</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-300 block mb-1">Pesan / Catatan Detail *</label>
                        <textarea 
                            v-model="feedbackForm.message" 
                            required
                            rows="3"
                            :placeholder="feedbackForm.type === 'CHANNEL_REQUEST' ? 'Jelaskan jadwal live rutin streamer atau link channel YouTube resminya...' : (feedbackForm.type === 'DATA_CORRECTION' ? 'Jelaskan data apa yang perlu dikoreksi (misal pangkat naik jadi Sergeant, ganti callsign)...' : 'Tuliskan detail masukan atau kendala Anda...')"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                        ></textarea>
                    </div>

                    <div class="mt-2 flex items-center justify-end space-x-2 pt-2 border-t border-slate-800">
                        <button 
                            type="button" 
                            @click="isFeedbackModalOpen = false" 
                            class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="isSubmittingFeedback"
                            class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow-lg shadow-sky-600/30 flex items-center gap-1.5"
                        >
                            <span v-if="isSubmittingFeedback" class="animate-spin">🔄</span>
                            <span v-else>🚀</span>
                            <span>{{ isSubmittingFeedback ? 'Mengirim...' : 'Kirim ke Discord' }}</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</template>

<style scoped>
/* Tactical Command Center Aesthetics */
iframe {
    width: 100%;
    height: 100%;
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
