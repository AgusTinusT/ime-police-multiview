<script setup>
import { computed } from "vue";
import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";

const props = defineProps({
    dept: {
        type: String,
        default: "",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const normalizedDept = computed(() => (props.dept || "").trim().toUpperCase());

const deptClasses = computed(() => {
    switch (normalizedDept.value) {
        case "LSPD":
            return "border-dept-lspd/40 text-dept-lspd bg-dept-lspd/10";
        case "BCSO":
        case "LSCSD":
            return "border-dept-bcso/40 text-dept-bcso bg-dept-bcso/10";
        case "SASP":
            return "border-dept-sasp/40 text-dept-sasp bg-dept-sasp/10";
        case "SAPR":
            return "border-dept-sapr/40 text-dept-sapr bg-dept-sapr/10";
        default:
            return "border-border text-muted-foreground bg-muted/40";
    }
});
</script>

<template>
    <Badge
        variant="outline"
        :class="cn('font-mono text-xs font-semibold uppercase tracking-wider', deptClasses, props.class)"
    >
        <slot>{{ normalizedDept || "UNKNOWN" }}</slot>
    </Badge>
</template>
