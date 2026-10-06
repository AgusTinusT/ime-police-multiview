<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { releasesId } from "@/Data/releases.id.js";
import { releasesEn } from "@/Data/releases.en.js";

// Branding & Icons
import iconFeedback from "@/Components/Icons/report-svgrepo-com.svg";
import { defineProps } from "vue";

const { t, locale } = useI18n();

const props = defineProps({
    appVersion: {
        type: String,
        default: "2.5.0-Pro",
    },
});

// Category Filter State
const selectedCategory = ref("ALL");

const categories = computed(() => [
    { id: "ALL", label: t("updates.filter_all") },
    { id: "FEATURE", label: t("updates.filter_feature") },
    { id: "BUGFIX", label: t("updates.filter_bugfix") },
    { id: "PERFORMANCE", label: t("updates.filter_performance") },
    { id: "UIUX", label: t("updates.filter_uiux") },
]);

// Release History Dataset (Multi-language Data Modules)
const releases = computed(() =>
    locale.value === "en" ? releasesEn : releasesId,
);

// Filtered Releases computed
const filteredReleases = computed(() => {
    if (selectedCategory.value === "ALL") {
        return releases.value;
    }
    return releases.value
        .map((rel) => {
            const filtered = rel.changes.filter(
                (c) => c.type === selectedCategory.value,
            );
            if (filtered.length === 0) return null;
            return {
                ...rel,
                changes: filtered,
            };
        })
        .filter(Boolean);
});
</script>

<template>
    <Head title="System Updates & Release Notes — IME RP Police Multiview" />

    <TacticalLayout activeTab="updates">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- HERO HEADER BANNER -->
            <div class="tactical-card p-6 sm:p-8">
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-6"
                >
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span class="tactical-badge">
                                {{ $t("updates.title") }}
                            </span>
                        </div>

                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight leading-tight"
                        >
                            {{ $t("updates.title") }}
                        </h1>

                        <p
                            class="text-xs sm:text-sm text-slate-400 leading-relaxed"
                        >
                            {{ $t("updates.subtitle") }}
                        </p>
                    </div>

                    <!-- Platform Version Card -->
                    <div
                        class="bg-slate-950 border border-slate-800 rounded-xl p-4 shrink-0 flex flex-col items-center justify-center text-center gap-1 md:w-48"
                    >
                        <div
                            class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest"
                        >
                            VERSI SAAT INI
                        </div>
                        <div
                            class="text-2xl font-black font-mono text-blue-400 tracking-wider"
                        >
                            {{ appVersion }}
                        </div>
                        <div
                            class="text-[10px] text-slate-500 font-mono mt-0.5"
                        >
                            Aktif Seluruh Unit
                        </div>
                    </div>
                </div>
            </div>

            <!-- METRICS SUMMARY ROW -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >TOTAL RILIS</span
                    >
                    <span
                        class="text-lg font-bold font-mono text-slate-100 mt-1"
                        >4 Versi</span
                    >
                </div>
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >VERSI UTAMA</span
                    >
                    <span class="text-lg font-bold font-mono text-blue-400 mt-1"
                        >v2.4.0-Pro</span
                    >
                </div>
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >HASHTAG RULE</span
                    >
                    <span
                        class="text-lg font-bold font-mono text-slate-200 mt-1"
                        >Wajib #imepolice</span
                    >
                </div>
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >UPDATE TERAKHIR</span
                    >
                    <span
                        class="text-lg font-bold font-mono text-slate-300 mt-1"
                        >17 Sep 2026</span
                    >
                </div>
            </div>

            <!-- CATEGORY FILTER TABS -->
            <div
                class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none"
            >
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = cat.id"
                    :class="[
                        'px-3 py-1.5 rounded-md text-xs font-bold font-mono transition shrink-0 border',
                        selectedCategory === cat.id
                            ? 'bg-blue-600 text-white border-blue-500'
                            : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800 hover:bg-slate-800',
                    ]"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- RELEASE TIMELINE SECTION -->
            <div class="space-y-6">
                <div
                    v-for="rel in filteredReleases"
                    :key="rel.version"
                    class="relative pl-6 sm:pl-8 border-l border-slate-800 space-y-3 group"
                >
                    <!-- Timeline Marker Dot -->
                    <div
                        class="absolute -left-[7px] top-2 w-3.5 h-3.5 rounded-full border transition duration-300"
                        :class="
                            rel.isLatest
                                ? 'bg-blue-500 border-blue-400'
                                : 'bg-slate-800 border-slate-700 group-hover:border-slate-500'
                        "
                    ></div>

                    <!-- Release Header Card -->
                    <div
                        class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-3.5"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-800"
                        >
                            <div class="flex items-center space-x-2.5">
                                <span
                                    class="text-lg font-bold font-mono text-slate-100 tracking-tight"
                                >
                                    {{ rel.version }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold uppercase border"
                                    :class="
                                        rel.isLatest
                                            ? 'bg-blue-950 text-blue-400 border-blue-900/60'
                                            : 'bg-slate-950 text-slate-400 border-slate-800'
                                    "
                                >
                                    {{ rel.badge }}
                                </span>
                            </div>
                            <span
                                class="text-xs font-mono text-slate-400 font-medium"
                            >
                                {{ rel.date }}
                            </span>
                        </div>

                        <!-- Summary Paragraph -->
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed font-sans"
                        >
                            {{ rel.summary }}
                        </p>

                        <!-- Changes List -->
                        <div class="grid grid-cols-1 gap-2.5 pt-1">
                            <div
                                v-for="(change, cIdx) in rel.changes"
                                :key="cIdx"
                                class="bg-slate-950 border border-slate-800 rounded-md p-3 space-y-1"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-200"
                                    >
                                        {{ change.title }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold border shrink-0',
                                            change.tagColor,
                                        ]"
                                    >
                                        {{ change.tag }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs text-slate-400 leading-relaxed"
                                >
                                    {{ change.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredReleases.length === 0"
                    class="text-center py-10 bg-slate-900 rounded-xl border border-slate-800 p-6 space-y-2"
                >
                    <div class="text-xs font-mono text-slate-400">
                        Tidak ada catatan untuk kategori ini.
                    </div>
                </div>
            </div>

            <!-- FOOTER CALLOUT BANNER -->
            <div
                class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="space-y-0.5">
                    <h4 class="text-xs font-bold text-slate-200">
                        {{ $t("updates.footer_suggestion") }}
                    </h4>
                </div>
                <Link
                    href="/feedback"
                    class="px-3.5 py-2 rounded-md bg-slate-950 hover:bg-slate-800 text-slate-200 text-xs font-bold transition shrink-0 border border-slate-800 flex items-center justify-center gap-2"
                >
                    <img
                        :src="iconFeedback"
                        class="w-3.5 h-3.5 invert opacity-80"
                        alt=""
                    />
                    <span>{{ $t("updates.footer_btn_report") }} →</span>
                </Link>
            </div>
        </div>
    </TacticalLayout>
</template>
