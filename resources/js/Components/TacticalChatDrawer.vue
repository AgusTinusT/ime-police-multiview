<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import iconChatLine from '@/Components/Icons/chat-line-svgrepo-com.svg';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

// Drawer Open State
const isOpen = ref(false);
const unreadCount = ref(0);
const drawerWrapperRef = ref(null);

// Messages State
const messages = ref([]);
const pinnedMessage = ref(null);
const newMessageText = ref('');
const isSending = ref(false);
const errorMessage = ref('');

const messagesContainer = ref(null);
let pollInterval = null;

// Format Timestamp
const formatTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Avatar Generator
const getAvatarUrl = (name) => {
    const seed = encodeURIComponent(name || 'guest');
    return `https://api.dicebear.com/7.x/bottts/svg?seed=${seed}`;
};

// Scroll to Bottom
const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Fetch Messages API
const fetchMessages = async (isInitial = false) => {
    try {
        const lastId = !isInitial && messages.value.length > 0 ? messages.value[messages.value.length - 1].id : 0;
        const res = await fetch(`/api/v1/chat/messages?after_id=${lastId}`, {
            headers: { 'Accept': 'application/json' },
        });
        const json = await res.json();

        if (json.status === 'success') {
            if (json.pinned) {
                pinnedMessage.value = json.pinned;
            } else {
                pinnedMessage.value = null;
            }

            if (isInitial) {
                messages.value = json.data || [];
                scrollToBottom();
            } else if (json.data && json.data.length > 0) {
                const existingIds = new Set(messages.value.map(m => m.id));
                const newItems = json.data.filter(m => !existingIds.has(m.id));

                if (newItems.length > 0) {
                    messages.value = [...messages.value, ...newItems];

                    if (!isOpen.value) {
                        unreadCount.value += newItems.length;
                    } else {
                        scrollToBottom();
                    }
                }
            }
        }
    } catch (e) {
        console.warn('Chat poll error:', e);
    }
};

// Send Message API
const sendMessage = async () => {
    if (!newMessageText.value.trim() || isSending.value) return;

    if (!currentUser.value) {
        errorMessage.value = 'Silakan login terlebih dahulu untuk mengirim pesan.';
        return;
    }

    isSending.value = true;
    errorMessage.value = '';

    try {
        const res = await axios.post('/api/v1/chat/messages', {
            message: newMessageText.value.trim(),
        });

        const json = res.data;
        if (json.status === 'success') {
            newMessageText.value = '';
            if (json.data) {
                messages.value.push(json.data);
                scrollToBottom();
            }
        } else {
            errorMessage.value = json.message || 'Gagal mengirim pesan.';
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            errorMessage.value = 'Sesi Anda telah berakhir. Silakan muat ulang halaman (Ctrl+R).';
        } else {
            errorMessage.value = e.response?.data?.message || 'Gagal terhubung ke server chat.';
        }
    } finally {
        isSending.value = false;
    }
};

// Admin Action: Delete Message
const deleteMessage = async (id) => {
    if (!confirm('Hapus pesan ini dari chat komunitas?')) return;
    try {
        const res = await axios.delete(`/api/v1/chat/messages/${id}`);
        if (res.status === 200 || res.status === 204) {
            messages.value = messages.value.filter(m => m.id !== id);
            if (pinnedMessage.value && pinnedMessage.value.id === id) {
                pinnedMessage.value = null;
            }
        }
    } catch (e) {
        console.error('Delete error:', e);
    }
};

// Admin Action: Toggle Pin Message
const togglePinMessage = async (id) => {
    try {
        const res = await axios.patch(`/api/v1/chat/messages/${id}/pin`);
        const json = res.data;
        if (json.status === 'success') {
            fetchMessages(true);
        }
    } catch (e) {
        console.error('Pin error:', e);
    }
};

// Toggle Open Drawer
const toggleDrawer = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        unreadCount.value = 0;
        scrollToBottom();
    }
};

