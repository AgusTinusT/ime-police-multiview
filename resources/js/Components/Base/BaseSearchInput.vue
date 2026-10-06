<script setup>
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

const handleInput = (e) => {
    emit("update:modelValue", e.target.value);
};

const handleClear = () => {
    emit("update:modelValue", "");
    emit("clear");
};
</script>

<template>
    <div
        class="relative flex items-center bg-tactical-panel border border-tactical-border focus-within:border-blue-500 rounded-lg p-1.5 transition duration-200"
    >
        <div class="pl-3 text-slate-400 shrink-0">
            <svg
                class="w-4 h-4 text-blue-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>
        </div>
        <input
            :value="modelValue"
            @input="handleInput"
            type="text"
            :placeholder="placeholder"
            class="w-full bg-transparent border-0 border-none ring-0 focus:ring-0 focus:outline-none focus:border-none shadow-none px-3 py-1.5 text-xs sm:text-sm font-sans text-slate-100 placeholder:text-tactical-muted"
        />
        <button
            v-if="modelValue"
            @click="handleClear"
            type="button"
            class="p-1 text-slate-400 hover:text-white rounded-md hover:bg-tactical-surface transition text-xs font-mono shrink-0 mr-1"
            title="Bersihkan pencarian"
        >
            ✕
        </button>
        <div
            v-if="showEsc"
            class="hidden sm:flex items-center pr-2.5 pointer-events-none shrink-0"
        >
            <kbd
                class="px-2 py-0.5 text-[10px] font-mono font-semibold text-tactical-muted bg-tactical-base border border-tactical-border rounded"
            >ESC</kbd>
        </div>
    </div>
</template>
