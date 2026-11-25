<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { CheckCircle2, ShieldCheck, TriangleAlert, User } from "@lucide/vue";
import { Card, CardContent } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { OfficerAvatar } from "@/Components/tactical";
import DeleteUserForm from "@/Pages/Profile/Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm.vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: "",
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roleLabel = computed(() =>
    user.value?.role === "admin" ? "Admin Dispatcher" : "Member",
);
const isEmailVerified = computed(() => !!user.value?.email_verified_at);
</script>

<template>
    <div class="space-y-6">
        <!-- User identity: avatar, name, role, email on one calm row. -->
        <Card>
            <CardContent class="p-6 flex items-center gap-4">
                <OfficerAvatar :name="user.name || 'User'" size="lg" class="shrink-0" />
                <div class="min-w-0 space-y-1.5">
                    <div class="flex items-center flex-wrap gap-2">
                        <h2 class="text-lg font-semibold text-foreground leading-tight">
                            {{ user.name }}
                        </h2>
                        <Badge
                            variant="outline"
                            :class="user.role === 'admin'
                                ? 'bg-warning/10 text-warning border-warning/30 gap-1'
                                : 'bg-muted text-muted-foreground border-border gap-1'"
                        >
                            <ShieldCheck v-if="user.role === 'admin'" class="size-3.5 shrink-0" />
                            <User v-else class="size-3.5 shrink-0" />
                            <span>{{ roleLabel }}</span>
                        </Badge>
                        <Badge
                            v-if="isEmailVerified"
                            variant="success"
                            class="gap-1"
                        >
                            <CheckCircle2 class="size-3.5 shrink-0" />
                            <span>Email Terverifikasi</span>
                        </Badge>
                        <Badge
                            v-else
                            variant="warning"
                            class="gap-1"
                        >
                            <TriangleAlert class="size-3.5 shrink-0" />
                            <span>Belum Verifikasi</span>
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground truncate">
                        {{ user.email }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-6">
                <UpdatePasswordForm class="max-w-2xl" />
            </CardContent>
        </Card>

        <Card class="border-destructive/40">
            <CardContent class="p-6">
                <DeleteUserForm class="max-w-2xl" />
            </CardContent>
        </Card>
    </div>
</template>

