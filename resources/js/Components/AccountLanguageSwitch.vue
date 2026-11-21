<script setup>
import { Check, Languages } from "@lucide/vue";
import { useI18n } from "vue-i18n";

import { cn } from "@/lib/utils";

// Two-option language picker for the account menu.
const { locale } = useI18n();

const LANGS = [
    { code: "id", short: "ID", label: "Bahasa" },
    { code: "en", short: "EN", label: "English" },
];
</script>

<template>
    <div class="px-1 pb-1 pt-2">
        <p class="mb-2 flex items-center gap-1.5 px-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            <Languages class="size-3.5" /> Bahasa / Language
        </p>
        <div class="grid grid-cols-2 gap-1.5">
            <button
                v-for="lang in LANGS"
                :key="lang.code"
                type="button"
                :aria-pressed="locale === lang.code"
                :class="cn(
                    'flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors',
                    locale === lang.code
                        ? 'border-primary/50 bg-primary/10 text-primary'
                        : 'border-transparent text-foreground/80 hover:bg-foreground/5 hover:text-foreground',
                )"
                @click="locale = lang.code"
            >
                <span class="rounded-sm bg-foreground/10 px-1 font-mono text-xs font-bold">{{ lang.short }}</span>
                <span class="flex-1 text-left">{{ lang.label }}</span>
                <Check v-if="locale === lang.code" class="size-4 shrink-0" />
            </button>
        </div>
    </div>
</template>
