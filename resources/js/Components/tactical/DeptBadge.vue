<script setup>
import { computed } from "vue";
import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";

const props = defineProps({
    dept: {
        type: String,
        default: "",
    },
    department: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const normalizedDept = computed(() => (props.dept || props.department || "").trim().toUpperCase());

const deptClasses = computed(() => {
    switch (normalizedDept.value) {
        case "LSPD":
            return "border-dept-lspd/30 text-dept-lspd";
        case "BCSO":
        case "LSCSD":
            return "border-dept-bcso/30 text-dept-bcso";
        case "SASP":
            return "border-dept-sasp/30 text-dept-sasp";
        case "SAPR":
            return "border-dept-sapr/30 text-dept-sapr";
        default:
            return "border-border text-muted-foreground";
    }
});
</script>

<template>
    <Badge
        variant="outline"
        :class="
            cn(
                'inline-flex h-5 items-center gap-1 rounded-sm px-1.5 py-0 font-mono text-xs font-semibold uppercase leading-none tracking-normal bg-card/60',
                deptClasses,
                props.class,
            )
        "
    >
        <span class="size-1.5 rounded-full shrink-0 bg-current opacity-90" aria-hidden="true" />
        <slot>{{ label || normalizedDept || "UNKNOWN" }}</slot>
    </Badge>
</template>

