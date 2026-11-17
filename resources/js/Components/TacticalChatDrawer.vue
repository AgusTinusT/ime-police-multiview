<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import axios from "axios";
import {
    MessageSquare,
    X,
    Pin,
    ShieldCheck,
    AlertTriangle,
    ArrowRight,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

const isOpen = ref(false);
const unreadCount = ref(0);
const drawerWrapperRef = ref(null);

const messages = ref([]);
const pinnedMessage = ref(null);
const newMessageText = ref("");
const isSending = ref(false);
const errorMessage = ref("");

const messagesContainer = ref(null);
let pollInterval = null;

const formatTime = (isoString) => {
    if (!isoString) return "";
    const date = new Date(isoString);
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
};

const getAvatarUrl = (name) => {
    const seed = encodeURIComponent(name || "guest");
    return `https://api.dicebear.com/7.x/bottts/svg?seed=${seed}`;
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop =
            messagesContainer.value.scrollHeight;
    }
};

const fetchMessages = async (isInitial = false) => {
    try {
        const lastId =
            !isInitial && messages.value.length > 0
                ? messages.value[messages.value.length - 1].id
                : 0;
        const res = await fetch(`/api/v1/chat/messages?after_id=${lastId}`, {
            headers: { Accept: "application/json" },
        });
        const json = await res.json();

        if (json.status === "success") {
            if (json.pinned) {
                pinnedMessage.value = json.pinned;
            } else {
                pinnedMessage.value = null;
            }

            if (isInitial) {
                messages.value = json.data || [];
                scrollToBottom();
            } else if (json.data && json.data.length > 0) {
                const existingIds = new Set(messages.value.map((m) => m.id));
                const newItems = json.data.filter(
                    (m) => !existingIds.has(m.id),
                );

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
        console.warn("Chat poll error:", e);
    }
};

const sendMessage = async () => {
    if (!newMessageText.value.trim() || isSending.value) return;

    if (!currentUser.value) {
        errorMessage.value =
            "Silakan login terlebih dahulu untuk mengirim pesan.";
        return;
    }

    isSending.value = true;
    errorMessage.value = "";

    try {
        const res = await axios.post("/api/v1/chat/messages", {
            message: newMessageText.value.trim(),
        });

        const json = res.data;
        if (json.status === "success") {
            newMessageText.value = "";
            if (json.data) {
                messages.value.push(json.data);
                scrollToBottom();
            }
        } else {
            errorMessage.value = json.message || "Gagal mengirim pesan.";
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            errorMessage.value =
                "Sesi Anda telah berakhir. Silakan muat ulang halaman (Ctrl+R).";
        } else {
            errorMessage.value =
                e.response?.data?.message || "Gagal terhubung ke server chat.";
        }
    } finally {
        isSending.value = false;
    }
};

const pendingDeleteId = ref(null);

const deleteMessage = (id) => {
    pendingDeleteId.value = id;
};

const confirmDeleteMessage = async () => {
    const id = pendingDeleteId.value;
    pendingDeleteId.value = null;
    if (!id) return;
    try {
        const res = await axios.delete(`/api/v1/chat/messages/${id}`);
        if (res.status === 200 || res.status === 204) {
            messages.value = messages.value.filter((m) => m.id !== id);
            if (pinnedMessage.value && pinnedMessage.value.id === id) {
                pinnedMessage.value = null;
            }
        }
    } catch (e) {
        console.error("Delete error:", e);
    }
};

const togglePinMessage = async (id) => {
    try {
        const res = await axios.patch(`/api/v1/chat/messages/${id}/pin`);
        const json = res.data;
        if (json.status === "success") {
            fetchMessages(true);
        }
    } catch (e) {
        console.error("Pin error:", e);
    }
};

const toggleDrawer = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        unreadCount.value = 0;
        scrollToBottom();
    }
};

const handleClickOutside = (event) => {
    if (!isOpen.value) return;
    if (
        drawerWrapperRef.value &&
        !drawerWrapperRef.value.contains(event.target)
    ) {
        isOpen.value = false;
    }
};

const handleKeydown = (event) => {
    if (event.key === "Escape" && isOpen.value) {
        isOpen.value = false;
    }
};

