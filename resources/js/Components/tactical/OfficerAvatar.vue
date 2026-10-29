<script setup>
import { computed } from "vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { cn } from "@/lib/utils";

const props = defineProps({
    src: {
        type: String,
        default: "",
    },
    name: {
        type: String,
        default: "",
    },
    live: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: "md", // 'sm' | 'md' | 'lg'
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const initials = computed(() => {
    const clean = (props.name || "").trim();
    if (!clean) return "OP";
    const parts = clean.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
});

const sizeClass = computed(() => {
    if (props.size === "sm") return "size-8 text-xs";
    if (props.size === "lg") return "size-14 text-base";
    return "size-10 text-sm";
});
</script>

<template>
    <Avatar
        :class="
            cn(
                sizeClass,
                live && 'ring-2 ring-live ring-offset-2 ring-offset-background',
                props.class,
            )
        "
    >
        <AvatarImage v-if="src" :src="src" :alt="name || 'Officer avatar'" />
        <AvatarFallback class="font-mono font-semibold text-foreground">
            {{ initials }}
        </AvatarFallback>
    </Avatar>
</template>
