<script setup>
import { computed } from "vue";
import { Link2 } from "@lucide/vue";
import { PLATFORM_ICONS } from "./platformIcons";

const props = defineProps({
    platform: {
        type: String,
        required: true,
    },
});

const icon = computed(() => {
    if (!props.platform) {
        return null;
    }
    const key = props.platform.trim().toLowerCase();
    if (key === "custom") {
        return null;
    }
    return PLATFORM_ICONS[key] || null;
});
</script>

<template>
    <svg
        v-if="icon"
        :viewBox="icon.viewBox || '0 0 24 24'"
        fill="currentColor"
        aria-hidden="true"
        class="shrink-0"
    >
        <path :d="icon.path" fill-rule="evenodd" clip-rule="evenodd" />
    </svg>
    <Link2
        v-else
        aria-hidden="true"
        class="shrink-0"
    />
</template>
