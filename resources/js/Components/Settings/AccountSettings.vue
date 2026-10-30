<script setup>
import { usePage } from "@inertiajs/vue3";
import { ShieldCheck, User } from "@lucide/vue";
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

const user = usePage().props.auth.user;
</script>

<template>
    <div class="space-y-6">
        <!-- User Header Summary Card -->
        <Card>
            <CardContent class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <OfficerAvatar
                        :name="user.name || 'User'"
                        size="lg"
                    />

                    <div class="space-y-1">
                        <div class="flex items-center flex-wrap gap-2">
                            <h2 class="text-xl font-bold text-foreground leading-tight">
                                {{ user.name }}
                            </h2>
                            <Badge
                                variant="outline"
                                :class="
                                    user.role === 'admin'
                                        ? 'bg-warning/15 text-warning border-warning/30 uppercase tracking-wider font-bold gap-1.5'
                                        : 'bg-success/15 text-success border-success/30 uppercase tracking-wider font-bold gap-1.5'
                                "
                            >
                                <ShieldCheck v-if="user.role === 'admin'" class="size-3.5 shrink-0" />
                                <User v-else class="size-3.5 shrink-0" />
                                <span>{{ user.role === "admin" ? "Admin Dispatcher" : "Member User" }}</span>
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground font-mono">
                            {{ user.email }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs font-mono text-muted-foreground border-t md:border-t-0 md:border-l border-border pt-3 md:pt-0 md:pl-6">
                    <div class="flex flex-col">
                        <span class="text-xs uppercase text-muted-foreground font-semibold">Status Hak Akses</span>
                        <span class="text-foreground font-bold">
                            {{ user.role === "admin" ? "Full Command Portal" : "Community Member" }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Profile Information Form -->
        <Card>
            <CardContent class="p-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </CardContent>
        </Card>

        <!-- Update Password Form -->
        <Card>
            <CardContent class="p-6">
                <UpdatePasswordForm class="max-w-2xl" />
            </CardContent>
        </Card>

        <!-- Delete User Account Form -->
        <Card class="border-destructive/40">
            <CardContent class="p-6">
                <DeleteUserForm class="max-w-2xl" />
            </CardContent>
        </Card>
    </div>
</template>

