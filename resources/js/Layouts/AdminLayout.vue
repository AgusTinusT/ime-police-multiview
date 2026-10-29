<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Toaster } from "@/Components/ui/sonner";
import {
    Shield,
    Building2,
    Award,
    Megaphone,
    Users,
    FileCheck,
    Lock,
    GitPullRequest,
    Images,
    ArrowLeft,
    ShieldAlert,
} from "@lucide/vue";

const props = defineProps({
    currentTab: {
        type: String,
        default: "officers",
    },
    title: {
        type: String,
        default: "",
    },
});

defineEmits(["update:currentTab"]);

const navItems = [
    { id: "officers", label: "Officers Roster", icon: Shield },
    { id: "agencies", label: "Agencies & Ranks Structure", icon: Building2 },
    { id: "certifications", label: "Tactical Certifications", icon: Award },
    { id: "announcements", label: "Promotions & Alerts", icon: Megaphone },
    { id: "users", label: "Member Accounts", icon: Users },
    { id: "claims", label: "Klaim Perwira", icon: FileCheck },
    { id: "moderation", label: "Moderasi Bio-Link", icon: Lock },
    { id: "requests", label: "Pengajuan Mutasi", icon: GitPullRequest },
    { id: "hero", label: "Hero Slides", icon: Images },
];

const currentSectionTitle = computed(() => {
    if (props.title) return props.title;
    const current = navItems.find((item) => item.id === props.currentTab);
    return current ? current.label : "Tactical Admin Hub";
});
</script>

<template>
    <SidebarProvider>
        <Sidebar collapsible="icon">
            <SidebarHeader class="border-b border-sidebar-border p-3">
                <div class="flex items-center gap-2.5 px-1">
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground"
                    >
                        <ShieldAlert class="size-4" />
                    </div>
                    <div
                        class="flex flex-col min-w-0 group-data-[collapsible=icon]:hidden"
                    >
                        <span
                            class="text-xs font-semibold leading-tight text-sidebar-foreground truncate"
                        >
                            Tactical Admin
                        </span>
                        <span
                            class="text-xs font-mono text-muted-foreground truncate"
                        >
                            Command Hub
                        </span>
                    </div>
                </div>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem
                                v-for="item in navItems"
                                :key="item.id"
                            >
                                <SidebarMenuButton
                                    :is-active="currentTab === item.id"
                                    :tooltip="item.label"
                                    @click="$emit('update:currentTab', item.id)"
                                >
                                    <component
                                        :is="item.icon"
                                        class="size-4 shrink-0"
                                    />
                                    <span>{{ item.label }}</span>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarRail />
        </Sidebar>

        <SidebarInset class="min-w-0">
            <!-- Admin Top Header Bar -->
            <header
                class="sticky top-0 z-10 flex h-14 shrink-0 items-center justify-between border-b border-border bg-background/95 px-4 backdrop-blur-sm"
            >
                <div class="flex items-center gap-3">
                    <SidebarTrigger />
                    <div class="h-4 w-px bg-border" />
                    <slot name="header-title">
                        <h1
                            class="text-sm font-semibold text-foreground tracking-tight"
                        >
                            {{ currentSectionTitle }}
                        </h1>
                    </slot>
                </div>
                <div class="flex items-center gap-2">
                    <slot name="header-actions" />
                    <Button
                        as-child
                        variant="ghost"
                        size="sm"
                        class="gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                    >
                        <Link href="/">
                            <ArrowLeft class="size-3.5" />
                            <span>Kembali ke situs</span>
                        </Link>
                    </Button>
                </div>
            </header>

            <!-- Main Content Area -->
            <div class="flex-1 w-full min-w-0 py-6">
                <slot />
            </div>

            <Toaster />
        </SidebarInset>
    </SidebarProvider>
</template>