// Auto Close on Outside Click or ESC Key
const handleClickOutside = (event) => {
    if (!isOpen.value) return;
    if (drawerWrapperRef.value && !drawerWrapperRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
};

onMounted(() => {
    fetchMessages(true);
    pollInterval = setInterval(() => {
        fetchMessages(false);
    }, 3000);
    document.addEventListener('pointerdown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
    document.removeEventListener('pointerdown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div ref="drawerWrapperRef" class="hidden sm:block fixed bottom-4 right-4 z-50 font-sans selection:bg-blue-600 selection:text-white">
        
        <!-- FLOATING TOGGLE BUTTON (MINIMALIST TACTICAL PILL) -->
        <button 
            @click.stop="toggleDrawer"
            class="relative px-3.5 py-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 hover:text-white font-bold text-xs shadow-xl border border-slate-800 hover:border-blue-500/40 flex items-center space-x-2 transition cursor-pointer backdrop-blur-md"
            title="Buka Chat Komunitas Member"
        >
            <img :src="iconChatLine" class="w-3.5 h-3.5 invert opacity-80 shrink-0" alt="Chat" />
            <span class="font-mono text-xs font-semibold uppercase tracking-wider hidden sm:inline">Member Chat</span>
            
            <!-- Unread Badge (Sleek static badge without bouncing) -->
            <span 
                v-if="unreadCount > 0"
                class="px-1.5 py-0.2 rounded-full bg-red-600 text-white font-mono text-[9px] font-bold border border-slate-900 shadow-sm"
            >
                {{ unreadCount }}
            </span>
        </button>

        <!-- CHAT DRAWER PANEL -->
        <div 
            v-if="isOpen"
            class="fixed bottom-20 right-4 sm:right-6 w-[calc(100vw-32px)] sm:w-96 max-h-[560px] bg-[#080d19]/95 border border-blue-500/30 rounded-3xl shadow-2xl backdrop-blur-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in-95 font-sans z-50"
        >
            <!-- PANEL HEADER -->
            <div class="px-4 py-3.5 bg-gradient-to-r from-blue-950/80 via-slate-900 to-slate-950 border-b border-blue-900/40 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <img :src="iconChatLine" class="w-4 h-4 invert opacity-90 shrink-0" alt="Chat" />
                    <div>
                        <h3 class="text-xs font-black tracking-wider text-white font-mono uppercase">
                            MEMBER COMMUNITY CHAT
                        </h3>
                        <p class="text-[10px] text-slate-400 font-mono">Saluran Obrolan Bebas Antar Member</p>
                    </div>
                </div>
                <button 
                    @click="isOpen = false"
                    class="w-7 h-7 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-xs transition"
                >
                    ✕
                </button>
            </div>

            <!-- INFORMATIONAL NOTICE FOR MEMBERS -->
            <div class="bg-blue-950/40 border-b border-blue-500/20 px-3.5 py-2.5 flex items-start space-x-2 text-[11px] text-blue-200/90 shrink-0">
                <span class="text-xs shrink-0">💡</span>
                <div class="flex-1 min-w-0 leading-snug">
                    <span>Ruang chat terbuka untuk <strong>seluruh member & perwira</strong>. Untuk pengaduan/masukan resmi ke Dispatcher, silakan gunakan menu </span>
                    <Link href="/feedback" class="text-blue-300 font-bold underline hover:text-white">Feedback & Reports</Link>.
                </div>
            </div>

            <!-- PINNED MESSAGE BANNER (IF ANY) -->
            <div 
                v-if="pinnedMessage"
                class="bg-amber-950/40 border-b border-amber-500/40 px-3.5 py-2.5 flex items-start space-x-2 text-xs text-amber-200/90 shrink-0"
            >
                <span class="text-sm shrink-0">📌</span>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-[10px] text-amber-400 uppercase font-mono">Disematkan oleh Dispatch Admin:</div>
                    <div class="text-[11px] truncate text-slate-200">{{ pinnedMessage.message }}</div>
                </div>
            </div>

            <!-- MESSAGES CONTAINER -->
            <div 
                ref="messagesContainer"
                class="flex-1 p-4 space-y-3.5 overflow-y-auto min-h-[280px] max-h-[380px] scrollbar-thin"
            >
                <!-- Empty State -->
                <div v-if="messages.length === 0" class="py-12 text-center text-slate-500 font-mono text-xs">
                    Belum ada pesan. Mulai obrolan komunitas member!
                </div>

                <!-- Message Item -->
                <div 
                    v-for="msg in messages" 
                    :key="msg.id"
                    class="flex items-start space-x-2.5 group"
                >
                    <!-- User Avatar -->
                    <img 
                        :src="getAvatarUrl(msg.user?.name)" 
                        class="w-7 h-7 rounded-xl bg-slate-800 border border-slate-700/60 object-cover shrink-0 mt-0.5" 
                        alt=""
                    />

                    <!-- Content Bubble -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5 gap-2">
                            <div class="flex items-center space-x-1.5 truncate">
                                <span class="text-xs font-bold text-slate-200 truncate">{{ msg.user?.name || 'Member' }}</span>
                                
                                <!-- Role Badges -->
                                <span 
                                    v-if="msg.user?.role === 'admin'"
                                    class="px-1.5 py-0.2 rounded text-[9px] font-black font-mono uppercase bg-gradient-to-r from-amber-500 to-orange-500 text-black shadow-sm"
                                >
                                    DISPATCH ADMIN
                                </span>
                                <span 
                                    v-else
                                    class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono uppercase bg-blue-950 text-blue-300 border border-blue-500/30"
                                >
                                    MEMBER
                                </span>
                            </div>

                            <span class="text-[10px] text-slate-500 font-mono shrink-0">{{ formatTime(msg.created_at) }}</span>
                        </div>

                        <!-- Message Text -->
                        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl rounded-tl-xs px-3 py-2 text-xs text-slate-200 leading-relaxed break-words relative shadow-sm">
                            {{ msg.message }}
                        </div>

                        <!-- Admin Message Tools -->
                        <div 
                            v-if="currentUser?.role === 'admin'"
                            class="opacity-0 group-hover:opacity-100 transition flex items-center space-x-2 mt-1 text-[10px] font-mono text-slate-400"
                        >
                            <button @click="togglePinMessage(msg.id)" class="hover:text-amber-400">
                                {{ msg.is_pinned ? 'Unpin' : 'Pin' }}
                            </button>
                            <span>•</span>
                            <button @click="deleteMessage(msg.id)" class="hover:text-red-400">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INPUT BOX AREA -->
            <div class="p-3 bg-[#060a14] border-t border-slate-800/80 space-y-2 shrink-0">
                <div v-if="errorMessage" class="text-[11px] text-red-400 font-mono px-1">
                    ⚠️ {{ errorMessage }}
                </div>

                <!-- Input Form (Authenticated User) -->
                <form v-if="currentUser" @submit.prevent="sendMessage" class="flex items-center gap-2">
                    <input 
                        v-model="newMessageText"
                        type="text"
                        placeholder="Tulis pesan obrolan..."
                        maxlength="500"
                        class="flex-1 bg-slate-950/90 border border-slate-800 text-slate-100 placeholder-slate-500 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:border-blue-500 shadow-inner font-sans"
                    />
                    <button 
                        type="submit"
                        :disabled="isSending || !newMessageText.trim()"
                        class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white font-bold text-xs shadow-md transition shrink-0"
                    >
                        {{ isSending ? '...' : 'Kirim' }}
                    </button>
                </form>

                <!-- Guest Notice -->
                <div v-else class="text-center py-1">
                    <Link 
                        href="/login" 
                        class="text-xs text-blue-400 font-bold hover:underline flex items-center justify-center space-x-1"
                    >
                        <span>Sign In untuk Ikut Obrolan Komunitas</span>
                        <span>→</span>
                    </Link>
                </div>
            </div>

        </div>
    </div>
</template>
