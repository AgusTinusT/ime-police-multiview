<script setup>
import { computed } from "vue";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from "@/Components/ui/sheet";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { DeptBadge, LiveBadge, OfficerAvatar } from "@/Components/tactical";
import {
    Award,
    Clock,
    Pencil,
    Trash2,
    ExternalLink,
    UserCheck,
    UserX,
    Link2,
    ShieldAlert,
    Ban,
    Radio,
    Lock,
    Unlock,
} from "@lucide/vue";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    officer: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits([
    "update:open",
    "edit",
    "delete",
    "duty",
    "toggle-hashtag",
    "bind",
    "slug",
    "unbind",
    "toggle-suspend",
    "toggle-ban",
]);

// Live comes from the eager-loaded ActiveStream; duty_status is only a label.
const isLive = computed(() => Boolean(props.officer?.active_stream));

const formatDutyHours = (minutes) => {
    const min = parseInt(minutes || 0, 10);
    const hrs = (min / 60).toFixed(1);
    return `${hrs} Jam (${min} mnt)`;
};

const calcDailyAvg = (minutes) => {
    const dayOfMonth = new Date().getDate() || 1;
    const m = parseInt(minutes || 0, 10);
    const avgMinutes = Math.round(m / dayOfMonth);
    const h = Math.floor(avgMinutes / 60);
    const remM = avgMinutes % 60;
    return `${h}j ${remM}m / hari`;
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent
            side="right"
            class="w-full sm:max-w-md flex flex-col justify-between p-0 overflow-hidden"
        >
            <SheetHeader class="p-5 border-b border-border bg-card shrink-0 text-left">
                <div class="flex items-center justify-between gap-2 pr-6">
                    <SheetTitle class="text-sm font-mono font-bold tracking-wider uppercase text-primary flex items-center gap-2">
                        <Radio class="size-4 text-primary" />
                        <span>Profil Personil Komando</span>
                    </SheetTitle>
                    <Badge
                        v-if="officer?.is_active"
                        variant="success"
                        class="text-xs font-mono font-bold"
                    >
                        ACTIVE
                    </Badge>
                    <Badge
                        v-else
                        variant="secondary"
                        class="text-xs font-mono font-bold"
                    >
                        DISABLED
                    </Badge>
                </div>
                <SheetDescription class="text-xs text-muted-foreground font-mono">
                    Detail personil, waktu patroli, integrasi akun, dan aksi manajemen.
                </SheetDescription>
            </SheetHeader>

            <div v-if="officer" class="p-5 overflow-y-auto space-y-6 flex-1 text-xs">
                <!-- 1. IDENTITAS -->
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <OfficerAvatar
                            :src="officer.avatar_url || `https://api.dicebear.com/7.x/bottts/svg?seed=${officer.callsign}`"
                            :name="officer.officer_name"
                            :callsign="officer.callsign"
                            :department="officer.agency?.agency_code || officer.department"
                            size="xl"
                            class="shrink-0"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <LiveBadge v-if="isLive" />
                                <Badge v-else variant="secondary" class="font-mono text-xs">
                                    {{ officer.duty_status || "10-7 (Off-Duty)" }}
                                </Badge>
                                <DeptBadge :department="officer.agency?.agency_code || officer.department" />
                            </div>
                            <h3 class="text-base font-bold text-foreground truncate">
                                {{ officer.officer_name }}
                            </h3>
                            <div class="text-xs text-muted-foreground font-mono">
                                {{ officer.rank_relation?.rank_title || officer.rank || "Officer" }}
                                <span v-if="officer.division" class="text-primary font-semibold">
                                    · {{ officer.division.division_code || officer.division.division_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 p-3 rounded-md bg-muted/40 font-mono text-xs border border-border">
                        <div>
                            <span class="text-muted-foreground block">Callsign</span>
                            <span class="text-primary font-bold text-sm">{{ officer.callsign }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block">Badge Number</span>
                            <span class="text-foreground font-bold text-sm">{{ officer.badge_number || "#000" }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block">Agency</span>
                            <span class="text-foreground font-medium truncate block">
                                {{ officer.agency?.agency_name || officer.department }}
                            </span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block">Streamer</span>
                            <span class="text-foreground font-medium truncate block">
                                {{ officer.streamer_name || "-" }}
                            </span>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- 2. DUTY PATROLI -->
                <div class="space-y-3">
                    <div class="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                        <Clock class="size-3.5 text-warning" />
                        <span>Duty Patroli & Hashtag Rule</span>
                    </div>

                    <div class="p-3 rounded-md bg-muted/40 border border-border space-y-3 font-mono">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <span class="text-muted-foreground text-xs block">Akumulasi Bulan Ini</span>
                                <span class="text-warning font-bold text-sm">
                                    {{ formatDutyHours(officer.monthly_duty_minutes) }}
                                </span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="emit('duty', officer)"
                                class="h-7 text-xs font-mono gap-1 text-warning border-warning/40 hover:bg-warning/10"
                            >
                                <Pencil class="size-3 shrink-0" />
                                <span>Atur Jam Duty</span>
                            </Button>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-border">
                            <span class="text-muted-foreground">Rata-rata Harian:</span>
                            <span class="text-foreground font-semibold">
                                {{ calcDailyAvg(officer.monthly_duty_minutes) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-border gap-2">
                            <div>
                                <span class="text-muted-foreground block">Aturan Hashtag Patroli</span>
                                <span
                                    class="font-semibold text-xs"
                                    :class="officer.bypass_hashtag_check ? 'text-info' : 'text-foreground'"
                                >
                                    {{ officer.bypass_hashtag_check ? "🔓 Bypass Hashtag: ON" : "🔒 Hashtag Check: Strict" }}
                                </span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="emit('toggle-hashtag', officer)"
                                class="h-7 text-xs font-mono font-bold gap-1"
                                :class="officer.bypass_hashtag_check ? 'bg-info/15 text-info border-info/40 hover:bg-info/25' : 'text-muted-foreground'"
                            >
                                <Unlock v-if="officer.bypass_hashtag_check" class="size-3 shrink-0" />
                                <Lock v-else class="size-3 shrink-0" />
                                <span>{{ officer.bypass_hashtag_check ? "Nonaktifkan" : "Aktifkan Bypass" }}</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- 3. SERTIFIKASI -->
                <div class="space-y-3">
                    <div class="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                        <Award class="size-3.5 text-primary" />
                        <span>Sertifikasi Personil</span>
                    </div>

                    <div
                        v-if="officer.certifications && officer.certifications.length > 0"
                        class="flex flex-wrap gap-1.5"
                    >
                        <Badge
                            v-for="cert in officer.certifications"
                            :key="cert.id"
                            variant="outline"
                            class="text-xs font-mono gap-1"
                        >
                            <Award class="size-3 text-primary shrink-0" />
                            <span>{{ cert.cert_type }}</span>
                        </Badge>
                    </div>
                    <p v-else class="text-xs text-muted-foreground italic font-mono">
                        Tidak ada sertifikasi khusus tercatat.
                    </p>
                </div>

                <Separator />

                <!-- 4. YOUTUBE & AKUN -->
                <div class="space-y-3">
                    <div class="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                        <Link2 class="size-3.5 text-success" />
                        <span>YouTube & Integrasi Akun</span>
                    </div>

                    <div class="p-3 rounded-md bg-muted/40 border border-border space-y-3 font-mono text-xs">
                        <!-- YouTube Handle -->
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-muted-foreground">YouTube Handle:</span>
                            <a
                                :href="`https://www.youtube.com/${officer.handle && officer.handle.startsWith('@') ? officer.handle : '@' + (officer.handle || '')}`"
                                target="_blank"
                                class="text-primary hover:underline font-semibold inline-flex items-center gap-1"
                            >
                                <span>{{ officer.handle || "-" }}</span>
                                <ExternalLink class="size-3 shrink-0" />
                            </a>
                        </div>

                        <!-- Akun Terikat -->
                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-border">
                            <span class="text-muted-foreground">Akun Terikat:</span>
                            <div v-if="officer.user" class="flex items-center gap-1.5 flex-wrap justify-end">
                                <Badge variant="info" class="text-xs font-mono gap-1">
                                    <UserCheck class="size-3 shrink-0" />
                                    <span class="truncate max-w-[120px]">{{ officer.user.name }}</span>
                                </Badge>
                                <Badge v-if="officer.user.status === 'banned'" variant="destructive" class="text-xs font-bold">
                                    BANNED
                                </Badge>
                            </div>
                            <Badge v-else variant="warning" class="text-xs font-mono">
                                Unclaimed
                            </Badge>
                        </div>

                        <!-- Bio-Link Publik -->
                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-border">
                            <span class="text-muted-foreground">Bio-Link:</span>
                            <div class="flex items-center gap-1.5 flex-wrap justify-end">
                                <a
                                    :href="`/@${officer.bio_profile?.slug || (officer.handle || '').replace('@', '')}`"
                                    target="_blank"
                                    class="text-warning hover:underline font-bold inline-flex items-center gap-0.5"
                                >
                                    <span>/@{{ officer.bio_profile?.slug || (officer.handle || '').replace('@', '') }}</span>
                                    <ExternalLink class="size-3 shrink-0" />
                                </a>
                                <Badge v-if="officer.bio_profile?.is_suspended" variant="destructive" class="text-xs font-bold">
                                    SUSPENDED
                                </Badge>
                            </div>
                        </div>

                        <!-- Quick Actions for Account -->
                        <div class="pt-2 border-t border-border flex flex-wrap gap-2">
                            <Button
                                v-if="!officer.user"
                                variant="outline"
                                size="sm"
                                @click="emit('bind', officer)"
                                class="h-7 text-xs font-mono text-primary border-primary/40 hover:bg-primary/10 gap-1.5"
                            >
                                <UserCheck class="size-3.5 shrink-0" />
                                <span>Bind User</span>
                            </Button>
                            <template v-else>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="emit('slug', officer)"
                                    class="h-7 text-xs font-mono gap-1.5"
                                >
                                    <Link2 class="size-3.5 shrink-0" />
                                    <span>Ubah Slug</span>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="emit('unbind', officer)"
                                    class="h-7 text-xs font-mono text-warning border-warning/40 hover:bg-warning/10 gap-1.5"
                                >
                                    <UserX class="size-3.5 shrink-0" />
                                    <span>Unbind User</span>
                                </Button>
                                <Button
                                    v-if="officer.bio_profile"
                                    variant="outline"
                                    size="sm"
                                    @click="emit('toggle-suspend', officer)"
                                    class="h-7 text-xs font-mono text-warning border-warning/40 hover:bg-warning/10 gap-1.5"
                                >
                                    <ShieldAlert class="size-3.5 shrink-0" />
                                    <span>{{ officer.bio_profile.is_suspended ? "Unsuspend Bio" : "Suspend Bio" }}</span>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="emit('toggle-ban', officer)"
                                    class="h-7 text-xs font-mono text-destructive border-destructive/40 hover:bg-destructive/10 gap-1.5"
                                >
                                    <Ban class="size-3.5 shrink-0" />
                                    <span>{{ officer.user.status === "banned" ? "Unban User" : "Ban User" }}</span>
                                </Button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <SheetFooter class="p-4 border-t border-border bg-card shrink-0 flex flex-row items-center justify-end gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="emit('edit', officer)"
                    class="gap-1.5 font-mono text-xs"
                >
                    <Pencil class="size-3.5" />
                    <span>Edit Officer</span>
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="emit('delete', officer)"
                    class="gap-1.5 font-mono text-xs"
                >
                    <Trash2 class="size-3.5" />
                    <span>Hapus</span>
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>

