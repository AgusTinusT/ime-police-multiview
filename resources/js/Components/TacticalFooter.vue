<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Crosshair,
    Globe,
    HelpCircle,
    Info,
    MessageSquareWarning,
    Scissors,
    Users,
} from "@lucide/vue";

const { locale } = useI18n();

const toggleLanguage = () => {
    locale.value = locale.value === "id" ? "en" : "id";
};
</script>

<template>
    <footer
        class="mt-auto bg-background border-t border-border text-muted-foreground font-sans pt-8 pb-6 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-(--breakpoint-2xl) mx-auto space-y-6">
            <!-- Top Footer Grid: Branding, Quick Navigation & System Status -->
            <div
                class="grid grid-cols-1 md:grid-cols-12 gap-6 pb-6 border-b border-border"
            >
                <!-- Branding & Community Info (Cols 1-5) -->
                <div class="md:col-span-5 space-y-3">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-10 h-10 rounded-md bg-card border border-border p-1 overflow-hidden shrink-0"
                        >
                            <img
                                :src="logoSaspColor"
                                class="w-full h-full object-contain rounded-sm"
                                alt="SASP Badge"
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

                <!-- Navigation Quick Links (Cols 6-8) -->
                <div class="md:col-span-4 space-y-2">
                    <h4
                        class="text-xs font-mono font-bold uppercase tracking-wider text-primary"
                    >
                        Navigasi Platform
                    </h4>
                    <ul class="grid grid-cols-2 gap-2 text-xs font-medium">
                        <li>
                            <Link
                                href="/multiview"
                                class="text-muted-foreground hover:text-foreground transition flex items-center gap-1.5 py-1"
                            >
                                <Crosshair class="size-3.5 shrink-0" />
                                <span>{{ $t("nav.multiview") }}</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/clipper"
                                class="text-primary hover:text-primary/80 font-bold transition flex items-center gap-1.5 py-1"
                            >
                                <Scissors class="size-3.5 shrink-0" />
                                <span>Clip Studio (BETA)</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/officers"
                                class="text-muted-foreground hover:text-foreground transition flex items-center gap-1.5 py-1"
                            >
                                <Users class="size-3.5 shrink-0" />
                                <span>{{ $t("nav.directory") }}</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/about"
                                class="text-muted-foreground hover:text-foreground transition flex items-center gap-1.5 py-1"
                            >
                                <Info class="size-3.5 shrink-0" />
                                <span>{{ $t("nav.about") }}</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/faq"
                                class="text-muted-foreground hover:text-foreground transition flex items-center gap-1.5 py-1"
                            >
                                <HelpCircle class="size-3.5 shrink-0" />
                                <span>{{ $t("nav.faq") }}</span>
                            </Link>
                        </li>
                        <li class="col-span-2">
                            <Link
                                href="/feedback"
                                class="text-muted-foreground hover:text-foreground transition flex items-center gap-1.5 py-1"
                            >
                                <MessageSquareWarning class="size-3.5 shrink-0" />
                                <span>{{ $t("nav.feedback") }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Legal / Language Switcher (Cols 9-12) -->
                <div class="md:col-span-3 space-y-3">
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
                    <span class="text-primary font-bold"
                        >Gusti Aidan (Agus Tinus Turnip)</span
                    >
                </p>
            </div>
        </div>
    </footer>
</template>
