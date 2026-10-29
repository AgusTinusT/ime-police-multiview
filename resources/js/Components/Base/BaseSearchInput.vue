<script setup>
// Deprecated: gunakan @/Components/ui/input (dihapus di MIG-10)
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Search, X } from "@lucide/vue";

defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Cari...",
    },
    showEsc: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["update:modelValue", "clear"]);

const handleUpdate = (val) => {
    emit("update:modelValue", val);
};

const handleClear = () => {
    emit("update:modelValue", "");
    emit("clear");
};
</script>

<template>
    <div class="relative flex items-center">
        <Search class="pointer-events-none absolute left-3 size-4 text-primary" aria-hidden="true" />
        <Input
            :model-value="modelValue"
            :placeholder="placeholder"
            class="bg-card pl-9 pr-16"
            @update:model-value="handleUpdate"
        />
        <div class="absolute right-2 flex items-center gap-1">
            <Button
                v-if="modelValue"
                type="button"
                variant="ghost"
                size="icon"
                class="size-6 text-muted-foreground hover:text-foreground"
                aria-label="Bersihkan pencarian"
                @click="handleClear"
            >
                <X class="size-3.5" />
            </Button>
            <kbd
                v-if="showEsc"
                class="pointer-events-none hidden rounded-sm border border-border bg-background px-1.5 py-0.5 font-mono text-xs font-semibold text-muted-foreground sm:inline-block"
            >
                ESC
            </kbd>
        </div>
    </div>
</template>
