<script setup>
import { computed } from "vue";
import { Button } from "@/Components/ui/button";
import { MessageSquare } from "@lucide/vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    whatsappNumber: {
        type: String,
        default: "6281234567890",
    },
    class: {
        type: [String, Object, Array],
        default: "",
    },
});

const cleanNumber = computed(() => {
    return (props.whatsappNumber || "6281234567890").replace(/[^0-9]/g, "");
});

const whatsappUrl = computed(() => {
    const text = encodeURIComponent("Halo Admin, saya ada ide desain seru untuk bio-link nih!");
    return `https://wa.me/${cleanNumber.value}?text=${text}`;
});
</script>

<template>
    <div
        :class="
            cn(
                'mx-auto max-w-sm rounded-2xl border-2 border-dashed border-border/80 bg-card/60 p-6 text-center backdrop-blur-xs transition hover:border-border',
                props.class
            )
        "
    >
        <img
            src="/images/admin-mager.jpg"
            alt="Admin Mager"
            class="mx-auto mb-3 size-36 rounded-xl object-contain"
            loading="lazy"
        />

        <h3 class="font-heading text-lg font-bold text-foreground">
            Mode Hemat Pikiran 💤
        </h3>

        <p class="mt-1.5 mb-4 text-xs sm:text-sm text-muted-foreground leading-relaxed">
            Admin lagi malas mikir desain. Kalau ada ide seru dan pengen dibuatin, langsung bilang ya!
        </p>

        <Button as-child class="font-mono text-xs font-bold gap-2">
            <a
                :href="whatsappUrl"
                target="_blank"
                rel="noopener noreferrer"
            >
                <MessageSquare class="size-4" />
                <span>Bisikin Ide ke Admin</span>
            </a>
        </Button>
    </div>
</template>

