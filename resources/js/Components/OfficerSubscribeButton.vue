<script setup>
import { computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Bell, BellRing } from "@lucide/vue";
import { toast } from "vue-sonner";
import { Button } from "@/Components/ui/button";
import { cn } from "@/lib/utils";
import { useOfficerSubscriptions } from "@/Composables/useOfficerSubscriptions";

const props = defineProps({
    officerId: {
        type: [Number, String],
        required: true,
    },
    officerName: {
        type: String,
        default: "",
    },
    size: {
        type: String,
        default: "sm",
    },
    iconOnly: {
        type: Boolean,
        default: false,
    },
    rounded: {
        type: Boolean,
        default: false,
    },
    class: {
        type: String,
        default: "",
    },
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const { ensureLoaded, isSubscribed, isPending, toggleSubscription } =
    useOfficerSubscriptions();

const subscribed = computed(() => isSubscribed(props.officerId));
const busy = computed(() => isPending(props.officerId));

onMounted(() => {
    if (isAuthenticated.value) {
        ensureLoaded(true);
    }
});

const handleClick = async () => {
    if (!isAuthenticated.value) {
        toast.info("Silakan masuk terlebih dahulu untuk mengikuti notifikasi live perwira.");
        router.visit("/login");
        return;
    }

    await toggleSubscription(props.officerId, props.officerName);
};
</script>

<template>
    <Button
        type="button"
        :variant="subscribed ? 'default' : 'outline'"
        :size="iconOnly ? 'icon-sm' : size"
        :disabled="busy"
        :class="
            cn(
                rounded && 'rounded-full',
                'gap-1.5',
                props.class,
            )
        "
        :title="
            subscribed
                ? 'Berhenti ikuti notifikasi live'
                : 'Ikuti notifikasi live perwira'
        "
        :aria-label="
            subscribed
                ? `Berhenti ikuti notifikasi live ${officerName || 'perwira'}`
                : `Ikuti notifikasi live ${officerName || 'perwira'}`
        "
        :aria-pressed="subscribed"
        @click.stop="handleClick"
    >
        <BellRing v-if="subscribed" class="size-4 shrink-0" />
        <Bell v-else class="size-4 shrink-0" />
        <span v-if="!iconOnly">{{ subscribed ? "Mengikuti" : "Ikuti" }}</span>
    </Button>
</template>
