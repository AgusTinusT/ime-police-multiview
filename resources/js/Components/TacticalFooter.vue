<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
const logoPoliceDuty = "/images/brand/police-duty/masters/police-duty-symbol-color.svg";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { ExternalLink, Globe } from "@lucide/vue";

const { locale } = useI18n();

const toggleLanguage = () => {
    locale.value = locale.value === "id" ? "en" : "id";
};
</script>

<template>
    <footer
        class="mt-auto bg-background border-t border-border text-muted-foreground font-sans pt-8 pb-[calc(5rem+env(safe-area-inset-bottom))] md:pb-6 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-(--breakpoint-2xl) mx-auto space-y-6">
            <!-- Top Footer Grid: Branding, 3 Columns Links, & Language Switcher -->
            <div
                class="grid grid-cols-1 md:grid-cols-12 gap-6 pb-6 border-b border-border"
            >
                <!-- Branding & Community Info (Cols 1-4) -->
                <div class="md:col-span-4 space-y-3">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-10 h-10 shrink-0 flex items-center justify-center"
                        >
                            <img
                                :src="logoPoliceDuty"
                                class="w-full h-full object-contain"
                                alt="Police Duty Badge"
                            />
                        </div>
                        <div>
                            <span
                                class="text-sm font-extrabold tracking-wider text-primary uppercase block leading-tight"
                                >IME ROLEPLAY</span
                            >
                            <span
                                class="text-xs font-bold tracking-wide text-foreground/80 uppercase block leading-tight"
                                >POLICE DUTY</span
                            >
                        </div>
                    </div>
                    <p
                        class="text-xs text-muted-foreground leading-relaxed max-w-md font-mono"
                    >
                        {{ $t("footer.tagline") }}
                    </p>
                </div>

                <!-- 3 Columns Links (Cols 5-10) -->
                <div class="md:col-span-6 grid grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- Kolom 1: Jelajahi -->
                    <div class="space-y-2">
                        <h4
                            class="text-xs font-mono font-bold uppercase tracking-wider text-foreground"
                        >
                            {{ $t("footer.col_explore") }}
                        </h4>
                        <ul class="space-y-1.5 text-xs font-medium">
                            <li>
                                <Link
                                    href="/multiview"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.multiview") }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/momen"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.clips") }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/officers"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.directory") }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/ranking"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.ranking") }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Kolom 2: Untuk perwira -->
                    <div class="space-y-2">
                        <h4
                            class="text-xs font-mono font-bold uppercase tracking-wider text-foreground"
                        >
                            {{ $t("footer.col_for_officers") }}
                        </h4>
                        <ul class="space-y-1.5 text-xs font-medium">
                            <li>
                                <Link
                                    href="/clip-studio"
                                    class="text-muted-foreground hover:text-foreground transition inline-flex items-center gap-1.5 py-0.5"
                                >
                                    <span>{{ $t("nav.clip_studio") }}</span>
                                    <Badge variant="success" class="font-mono text-xs px-1.5 py-0">
                                        BETA
                                    </Badge>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/dashboard/bio-links"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("footer.officer_bio_link") }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Kolom 3: Bantuan -->
                    <div class="space-y-2 col-span-2 md:col-span-1">
                        <h4
                            class="text-xs font-mono font-bold uppercase tracking-wider text-foreground"
                        >
                            {{ $t("footer.col_help") }}
                        </h4>
                        <ul class="space-y-1.5 text-xs font-medium">
                            <li>
                                <Link
                                    href="/about"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.about") }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/faq"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.faq") }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/updates"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.updates") }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/feedback"
                                    class="text-muted-foreground hover:text-foreground transition block py-0.5"
                                >
                                    {{ $t("nav.feedback") }}
                                </Link>
                            </li>
                            <li>
                                <a
                                    href="https://github.com/AgusTinusT/ime-police-multiview"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-muted-foreground hover:text-foreground transition inline-flex items-center gap-1 py-0.5"
                                >
                                    <span>{{ $t("footer.github_repo") }}</span>
                                    <ExternalLink class="size-3" />
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://discord.gg/gkaXxWpwhA"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-muted-foreground hover:text-foreground transition inline-flex items-center gap-1 py-0.5"
                                >
                                    <span>{{ $t("footer.discord_server") }}</span>
                                    <ExternalLink class="size-3" />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Legal / Language Switcher (Cols 11-12) -->
                <div class="md:col-span-2 space-y-3">
                    <h4
                        class="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground"
                    >
                        Bahasa / Language
                    </h4>
                    <!-- Language Switcher Button -->
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="text-xs gap-2"
                        @click="toggleLanguage"
                    >
                        <Globe class="size-3.5 text-muted-foreground" />
                        <span>{{
                            locale === "id" ? "Bahasa Indonesia" : "English"
                        }}</span>
                        <Badge variant="secondary" class="font-mono">
                            {{ locale.toUpperCase() }}
                        </Badge>
                    </Button>

                    <p class="text-xs text-muted-foreground leading-normal">
                        {{ $t("footer.disclaimer") }}
                    </p>
                </div>
            </div>

            <!-- Bottom Copyright & Developer Credits Bar -->
            <div
                class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground pt-2"
            >
                <p class="font-medium text-foreground/80">
                    {{ $t("footer.copyright") }}
                </p>
                <p class="text-xs text-muted-foreground font-mono">
                    Developed with ❤️ by
                    <a
                        href="https://github.com/AgusTinusT"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary font-bold hover:underline"
                        >Gusti Aidan (Agus Tinus Turnip)</a
                    >
                </p>
            </div>
        </div>
    </footer>
</template>
