<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconRoster from "@/Components/Icons/doc-svgrepo-com.svg";
import iconFeedback from "@/Components/Icons/report-svgrepo-com.svg";
import iconUrl from "@/Components/Icons/url-checker-svgrepo-com.svg";

const { t, locale } = useI18n();

const toggleLanguage = () => {
    locale.value = locale.value === "id" ? "en" : "id";
};
</script>

<template>
    <footer
        class="mt-auto bg-tactical-base border-t border-tactical-border text-tactical-muted font-sans pt-8 pb-6 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-screen-2xl mx-auto space-y-6">
            <!-- Top Footer Grid: Branding, Quick Navigation & System Status -->
            <div
                class="grid grid-cols-1 md:grid-cols-12 gap-6 pb-6 border-b border-tactical-border"
            >
                <!-- Branding & Community Info (Cols 1-5) -->
                <div class="md:col-span-5 space-y-3">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-10 h-10 rounded-md bg-tactical-panel border border-tactical-border p-1 overflow-hidden shrink-0"
                        >
                            <img
                                :src="logoSaspColor"
                                class="w-full h-full object-contain rounded"
                                alt="SASP Badge"
                            />
                        </div>
                        <div>
                            <span
                                class="text-sm font-black tracking-wider text-blue-400 uppercase block leading-tight"
                                >IME ROLEPLAY</span
                            >
                            <span
                                class="text-xs font-bold tracking-wide text-slate-300 uppercase block leading-tight"
                                >POLICE DUTY</span
                            >
                        </div>
                    </div>
                    <p
                        class="text-xs text-tactical-muted leading-relaxed max-w-md font-mono"
                    >
                        {{ $t("footer.tagline") }}
                    </p>
                </div>

                <!-- Navigation Quick Links (Cols 6-8) -->
                <div class="md:col-span-4 space-y-2">
                    <h4
                        class="text-xs font-mono font-bold uppercase tracking-wider text-blue-400"
                    >
                        Navigasi Platform
                    </h4>
                    <ul class="grid grid-cols-2 gap-2 text-xs font-medium">
                        <li>
                            <Link
                                href="/multiview"
                                class="text-slate-400 hover:text-slate-100 transition flex items-center gap-1.5 py-1"
                            >
                                <img
                                    :src="iconFocus"
                                    class="w-3.5 h-3.5 invert opacity-80"
                                    alt=""
                                />
                                <span>{{ $t("nav.multiview") }}</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/officers"
                                class="text-slate-400 hover:text-slate-100 transition flex items-center gap-1.5 py-1"
                            >
                                <img
                                    :src="iconRoster"
                                    class="w-3.5 h-3.5 invert opacity-80"
                                    alt=""
                                />
                                <span>{{ $t("nav.directory") }}</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/faq"
                                class="text-slate-400 hover:text-slate-100 transition flex items-center gap-1.5 py-1"
                            >
                                <img
                                    :src="iconRadio"
                                    class="w-3.5 h-3.5 invert opacity-80"
                                    alt=""
                                />
                                <span>{{ $t("nav.faq") }}</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/about"
                                class="text-slate-400 hover:text-slate-100 transition flex items-center gap-1.5 py-1"
                            >
                                <img
                                    :src="iconUrl"
                                    class="w-3.5 h-3.5 invert opacity-80"
                                    alt=""
                                />
                                <span>{{ $t("nav.about") }}</span>
                            </Link>
                        </li>
                        <li class="col-span-2">
                            <Link
                                href="/feedback"
                                class="text-slate-400 hover:text-slate-100 transition flex items-center gap-1.5 py-1"
                            >
                                <img
                                    :src="iconFeedback"
                                    class="w-3.5 h-3.5 invert opacity-90"
                                    alt=""
                                />
                                <span>{{ $t("nav.feedback") }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Legal / Language Switcher (Cols 9-12) -->
                <div class="md:col-span-3 space-y-3">
                    <h4
                        class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400"
                    >
                        Bahasa / Language
                    </h4>
                    <!-- Language Switcher Button -->
                    <button
                        @click="toggleLanguage"
                        class="btn-tactical-secondary text-xs"
                    >
                        <span class="text-sm">{{ locale === 'id' ? '🇮🇩' : '🇬🇧' }}</span>
                        <span>{{ locale === 'id' ? 'Bahasa Indonesia' : 'English' }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800 font-mono">
                            {{ locale.toUpperCase() }}
                        </span>
                    </button>

                    <p class="text-[11px] text-slate-500 leading-normal">
                        {{ $t("footer.disclaimer") }}
                    </p>
                </div>
            </div>

            <!-- Bottom Copyright & Developer Credits Bar -->
            <div
                class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 pt-2"
            >
                <p class="font-medium text-slate-300">
                    {{ $t("footer.copyright") }}
                </p>
                <p class="text-[11px] text-slate-400 font-mono">
                    Developed with ❤️ by
                    <span class="text-blue-400 font-bold"
                        >Gusti Aidan (Agus Tinus Turnip)</span
                    >
                </p>
            </div>
        </div>
    </footer>
</template>