onMounted(() => {
    fetchMessages(true);
    pollInterval = setInterval(() => {
        fetchMessages(false);
    }, 3000);
    document.addEventListener("pointerdown", handleClickOutside);
    document.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
    document.removeEventListener("pointerdown", handleClickOutside);
    document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <div
        ref="drawerWrapperRef"
        class="block fixed bottom-20 sm:bottom-4 right-4 z-50 font-sans selection:bg-primary selection:text-primary-foreground"
    >
        <!-- FLOATING TOGGLE BUTTON -->
        <Button
            type="button"
            variant="outline"
            size="sm"
            @click.stop="toggleDrawer"
            class="relative h-9 px-3.5 rounded-xl bg-card/90 hover:bg-muted text-foreground font-bold text-xs shadow-xl border-border hover:border-primary/40 gap-2 backdrop-blur-md"
            title="Buka Chat Komunitas Member"
        >
            <MessageSquare class="w-3.5 h-3.5 shrink-0" />
            <span
                class="font-mono text-xs font-semibold uppercase tracking-wider hidden sm:inline"
                >Chat</span
            >

            <Badge
                v-if="unreadCount > 0"
                variant="destructive"
                class="px-1.5 py-0 rounded-full font-mono text-xs font-bold"
            >
                {{ unreadCount }}
            </Badge>
        </Button>

        <!-- CHAT DRAWER PANEL -->
        <div
            v-if="isOpen"
            class="fixed bottom-20 right-4 sm:right-6 w-[calc(100vw-32px)] sm:w-96 max-h-[560px] bg-popover/95 border border-primary/30 rounded-2xl shadow-2xl backdrop-blur-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in-95 font-sans z-50"
        >
            <!-- PANEL HEADER -->
            <div
                class="px-4 py-3.5 bg-card border-b border-border flex items-center justify-between"
            >
                <div class="flex items-center space-x-2.5">
                    <MessageSquare class="w-4 h-4 text-primary shrink-0" />
                    <div>
                        <h3
                            class="text-xs font-extrabold tracking-wider text-foreground font-mono uppercase"
                        >
                            MEMBER COMMUNITY CHAT
                        </h3>
                        <p class="text-xs text-muted-foreground font-mono">
                            Saluran Obrolan Bebas Antar Member
                        </p>
                    </div>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Tutup Chat"
                    @click="isOpen = false"
                    class="h-7 w-7 text-muted-foreground hover:text-foreground"
                >
                    <X class="w-3.5 h-3.5" />
                </Button>
            </div>

            <!-- INFORMATIONAL NOTICE FOR MEMBERS -->
            <div
                class="bg-primary/10 border-b border-primary/20 px-3.5 py-2.5 flex items-start space-x-2 text-xs text-foreground shrink-0"
            >
                <div class="flex-1 min-w-0 leading-snug">
                    <span
                        >Ruang chat terbuka untuk
                        <strong>seluruh member & perwira</strong>. Untuk
                        pengaduan/masukan resmi ke Dispatcher, silakan gunakan
                        menu
                    </span>
                    <Link
                        href="/feedback"
                        class="text-primary font-bold underline hover:text-primary/80"
                        >Feedback & Reports</Link
                    >.
                </div>
            </div>

            <!-- PINNED MESSAGE BANNER (IF ANY) -->
            <div
                v-if="pinnedMessage"
                class="bg-warning/15 border-b border-warning/40 px-3.5 py-2.5 flex items-start space-x-2 text-xs text-foreground shrink-0"
            >
                <Pin class="w-3.5 h-3.5 text-warning shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <div
                        class="font-bold text-xs text-warning uppercase font-mono"
                    >
                        Disematkan oleh Dispatch Admin:
                    </div>
                    <div class="text-xs truncate text-foreground">
                        {{ pinnedMessage.message }}
                    </div>
                </div>
            </div>

            <!-- MESSAGES CONTAINER -->
            <div
                ref="messagesContainer"
                class="flex-1 p-4 space-y-3.5 overflow-y-auto min-h-[280px] max-h-[380px] scrollbar-thin"
            >
                <!-- Empty State -->
                <div
                    v-if="messages.length === 0"
                    class="py-12 text-center text-muted-foreground font-mono text-xs"
                >
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
                        class="w-7 h-7 rounded-xl bg-muted border border-border object-cover shrink-0 mt-0.5"
                        alt=""
                    />

                    <!-- Content Bubble -->
                    <div class="flex-1 min-w-0">
                        <div
                            class="flex items-center justify-between mb-0.5 gap-2"
                        >
                            <div class="flex items-center space-x-1.5 truncate">
                                <span
                                    class="text-xs font-bold text-foreground truncate"
                                    >{{ msg.user?.name || "Member" }}</span
                                >

                                <!-- Role Badges -->
                                <a
                                    v-if="msg.user?.officer"
                                    :href="
                                        msg.user?.officer?.bio_profile?.slug
                                            ? '/@' +
                                              msg.user.officer.bio_profile.slug
                                            : '#'
                                    "
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-sm text-xs font-extrabold font-mono uppercase bg-info/15 text-info border border-info/40 hover:bg-info/25 transition shadow-xs"
                                    :title="
                                        'Verified Officer: ' +
                                        msg.user.officer.officer_name +
                                        ' (' +
                                        msg.user.officer.callsign +
                                        ')'
                                    "
                                >
                                    <ShieldCheck class="w-3 h-3" />
                                    <span
                                        >{{
                                            msg.user.officer.department ||
                                            "POLICE"
                                        }}
                                        #{{ msg.user.officer.callsign }}</span
                                    >
                                </a>
                                <Badge
                                    v-else-if="msg.user?.role === 'admin'"
                                    variant="outline"
                                    class="px-1.5 py-0 text-xs font-extrabold font-mono uppercase bg-warning/20 text-warning border-warning/40"
                                >
                                    DISPATCH ADMIN
                                </Badge>
                                <Badge
                                    v-else-if="msg.user?.role === 'officer'"
                                    variant="outline"
                                    class="px-1.5 py-0 text-xs font-extrabold font-mono uppercase bg-info/15 text-info border-info/40"
                                >
                                    OFFICER
                                </Badge>
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="px-1.5 py-0 text-xs font-bold font-mono uppercase bg-primary/15 text-primary border-primary/30"
                                >
                                    MEMBER
                                </Badge>
                            </div>

                            <span
                                class="text-xs text-muted-foreground font-mono shrink-0"
                                >{{ formatTime(msg.created_at) }}</span
                            >
                        </div>

                        <!-- Message Text -->
                        <div
                            class="bg-card border border-border rounded-2xl rounded-tl-xs px-3 py-2 text-xs text-foreground leading-relaxed wrap-break-word relative shadow-xs"
                        >
                            {{ msg.message }}
                        </div>

                        <!-- Admin Message Tools -->
                        <div
                            v-if="currentUser?.role === 'admin'"
                            class="opacity-0 group-hover:opacity-100 transition flex items-center space-x-2 mt-1 text-xs font-mono text-muted-foreground"
                        >
                            <button
                                type="button"
                                @click="togglePinMessage(msg.id)"
                                class="hover:text-warning cursor-pointer"
                            >
                                {{ msg.is_pinned ? "Unpin" : "Pin" }}
                            </button>
                            <span>•</span>
                            <button
                                type="button"
                                @click="deleteMessage(msg.id)"
                                class="hover:text-destructive cursor-pointer"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INPUT BOX AREA -->
            <div
                class="p-3 bg-background border-t border-border space-y-2 shrink-0"
            >
                <div
                    v-if="errorMessage"
                    class="text-xs text-destructive font-mono px-1 flex items-center gap-1"
                >
                    <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Input Form (Authenticated User) -->
                <form
                    v-if="currentUser"
                    @submit.prevent="sendMessage"
                    class="flex items-center gap-2"
                >
                    <Input
                        v-model="newMessageText"
                        type="text"
                        placeholder="Tulis pesan obrolan..."
                        maxlength="500"
                        class="flex-1 h-9 text-xs font-sans"
                    />
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="isSending || !newMessageText.trim()"
                        class="h-9 px-3.5 font-bold text-xs shrink-0"
                    >
                        {{ isSending ? "..." : "Kirim" }}
                    </Button>
                </form>

                <!-- Guest Notice -->
                <div v-else class="text-center py-1">
                    <Link
                        href="/login"
                        class="text-xs text-primary font-bold hover:underline flex items-center justify-center space-x-1"
                    >
                        <span>Sign In untuk Ikut Obrolan Komunitas</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </div>

        <AlertDialog
            :open="!!pendingDeleteId"
            @update:open="(val) => { if (!val) pendingDeleteId = null; }"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Pesan Chat?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Hapus pesan ini dari chat komunitas? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="pendingDeleteId = null">
                        Batal
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="confirmDeleteMessage"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
